<?php
require_once __DIR__ . '/../../config/database.php';
global $pdo;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_GET['action'] ?? '';
$caseId = isset($_REQUEST['case_id']) ? (int) $_REQUEST['case_id'] : 0;

if (!$caseId) {
    echo json_encode(['success' => false, 'error' => 'No case ID']);
    exit;
}

if ($action === 'ping') {
    // Received from radiologist
    $status = $_POST['status'] ?? 'viewing'; // 'typing' or 'viewing' or 'inactive'

    if ($status === 'inactive') {
        // Set rad_last_active far in the past so the patient's status check
        // immediately returns 'inactive' (diff >> 12s) — no waiting period.
        $stmt = $pdo->prepare("UPDATE cases SET rad_activity_status = 'inactive', rad_last_active = '1970-01-01 00:00:00' WHERE id = ?");
        $stmt->execute([$caseId]);
    } else {
        $stmt = $pdo->prepare("UPDATE cases SET rad_activity_status = ?, rad_last_active = NOW() WHERE id = ?");
        $stmt->execute([$status, $caseId]);
    }

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'status') {
    // Received from patient page
    $stmt = $pdo->prepare("
        SELECT 
            rad_activity_status, 
            TIMESTAMPDIFF(SECOND, rad_last_active, NOW()) as diff_seconds,
            status 
        FROM cases 
        WHERE id = ?
    ");
    $stmt->execute([$caseId]);
    $row = $stmt->fetch();
    
    if (!$row) {
        echo json_encode(['success' => false]);
        exit;
    }
    
    $radStatus = $row['rad_activity_status'];
    $diff = $row['diff_seconds'] !== null ? (int)$row['diff_seconds'] : 999999;
    
    $displayStatus = 'inactive';
    
    if ($radStatus) {
        if ($diff > 12) {
            // More than 12 seconds since last ping -> left the page or closed
            $displayStatus = 'inactive';
        } elseif ($radStatus === 'typing') {
            if ($diff <= 6) {
                // Typed recently
                $displayStatus = 'active'; // typing
            } else {
                // Stopped typing -> idle
                $displayStatus = 'idle';
            }
        } elseif ($radStatus === 'viewing') {
            // Viewing but not typing. If ping gets slightly delayed, it stays idle.
            $displayStatus = 'idle';
        }
    }
    
    echo json_encode([
        'success' => true,
        'state' => $displayStatus,
        'diff' => $diff
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
exit;
