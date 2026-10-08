<?php
/**
 * CitiLife Diagnostic System - 700 Case & Patient Seeder
 * Generates 100 realistic patients and completed cases per branch across 7 branches (Total: 700 cases)
 * Usage: php scripts/seed_700_cases.php
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

// 1. Setup Sample Images Directory
$uploadsBase = dirname(__DIR__) . '/public/assets/uploads/cases';
$samplesDir = $uploadsBase . '/samples';
if (!is_dir($samplesDir)) {
    mkdir($samplesDir, 0777, true);
}

// Source reference files to copy as standard samples
$sampleMapping = [
    'sample_chest_pa.png'        => 'case_185_1776945278_0.png',
    'sample_foot.png'            => 'case_184_1776941781_0.png',
    'sample_elbow_arm.png'       => 'case_186_1776945916_1.png',
    'sample_abdomen_upright.png' => 'case_187_1776954181_0.png',
    'sample_abdomen_pedia.png'   => 'case_188_1776999735_0.png',
    'sample_hand.png'            => 'case_186_1776945916_1.png',
    'sample_spine.png'           => 'case_185_1776945278_0.png',
];

echo "Preparing sample images in public/assets/uploads/cases/samples/...\n";
foreach ($sampleMapping as $targetSample => $sourceFile) {
    $targetPath = $samplesDir . '/' . $targetSample;
    $sourcePath = $uploadsBase . '/' . $sourceFile;
    if (!file_exists($targetPath)) {
        if (file_exists($sourcePath)) {
            copy($sourcePath, $targetPath);
            echo "  [+] Created: {$targetSample}\n";
        } else {
            // Fallback: search any png in uploadsBase
            $files = glob($uploadsBase . '/*.png');
            if (!empty($files)) {
                copy($files[0], $targetPath);
                echo "  [+] Created fallback: {$targetSample}\n";
            }
        }
    }
}
echo "Sample images ready!\n\n";

// 2. Fetch or assign Staff Users (Radiologist & Radtech)
$stmtRad = $pdo->query("SELECT id FROM users WHERE role = 'radiologist' ORDER BY id ASC LIMIT 1");
$radId = $stmtRad->fetchColumn() ?: 5;

$stmtTech = $pdo->query("SELECT id FROM users WHERE role = 'radtech' ORDER BY id ASC LIMIT 1");
$techId = $stmtTech->fetchColumn() ?: 7;

// 3. Branches Configuration (7 Branches)
$branches = [
    1 => ['name' => 'Gapan',         'code' => 'GAP', 'town' => 'Gapan City', 'brgys' => ['Bayanihan', 'San Vicente', 'San Nicolas', 'Bungo', 'Pambuan', 'Santa Cruz']],
    2 => ['name' => 'Bongabon',      'code' => 'BON', 'town' => 'Bongabon',   'brgys' => ['Poblacion', 'Commercial', 'Sinipit', 'Vega', 'Santor', 'Rizal']],
    3 => ['name' => 'Peñaranda',     'code' => 'PEN', 'town' => 'Peñaranda',  'brgys' => ['Poblacion I', 'Poblacion II', 'Santo Tomas', 'San Josef', 'Sinasajan']],
    4 => ['name' => 'General Tinio', 'code' => 'GTI', 'town' => 'General Tinio', 'brgys' => ['Padolina', 'Pulong Matong', 'Poblacion Central', 'San Pedro', 'Sampaguita']],
    5 => ['name' => 'Sto Domingo',   'code' => 'STD', 'town' => 'Sto. Domingo', 'brgys' => ['Malaya', 'Poblacion', 'San Francisco', 'San Pascual', 'Concepcion']],
    6 => ['name' => 'San Antonio',   'code' => 'SAN', 'town' => 'San Antonio', 'brgys' => ['Julo', 'Tikitiki', 'San Mariano', 'Santa Cruz', 'Lawang Kupang']],
    7 => ['name' => 'Pantabangan',   'code' => 'PAN', 'town' => 'Pantabangan', 'brgys' => ['East Poblacion', 'West Poblacion', 'Villarica', 'Fatima', 'Cadre Site']],
];

// Verify branches in DB
foreach ($branches as $bId => $bData) {
    $chk = $pdo->prepare("SELECT id FROM branches WHERE id = ?");
    $chk->execute([$bId]);
    if (!$chk->fetch()) {
        $insB = $pdo->prepare("INSERT INTO branches (id, name, address, status) VALUES (?, ?, ?, 'Active')");
        $insB->execute([$bId, $bData['name'], "{$bData['town']}, Nueva Ecija"]);
    }
}

// 4. Procedures & Templates Pool
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
        'exam_type' => 'Abdomen Upright',
        'service_id' => 6,
        'image' => 'public/assets/uploads/cases/samples/sample_abdomen_upright.png',
        'findings' => "Normal bowel gas distribution pattern throughout the abdomen.\nNo pathologically dilated small or large bowel loops seen.\nNo abnormal air-fluid levels or radiopaque densities noted.\nNo evidence of free air (pneumoperitoneum) beneath the diaphragmatic domes.",
        'impression' => "No radiographic signs of bowel obstruction or pneumoperitoneum."
    ],
    [
        'exam_type' => 'Abdomen (Pedia)',
        'service_id' => 5,
        'image' => 'public/assets/uploads/cases/samples/sample_abdomen_pedia.png',
        'findings' => "There is a normal distribution of bowel gas within the pediatric abdomen.\nNo dilated bowel loops or abnormal air-fluid levels are seen.\nNo radiopaque foreign bodies or abnormal calcifications are identified.\nThe soft tissue shadows are within normal limits, and the visualized bony structures appear intact.",
        'impression' => "No radiographic evidence of acute intra-abdominal pathology."
    ],
    [
        'exam_type' => 'Foot',
        'service_id' => 7,
        'image' => 'public/assets/uploads/cases/samples/sample_foot.png',
        'findings' => "The tarsal bones, metatarsals, and phalanges demonstrate normal alignment and bone density.\nNo evidence of cortical break, fracture, or dislocation is identified.\nIntertarsal, tarsometatarsal, and interphalangeal joint spaces are preserved.\nNo abnormal soft tissue swelling or foreign body seen.",
        'impression' => "No acute bony or articular abnormality of the foot."
    ],
    [
        'exam_type' => 'Elbow',
        'service_id' => 8,
        'image' => 'public/assets/uploads/cases/samples/sample_elbow_arm.png',
        'findings' => "The distal humerus, proximal radius, and ulna are intact.\nNormal articulation and alignment of the elbow joint.\nNo evidence of fracture, joint effusion, or anterior/posterior fat pad displacement.\nPeriarticular soft tissues are unremarkable.",
        'impression' => "Normal radiographic study of the elbow joint."
    ],
    [
        'exam_type' => 'Arm',
        'service_id' => 12,
        'image' => 'public/assets/uploads/cases/samples/sample_hand.png',
        'findings' => "The visualized long bones show smooth cortical margins and normal trabecular pattern.\nNo evidence of fracture, focal osseous lesion, or periosteal reaction.\nSurrounding muscular and soft tissue planes are intact.",
        'impression' => "No acute osseous injury."
    ],
    [
        'exam_type' => 'Cervical AP/LAT',
        'service_id' => 9,
        'image' => 'public/assets/uploads/cases/samples/sample_spine.png',
        'findings' => "Cervical lordosis is preserved. Vertebral body heights and alignment are maintained.\nIntervertebral disc spaces are intact with no significant disc height loss.\nNo evidence of fracture, subluxation, or destructive bone lesion.\nPrevertebral soft tissue thickness is within normal limits.",
        'impression' => "No acute cervical spine osseous injury. Unremarkable cervical spine study."
    ],
];

// 5. Realistic Filipino Names Pool
$firstNamesMale = [
    'Juan', 'Jose', 'Mark', 'John', 'Michael', 'Angelo', 'Christian', 'Daniel', 'Joshua',
    'Gabriel', 'Rafael', 'Paul', 'Anthony', 'Dominic', 'Vincent', 'Francis', 'Emmanuel',
    'Jericho', 'Kenneth', 'Adrian', 'Paolo', 'Carlo', 'Nathaniel', 'Arvin', 'Eduardo',
    'Ricardo', 'Ramon', 'Fernando', 'Danilo', 'Renato', 'Rowell', 'Rodolfo', 'Jaime',
    'Salvador', 'Alberto', 'Bernardo', 'Crisanto', 'Dante', 'Edgar', 'Felix', 'Gilbert'
];

$firstNamesFemale = [
    'Maria', 'Mary', 'Angelica', 'Princess', 'Camille', 'Christine', 'Bea', 'Kathryn',
    'Patricia', 'Nicole', 'Hannah', 'Andrea', 'Alyssa', 'Samantha', 'Jasmine', 'Kristine',
    'Rhea', 'Maricar', 'Bernadette', 'Teresa', 'Rosario', 'Lourdes', 'Carmela', 'Rowena',
    'Corazon', 'Imelda', 'Elena', 'Gloria', 'Aurora', 'Divina', 'Liza', 'Marian',
    'Jenny', 'Analyn', 'Gemma', 'Maricel', 'Rowena', 'Shiela', 'Aileen', 'Charito'
];

$middleNames = [
    'Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 'Mendoza', 'Torres',
    'Tomas', 'Aquino', 'Del Rosario', 'Villanueva', 'Ramos', 'Castro', 'Rivera',
    'Fernandez', 'Valdez', 'Domingo', 'Gonzales', 'Navarro', 'Soriano', 'Mercado'
];

$lastNames = [
    'Dela Cruz', 'Santos', 'Reyes', 'Garcia', 'Mendoza', 'Torres', 'Bautista', 'Flores',
    'Gonzales', 'Lopez', 'Hernandez', 'Perez', 'Sanchez', 'Ramirez', 'Castillo', 'Villanueva',
    'Ramos', 'Castro', 'Rivera', 'Fernandez', 'Valdez', 'Domingo', 'Morales', 'Mercado',
    'Santiago', 'Salazar', 'Aquino', 'Dizon', 'Corpuz', 'Tolentino', 'Bernardo', 'Pascual',
    'Manalo', 'Padilla', 'Soriano', 'Navarro', 'De Leon', 'Rosales', 'Cabrera', 'Agustin',
    'Evangelista', 'Alcantara', 'Enriquez', 'Velasco', 'Arellano', 'Ferrer', 'Ignacio'
];

$priorities = ['Routine', 'Routine', 'Routine', 'Normal', 'Urgent', 'STAT'];
$year = (int)date('Y');

echo "====================================================\n";
echo " Starting Generation of 100 Cases per Branch (700 Total)\n";
echo "====================================================\n\n";

$totalCreated = 0;

$pdo->beginTransaction();

try {
    foreach ($branches as $branchId => $branchInfo) {
        $branchCode = $branchInfo['code'];
        $branchName = $branchInfo['name'];
        echo "Processing Branch [{$branchId}] - {$branchName} ({$branchCode})...\n";

        // Get current case sequence for this branch
        $seqStmt = $pdo->prepare("SELECT current_number FROM branch_case_sequences WHERE branch_id = ? AND year = ? FOR UPDATE");
        $seqStmt->execute([$branchId, $year]);
        $currentSeq = $seqStmt->fetchColumn();
        if ($currentSeq === false) {
            $pdo->prepare("INSERT INTO branch_case_sequences (branch_id, year, current_number) VALUES (?, ?, 0)")->execute([$branchId, $year]);
            $currentSeq = 0;
        }
        $currentSeq = (int)$currentSeq;

        // Get current patient sequence for this branch
        $patPrefix = "PAT{$year}-{$branchCode}-";
        $pStmtLast = $pdo->prepare("SELECT patient_number FROM patients WHERE patient_number LIKE ? ORDER BY id DESC LIMIT 1");
        $pStmtLast->execute([$patPrefix . '%']);
        $lastPatient = $pStmtLast->fetchColumn();
        $patSeq = 1;
        if ($lastPatient && preg_match('/' . preg_quote($patPrefix, '/') . '(\d+)/', $lastPatient, $m)) {
            $patSeq = (int)$m[1] + 1;
        }

        for ($i = 1; $i <= 100; $i++) {
            // 1. Patient Data
            $isMale = (mt_rand(0, 1) === 1);
            $firstName = $isMale ? $firstNamesMale[array_rand($firstNamesMale)] : $firstNamesFemale[array_rand($firstNamesFemale)];
            $middleName = $middleNames[array_rand($middleNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $sex = $isMale ? 'Male' : 'Female';

            // Random Birthdate between 1955 and 2018
            $birthYear = mt_rand(1955, 2018);
            $birthMonth = str_pad(mt_rand(1, 12), 2, '0', STR_PAD_LEFT);
            $birthDay = str_pad(mt_rand(1, 28), 2, '0', STR_PAD_LEFT);
            $birthdate = "{$birthYear}-{$birthMonth}-{$birthDay}";

            $brgy = $branchInfo['brgys'][array_rand($branchInfo['brgys'])];
            $homeAddress = "Brgy. {$brgy}, {$branchInfo['town']}, Nueva Ecija";
            $contactNumber = '09' . mt_rand(10, 99) . '-' . mt_rand(100, 999) . '-' . mt_rand(1000, 9999);
            $patientEmail = strtolower(str_replace(' ', '', $firstName)) . '.' . strtolower(str_replace(' ', '', $lastName)) . mt_rand(10, 999) . '@gmail.com';

            $patientNumber = $patPrefix . str_pad($patSeq, 5, '0', STR_PAD_LEFT);
            $patSeq++;

            // Insert Patient
            $insPat = $pdo->prepare("INSERT INTO patients (patient_number, first_name, middle_name, last_name, sex, birthdate, contact_number, email, home_address, branch_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            // Distributed created_at date in the past 120 days
            $daysAgo = mt_rand(1, 120);
            $caseCreatedTime = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days + " . mt_rand(8, 16) . " hours + " . mt_rand(0, 59) . " minutes"));
            $completedTime = date('Y-m-d H:i:s', strtotime("{$caseCreatedTime} + " . mt_rand(15, 120) . " minutes"));

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

            // 2. Case Data
            $currentSeq++;
            $caseNumber = "{$branchCode}{$year}-" . str_pad($currentSeq, 5, '0', STR_PAD_LEFT);

            $template = $examTemplates[array_rand($examTemplates)];
            $priority = $priorities[array_rand($priorities)];
            
            $hasPhilhealth = (mt_rand(0, 10) > 3); // 70% with PhilHealth
            $philhealthStatus = $hasPhilhealth ? 'With PhilHealth Card' : 'Without PhilHealth Card';
            $philhealthId = $hasPhilhealth ? (mt_rand(10, 99) . '-' . mt_rand(100000000, 999999999) . '-' . mt_rand(1, 9)) : null;

            $imagePathJson = json_encode([$template['image']]);

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

            $totalCreated++;
        }

        // Update branch sequence
        $pdo->prepare("UPDATE branch_case_sequences SET current_number = ? WHERE branch_id = ? AND year = ?")
            ->execute([$currentSeq, $branchId, $year]);

        echo "  --> Branch {$branchName}: Generated 100 cases ({$branchCode}{$year}-" . str_pad($currentSeq - 99, 5, '0', STR_PAD_LEFT) . " to {$branchCode}{$year}-" . str_pad($currentSeq, 5, '0', STR_PAD_LEFT) . ")\n";
    }

    $pdo->commit();

    echo "\n====================================================\n";
    echo " SUCCESS! Successfully seeded {$totalCreated} cases across 7 branches!\n";
    echo " All records are marked Completed, Approved, with matched X-ray images.\n";
    echo "====================================================\n";

} catch (Exception $e) {
    $pdo->rollBack();
    die("\n[ERROR] Seeding failed and transaction was rolled back: " . $e->getMessage() . "\n");
}
