<?php
// radiologists_status.php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

try {
    $stmtRad = $pdo->prepare("
        SELECT 
            u.id, 
            COALESCE(NULLIF(u.full_name_report, ''), NULLIF(u.name, ''), SUBSTRING_INDEX(u.email, '@', 1)) AS radiologist_name,
            COUNT(c.id) AS active_case_count,
            u.is_available
        FROM users u
        LEFT JOIN cases c ON u.id = c.radiologist_id AND c.status IN ('Pending', 'Under Reading')
        WHERE u.role = 'radiologist' AND u.status = 'Active'
        GROUP BY u.id
    ");
    $stmtRad->execute();
    $radiologistsList = $stmtRad->fetchAll(\PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $radiologistsList]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
