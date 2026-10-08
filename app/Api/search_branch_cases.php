<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
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
$birthdate = trim($_GET['birthdate'] ?? '');

if (empty($patientName) || empty($branch)) {
    echo json_encode(['success' => false, 'error' => 'Missing search parameters']);
    exit;
}

try {
    $whereConditions = [
        "b.name = :branch",
        "c.released = 1",
        "c.status IN ('Completed', 'Released')",
        "c.exam_type != 'To be determined'",
        "(REPLACE(REPLACE(CONCAT(p.first_name, ' ', p.last_name), '-', ''), ' ', '') LIKE :name_clean
         OR REPLACE(p.first_name, '-', '') LIKE :name_clean
         OR REPLACE(p.last_name, '-', '') LIKE :name_clean)"
    ];

    $params = [
        'branch' => $branch,
        'name_clean' => '%' . str_replace([' ', '-'], '', $patientName) . '%'
    ];

    if (!empty($birthdate)) {
        $whereConditions[] = "p.birthdate = :birthdate";
        $params['birthdate'] = $birthdate;
    }

    $whereSql = implode(' AND ', $whereConditions);

    $stmt = $pdo->prepare("
        SELECT 
            c.id, p.patient_number, c.case_number, 
            p.first_name, p.last_name, p.birthdate,
            TIMESTAMPDIFF(YEAR, p.birthdate, CURDATE()) AS age,
            c.exam_type, b.name as branch_name,
            c.created_at 
        FROM cases c
        INNER JOIN patients p ON c.patient_id = p.id
        LEFT JOIN branches b ON c.branch_id = b.id
        WHERE {$whereSql}
        ORDER BY c.created_at DESC
        LIMIT 50
    ");
    
    $stmt->execute($params);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as &$r) {
        $r['full_name'] = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
    }
    
    echo json_encode(['success' => true, 'data' => $results]);
    
} catch (Exception $e) {
    error_log("Search branch cases error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred while searching cases. Please try again.']);
}
