<?php
/**
 * CitiLife Diagnostic System - Master Patient & Case Alignment (700 Cases / 100 per Branch)
 * Strictly aligns Patient Numbers (PAT2026-[CODE]-00001..00100) with Case Numbers ([CODE]2026-00001..00100)
 * All records set to Completed & Released (Queue 0, Records 100 per branch)
 * Usage: php scripts/align_700_cases_and_patients.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(0);

$dbConfig = require __DIR__ . '/../config/db.php';

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "====================================================\n";
    echo " Connected to database: {$dbConfig['dbname']} ({$dbConfig['host']})\n";
    echo "====================================================\n\n";
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage() . "\n");
}

try {
    // Temporarily disable foreign key checks for clean recreation
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE result_disputes");
    $pdo->exec("TRUNCATE TABLE cases");
    $pdo->exec("TRUNCATE TABLE patients");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    $pdo->beginTransaction();
    // 1. Fetch Staff Users (Radiologist & Radtech)
    $stmtRad = $pdo->query("SELECT id FROM users WHERE role = 'radiologist' ORDER BY id ASC LIMIT 1");
    $radId = (int)$stmtRad->fetchColumn() ?: 5;

    $stmtTech = $pdo->query("SELECT id FROM users WHERE role = 'radtech' ORDER BY id ASC LIMIT 1");
    $techId = (int)$stmtTech->fetchColumn() ?: 7;

    // 2. Procedures & Templates Pool
    $examTemplates = [
        [
            'exam_type' => 'Chest PA',
            'service_id' => 1,
            'image' => 'public/assets/uploads/cases/samples/sample_chest_pa.png',
            'findings' => "The lung fields are clear without evidence of focal consolidation, active infiltrates, or mass lesions.\nThe cardiac silhouette and mediastinal contours are within normal limits for size and configuration.\nThe costophrenic angles are sharp and well-defined. No pleural effusion or pneumothorax is identified.\nThe visualized thoracic cage and bony structures are intact without acute fracture.",
            'impression' => "No radiographic evidence of active cardiopulmonary disease."
        ],
        [
            'exam_type' => 'Chest AP',
            'service_id' => 2,
            'image' => 'public/assets/uploads/cases/samples/sample_chest_pa.png',
            'findings' => "Trachea is midline. Normal bronchovascular markings are seen bilaterally.\nNo definite pulmonary infiltrate or congestion noted.\nHeart is not enlarged. Hemidiaphragms and costophrenic sulci are intact.\nOsseous cage shows no acute deformity.",
            'impression' => "Clear lung fields. Normal chest radiograph."
        ],
        [
            'exam_type' => 'Chest AP/LAT',
            'service_id' => 3,
            'image' => 'public/assets/uploads/cases/samples/sample_chest_pa.png',
            'findings' => "Biapical lung zones are clear. Costophrenic sulci are sharp.\nHeart size and pulmonary vascularity are normal.\nNo evidence of pleural effusion or parenchymal infiltrate in both AP and lateral projections.",
            'impression' => "No acute cardiopulmonary disease."
        ],
        [
            'exam_type' => 'Abdomen Upright',
            'service_id' => 6,
            'image' => 'public/assets/uploads/cases/samples/sample_abdomen_upright.png',
            'findings' => "Normal bowel gas distribution pattern throughout the abdomen.\nNo pathologically dilated small or large bowel loops seen.\nNo abnormal air-fluid levels or radiopaque densities noted.\nNo evidence of free air (pneumoperitoneum) beneath the diaphragmatic domes.",
            'impression' => "No radiographic signs of bowel obstruction or pneumoperitoneum."
        ],
        [
            'exam_type' => 'Foot',
            'service_id' => 7,
            'image' => 'public/assets/uploads/cases/samples/sample_foot.png',
            'findings' => "Cortical margins and trabecular patterns of the tarsals, metatarsals, and phalanges are intact.\nJoint spaces are preserved without significant narrowing or osteophyte formation.\nNo acute cortical disruption, fracture, or joint dislocation identified.\nSoft tissue shadows are unremarkable without edema or foreign body radiopacity.",
            'impression' => "No radiographic evidence of acute bony fracture or dislocation in the foot."
        ],
        [
            'exam_type' => 'Elbow',
            'service_id' => 8,
            'image' => 'public/assets/uploads/cases/samples/sample_elbow_arm.png',
            'findings' => "Visualized distal humerus, proximal radius, and ulna show normal bony density and alignment.\nRadio-capitellar and ulno-humeral joint alignments are anatomically preserved.\nNo visible anterior or posterior fat pad sign (joint effusion) detected.\nNo fracture line or periosteal reaction noted.",
            'impression' => "Normal elbow radiographic study. No acute osseous injury."
        ],
        [
            'exam_type' => 'Arm',
            'service_id' => 12,
            'image' => 'public/assets/uploads/cases/samples/sample_elbow_arm.png',
            'findings' => "Humeral shaft and surrounding muscular contours are intact and well-aligned.\nNo cortical discontinuity, periosteal reaction, or pathologic bone lesion.\nAdjacent glenohumeral and elbow articulating margins are preserved.",
            'impression' => "Normal arm radiograph. No evidence of acute fracture."
        ],
        [
            'exam_type' => 'Cervical AP/LAT',
            'service_id' => 9,
            'image' => 'public/assets/uploads/cases/samples/sample_spine.png',
            'findings' => "Vertebral body heights and alignment of the cervical spine are maintained.\nIntervertebral disc spaces are well-preserved with no significant subluxation.\nPrevertebral soft tissue thickness is normal.\nNo focal destructive or osteolytic osseous lesions noted.",
            'impression' => "Normal cervical spine series. No acute fracture or dislocation."
        ],
    ];

    $priorities = ['Routine', 'Routine', 'Routine', 'Urgent', 'Routine', 'STAT'];

    // 3. Filipino Names Pool
    $firstNamesMale = [
        'Sedji', 'Juan', 'Jose', 'Mark', 'John', 'Michael', 'Angelo', 'Gabriel', 'Joshua', 'Christian',
        'Daniel', 'Emmanuel', 'Kenneth', 'Kevin', 'Dominic', 'Rodel', 'Danilo', 'Edgar', 'Eduardo',
        'Fernando', 'Gilbert', 'Harold', 'Ignacio', 'Jerome', 'Leonardo', 'Manuel', 'Nestor', 'Orlando',
        'Pedro', 'Ramon', 'Salvador', 'Tomas', 'Vicente', 'Wilfredo', 'Alexander', 'Benjamin', 'Carlo',
        'Dante', 'Ernesto', 'Francis', 'Gregorio', 'Henry', 'Isagani', 'Joel', 'Karl', 'Luis', 'Mario'
    ];

    $firstNamesFemale = [
        'Maria', 'Ana', 'Angelica', 'Bea', 'Catherine', 'Christine', 'Diana', 'Elena', 'Francheska',
        'Grace', 'Hazel', 'Irene', 'Jasmine', 'Kristine', 'Liza', 'Margie', 'Nicole', 'Patricia',
        'Rhea', 'Sarah', 'Teresa', 'Vanessa', 'Wilma', 'Yvonne', 'Abigail', 'Carmela', 'Divina',
        'Estrella', 'Flor', 'Gemma', 'Imelda', 'Joy', 'Karen', 'Lourdes', 'Maricel', 'Norma', 'Ofelia'
    ];

    $lastNames = [
        'Pascual', 'Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 'Mendoza', 'Torres',
        'Tomas', 'Castillo', 'Flores', 'Villanueva', 'Ramos', 'Castro', 'Rivera', 'Aquino', 'Navarro',
        'Salazar', 'Mercado', 'Del Rosario', 'Valdez', 'Soriano', 'Guevarra', 'Corpuz', 'Tolentino',
        'Manabat', 'Maglaque', 'Domingo', 'Ferrer', 'Ignacio', 'Agustin', 'De Leon', 'San Pedro',
        'Ventura', 'David', 'Miranda', 'Santiago', 'Cabrera', 'Perez', 'Tan', 'Morales', 'Hernandez'
    ];

    // 4. Branches Definition
    $branches = [
        1 => ['name' => 'Gapan',         'code' => 'GAP', 'town' => 'Gapan City', 'brgys' => ['Bayanihan', 'San Vicente', 'San Nicolas', 'Bungo', 'Pambuan', 'Santa Cruz']],
        2 => ['name' => 'Bongabon',      'code' => 'BON', 'town' => 'Bongabon',   'brgys' => ['Poblacion', 'Commercial', 'Sinipit', 'Vega', 'Santor', 'Rizal']],
        3 => ['name' => 'Peñaranda',     'code' => 'PEN', 'town' => 'Peñaranda',  'brgys' => ['Poblacion I', 'Poblacion II', 'Santo Tomas', 'San Josef', 'Sinasajan']],
        4 => ['name' => 'General Tinio', 'code' => 'GTI', 'town' => 'General Tinio', 'brgys' => ['Padolina', 'Pulong Matong', 'Poblacion Central', 'San Pedro', 'Sampaguita']],
        5 => ['name' => 'Sto Domingo',   'code' => 'STD', 'town' => 'Sto. Domingo', 'brgys' => ['Malaya', 'Poblacion', 'San Francisco', 'San Pascual', 'Concepcion']],
        6 => ['name' => 'San Antonio',   'code' => 'SAN', 'town' => 'San Antonio', 'brgys' => ['Julo', 'Tikitiki', 'San Mariano', 'Santa Cruz', 'Lawang Kupang']],
        7 => ['name' => 'Pantabangan',   'code' => 'PAN', 'town' => 'Pantabangan', 'brgys' => ['East Poblacion', 'West Poblacion', 'Villarica', 'Fatima', 'Cadre Site']],
    ];

    echo "Cleared old records. Generating 100 perfectly aligned patients & cases per branch...\n\n";

    $year = '2026';
    $totalPatients = 0;
    $totalCases = 0;

    foreach ($branches as $branchId => $branchInfo) {
        $branchCode = $branchInfo['code'];
        $branchName = $branchInfo['name'];

        echo "Seeding Branch {$branchId}: {$branchName} ({$branchCode})...\n";

        for ($seq = 1; $seq <= 100; $seq++) {
            $seqStr = str_pad($seq, 5, '0', STR_PAD_LEFT);
            $patientNumber = "PAT{$year}-{$branchCode}-{$seqStr}";
            $caseNumber = "{$branchCode}{$year}-{$seqStr}";

            // Date sequence: Case 00001 is 100 days ago, Case 00100 is today
            $daysAgo = 101 - $seq;
            $hour = 8 + ($seq % 9); // 08:00 to 17:00
            $minute = ($seq * 7) % 60;
            $caseCreatedTime = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days + {$hour} hours + {$minute} minutes"));
            $completedTime = date('Y-m-d H:i:s', strtotime("{$caseCreatedTime} + 35 minutes"));

            // Specific Patient 1 in Gapan (Sedji Pascual - Main Account)
            if ($branchId === 1 && $seq === 1) {
                $firstName = 'Sedji';
                $middleName = 'Reyes';
                $lastName = 'Pascual';
                $sex = 'Male';
                $birthdate = '2005-01-01';
                $contactNumber = '0985-767-3975';
                $patientEmail = 'seigipascual09@gmail.com';
                $homeAddress = '0089 Purok 3, Mangino, Gapan City, Nueva Ecija';
            } else {
                $isMale = ($seq % 2 !== 0);
                $firstName = $isMale 
                    ? $firstNamesMale[($seq + $branchId) % count($firstNamesMale)]
                    : $firstNamesFemale[($seq + $branchId) % count($firstNamesFemale)];
                $middleName = $lastNames[($seq * 3 + $branchId) % count($lastNames)];
                $lastName = $lastNames[($seq * 7 + $branchId) % count($lastNames)];
                $sex = $isMale ? 'Male' : 'Female';
                
                $birthYear = 1950 + (($seq * 13) % 55); // Ages 21 to 75
                $birthMonth = str_pad((($seq * 3) % 12) + 1, 2, '0', STR_PAD_LEFT);
                $birthDay = str_pad((($seq * 7) % 28) + 1, 2, '0', STR_PAD_LEFT);
                $birthdate = "{$birthYear}-{$birthMonth}-{$birthDay}";

                $brgy = $branchInfo['brgys'][$seq % count($branchInfo['brgys'])];
                $homeAddress = "Brgy. {$brgy}, {$branchInfo['town']}, Nueva Ecija";
                $contactNumber = '09' . mt_rand(10, 99) . '-' . mt_rand(100, 999) . '-' . mt_rand(1000, 9999);
                $patientEmail = strtolower(str_replace(' ', '', $firstName)) . '.' . strtolower(str_replace(' ', '', $lastName)) . $seq . '@gmail.com';
            }

            // Insert Patient
            $insPat = $pdo->prepare("INSERT INTO patients (patient_number, first_name, middle_name, last_name, sex, birthdate, contact_number, email, home_address, branch_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insPat->execute([
                $patientNumber,
                $firstName,
                $middleName,
                $lastName,
                $sex,
                $birthdate,
                $contactNumber,
                $patientEmail,
                $homeAddress,
                $branchId,
                $caseCreatedTime
            ]);
            $patientId = (int)$pdo->lastInsertId();
            $totalPatients++;

            // Template & Priority
            $tplIdx = ($seq + $branchId) % count($examTemplates);
            $template = $examTemplates[$tplIdx];
            $priority = $priorities[$seq % count($priorities)];

            $hasPhilhealth = ($seq % 4 !== 0); // 75% PhilHealth
            $philhealthStatus = $hasPhilhealth ? 'With PhilHealth Card' : 'Without PhilHealth Card';
            $philhealthId = $hasPhilhealth ? (mt_rand(10, 99) . '-' . mt_rand(100000000, 999999999) . '-' . mt_rand(1, 9)) : null;

            $imagePathJson = json_encode([$template['image']]);

            // Insert Case
            $insCase = $pdo->prepare("INSERT INTO cases (
                case_number, patient_id, branch_id, service_type, service_id, exam_type, priority,
                philhealth_status, philhealth_id, status, report_status, approval_status,
                image_status, image_path, released, created_at, clinical_information,
                findings, impression, recommendation, radiologist_id, radtech_id,
                date_completed, report_template
            ) VALUES (
                ?, ?, ?, 'X-Ray', ?, ?, ?,
                ?, ?, 'Completed', 'Final', 'Approved',
                'Uploaded', ?, 1, ?, 'Routine medical check-up / diagnostic evaluation',
                ?, ?, '', ?, ?,
                ?, ?
            )");

            $insCase->execute([
                $caseNumber,
                $patientId,
                $branchId,
                $template['service_id'],
                $template['exam_type'],
                $priority,
                $philhealthStatus,
                $philhealthId,
                $imagePathJson,
                $caseCreatedTime,
                $template['findings'],
                $template['impression'],
                $radId,
                $techId,
                $completedTime,
                $template['exam_type']
            ]);
            $totalCases++;
        }

        // Update branch sequence to 100
        $pdo->prepare("INSERT INTO branch_case_sequences (branch_id, year, current_number) VALUES (?, ?, 100) ON DUPLICATE KEY UPDATE current_number = 100")
            ->execute([$branchId, (int)$year]);

        echo "  --> Branch {$branchName}: Completed 100 aligned records ({$branchCode}{$year}-00001 to {$branchCode}{$year}-00100)\n";
    }

    // 5. Seed 50 Resolved Disputes per Branch (350 total)
    echo "\nSeeding 50 Resolved Correction Requests per Branch...\n";

    $disputeTemplates = [
        [
            'category' => 'demographic_error',
            'desc' => "Please correct the spelling of patient's middle name and update the current barangay address.",
            'rad_note' => "Patient provided valid ID. Demographics verified and updated in master records.",
            'res_note' => "Patient information successfully updated and verified."
        ],
        [
            'category' => 'findings_error',
            'desc' => "Typographical error noticed in the findings description text.",
            'rad_note' => "Reviewed report text with Radiologist. Typographical wording refined.",
            'res_note' => "Findings description updated and amended report re-released."
        ],
        [
            'category' => 'template_error',
            'desc' => "Requesting clarification and standard naming format on the examination view title.",
            'rad_note' => "Exam template naming aligned with official clinic protocol.",
            'res_note' => "Template header corrected and finalized."
        ],
        [
            'category' => 'both_error',
            'desc' => "Minor typo in findings and request to check middle initial on receipt.",
            'rad_note' => "Checked patient file and adjusted findings wording and middle name.",
            'res_note' => "Both patient info and findings verified and amended."
        ],
        [
            'category' => 'exam_details_error',
            'desc' => "Requesting to confirm projection angle notation in the formal clinical impression.",
            'rad_note' => "Checked radiographic film series. Projection confirmed and clarified in report.",
            'res_note' => "Exam details validated and amended report released."
        ],
        [
            'category' => 'other_error',
            'desc' => "Request for amended printed copy with updated physician license signature alignment.",
            'rad_note' => "Digital certificate and signature layout updated.",
            'res_note' => "Report amended with updated digital verification."
        ]
    ];

    $insDisp = $pdo->prepare("INSERT INTO result_disputes (
        case_id, patient_id, branch_id, dispute_category, description,
        old_findings, old_impression, status, assigned_role, radtech_notes,
        resolution_notes, demographics_fixed, radiologist_amended,
        resolved_by, resolved_at, created_at
    ) VALUES (
        ?, ?, ?, ?, ?,
        ?, ?, 'Resolved', 'radtech', ?,
        ?, 1, 1,
        ?, ?, ?
    )");

    $totalDisputes = 0;
    foreach ($branches as $branchId => $branchInfo) {
        // Fetch first 50 cases in this branch
        $branchCases = $pdo->prepare("SELECT id, patient_id, findings, impression, created_at FROM cases WHERE branch_id = ? ORDER BY id ASC LIMIT 50");
        $branchCases->execute([$branchId]);
        $cRows = $branchCases->fetchAll();

        foreach ($cRows as $idx => $cRow) {
            $tpl = $disputeTemplates[$idx % count($disputeTemplates)];
            $dispCreated = date('Y-m-d H:i:s', strtotime("{$cRow['created_at']} + 2 hours"));
            $dispResolved = date('Y-m-d H:i:s', strtotime("{$dispCreated} + 45 minutes"));

            $insDisp->execute([
                $cRow['id'],
                $cRow['patient_id'],
                $branchId,
                $tpl['category'],
                $tpl['desc'],
                $cRow['findings'],
                $cRow['impression'],
                $tpl['rad_note'],
                $tpl['res_note'],
                $techId,
                $dispResolved,
                $dispCreated
            ]);
            $totalDisputes++;
        }
    }

    $pdo->commit();

    echo "\n====================================================\n";
    echo " SUCCESS!\n";
    echo " Total Patients Seeded: {$totalPatients}\n";
    echo " Total Cases Seeded:    {$totalCases}\n";
    echo " Total Disputes Seeded: {$totalDisputes}\n";
    echo " All cases strictly 1-to-1: [CODE]2026-00001 -> PAT2026-[CODE]-00001\n";
    echo " All cases are Completed & Released (Queue is 0, Records has all 700)\n";
    echo "====================================================\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("\n[ERROR] Alignment failed: " . $e->getMessage() . "\n");
}
