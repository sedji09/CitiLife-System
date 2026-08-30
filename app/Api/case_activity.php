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
    $diff = $row['diff_seconds'] !== null ? (int) $row['diff_seconds'] : 999999;

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
        'state'   => $displayStatus,
        'diff'    => $diff
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
exit;
