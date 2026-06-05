<?php
require_once __DIR__ . '/../../config/database.php';
global $pdo;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? null;
if ($role !== 'radtech' && $role !== 'admin' && $role !== 'radiologist') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$patientName = trim($_GET['patient_name'] ?? '');
$branch = trim($_GET['branch'] ?? '');

if (empty($patientName) || empty($branch)) {
    echo json_encode(['success' => false, 'error' => 'Missing search parameters']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            c.id, p.patient_number, c.case_number, 
            p.first_name, p.last_name, c.exam_type, b.name as branch_name,
            c.created_at 
        FROM cases c
        INNER JOIN patients p ON c.patient_id = p.id
        LEFT JOIN branches b ON c.branch_id = b.id
        WHERE 
            b.name = :branch AND
            c.released = 1 AND
            c.approval_status = 'Approved' AND
            c.status = 'Completed' AND
            c.exam_type != 'To be determined' AND
            (REPLACE(REPLACE(CONCAT(p.first_name, ' ', p.last_name), '-', ''), ' ', '') LIKE :name_clean
             OR REPLACE(p.first_name, '-', '') LIKE :name_clean
             OR REPLACE(p.last_name, '-', '') LIKE :name_clean)
        ORDER BY c.created_at DESC
        LIMIT 50
    ");
    
    $searchClean = '%' . str_replace([' ', '-'], '', $patientName) . '%';
    
    $stmt->execute([
        'branch' => $branch,
        'name_clean' => $searchClean
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as &$r) {
        $r['full_name'] = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
    }
    
    echo json_encode(['success' => true, 'data' => $results]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database query failed: ' . $e->getMessage()]);
}
