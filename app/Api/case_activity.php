<?php
require_once __DIR__ . '/../../config/database.php';
global $pdo;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- AUTH GUARD ---
// Every request to this endpoint requires an active session.
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$sessionRole    = $_SESSION['role'] ?? '';
$sessionUserId  = (int) ($_SESSION['user_id'] ?? 0);
$sessionPatientId = isset($_SESSION['patient_id']) ? (int) $_SESSION['patient_id'] : null;

$action = $_GET['action'] ?? '';
$caseId = isset($_REQUEST['case_id']) ? (int) $_REQUEST['case_id'] : 0;

if (!$caseId) {
    echo json_encode(['success' => false, 'error' => 'No case ID']);
    exit;
}

if ($action === 'ping') {
    // --- ROLE GUARD: Only radiologist (or other staff) may ping ---
    $staffRoles = ['radiologist', 'radtech', 'branch_admin', 'admin_central', 'it_admin'];
    if (!in_array($sessionRole, $staffRoles, true)) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $status = $_POST['status'] ?? $_GET['status'] ?? $_REQUEST['status'] ?? 'viewing'; // 'typing' or 'viewing' or 'inactive'

    if ($status === 'inactive') {
        // Set rad_last_active far in the past so the patient's status check
        // immediately returns 'inactive' (diff >> 12s) — no waiting period.
        $stmt = $pdo->prepare("UPDATE cases SET rad_activity_status = 'inactive', rad_last_active = '1970-01-01 00:00:00' WHERE id = ?");
        $stmt->execute([$caseId]);
    } else {
        $stmt = $pdo->prepare("UPDATE cases SET rad_activity_status = ?, rad_last_active = NOW() WHERE id = ?");
        $stmt->execute([$status, $caseId]);

        // If typing and an active dispute exists, advance status to 'Correction in Progress'
        if ($status === 'typing') {
            $stmtChkDisp = $pdo->prepare("SELECT id FROM result_disputes WHERE case_id = ? AND status IN ('Issue Reported', 'For RadTech Review', 'Pending RadTech Review') LIMIT 1");
            $stmtChkDisp->execute([$caseId]);
            $activeDispId = $stmtChkDisp->fetchColumn();
            if ($activeDispId) {
                $pdo->prepare("UPDATE result_disputes SET status = 'Correction in Progress', updated_at = NOW() WHERE id = ?")
                    ->execute([$activeDispId]);
                $pdo->prepare("UPDATE cases SET status = 'Correction in Progress', status_timestamp = NOW() WHERE id = ?")
                    ->execute([$caseId]);
            }
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'status') {
    // --- OWNERSHIP GUARD: Patients may only poll their own cases ---
    if ($sessionRole === 'patient') {
        if (!$sessionPatientId) {
            // Attempt to resolve patient_id from users table if not cached in session
            $stmtP = $pdo->prepare("SELECT patient_id FROM users WHERE id = ? LIMIT 1");
            $stmtP->execute([$sessionUserId]);
            $sessionPatientId = (int) ($stmtP->fetchColumn() ?: 0);
            if ($sessionPatientId) {
                $_SESSION['patient_id'] = $sessionPatientId;
            }
        }

        if (!$sessionPatientId) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        // Verify the requested case_id belongs to this patient
        $stmtOwn = $pdo->prepare("SELECT id FROM cases WHERE id = ? AND patient_id = ? LIMIT 1");
        $stmtOwn->execute([$caseId, $sessionPatientId]);
        if (!$stmtOwn->fetchColumn()) {
            // Case not found or belongs to another patient — return silent inactive
            echo json_encode(['success' => true, 'state' => 'inactive', 'diff' => 999999]);
            exit;
        }
    }

    // Received from patient page (or staff)
    $stmt = $pdo->prepare("
        SELECT 
            rad_activity_status, 
            TIMESTAMPDIFF(SECOND, rad_last_active, NOW()) as diff_seconds,
            TIMESTAMPDIFF(HOUR, created_at, NOW()) as elapsed_hours,
            status,
            released
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
    $diff = $row['diff_seconds'] !== null ? (int) $row['diff_seconds'] : 999999;

    $displayActivity = 'inactive';
    $isTyping = false;

    if ($radStatus) {
        if ($diff > 15) {
            // More than 15 seconds since last ping -> left the page or closed
            $displayActivity = 'inactive';
        } elseif ($radStatus === 'typing') {
            $displayActivity = 'active';
            $isTyping = ($diff <= 6);
        } elseif ($radStatus === 'viewing') {
            // Staff is actively viewing/working on the case page
            $displayActivity = 'active';
        }
    }

    // Check if there is an active dispute for this case
    $stmtDisp = $pdo->prepare("SELECT status FROM result_disputes WHERE case_id = ? AND status NOT IN ('Resolved', 'Correction Completed') ORDER BY id DESC LIMIT 1");
    $stmtDisp->execute([$caseId]);
    $dispStatus = $stmtDisp->fetchColumn();

    $caseStatus = $row['status'] ?: 'Pending';
    $effectiveDisplayStatus = $dispStatus ?: $caseStatus;

    if ($effectiveDisplayStatus === 'Escalated to Radiologist') {
        $effectiveDisplayStatus = 'Correction in Progress';
    }

    $isOverdue = ((int)($row['elapsed_hours'] ?? 0)) >= 3;
    if ($effectiveDisplayStatus === 'Pending' && $isOverdue) {
        $effectiveDisplayStatus = 'Overdue';
    }

    echo json_encode([
        'success'        => true,
        'state'          => $displayActivity,
        'is_typing'      => $isTyping,
        'diff'           => $diff,
        'case_status'    => $caseStatus,
        'display_status' => $effectiveDisplayStatus,
        'is_released'    => (int)($row['released'] ?? 0) === 1
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
exit;
