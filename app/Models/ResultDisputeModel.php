<?php
/**
 * ResultDisputeModel.php
 * Model for handling result error reports and strict 5-step dispute workflow.
 */

class ResultDisputeModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new dispute entry (Step 2: Always starts with Pending RadTech Review & assigned to radtech)
     */
    public function createDispute($caseId, $patientId, $branchId, $category, $description) {
        $stmt = $this->pdo->prepare("
            INSERT INTO result_disputes (case_id, patient_id, branch_id, dispute_category, description, status, assigned_role, created_at)
            VALUES (?, ?, ?, ?, ?, 'Pending RadTech Review', 'radtech', NOW())
        ");
        $stmt->execute([$caseId, $patientId, $branchId, $category, $description]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Get disputes for a patient
     */
    public function getDisputesByPatient($patientId) {
        $stmt = $this->pdo->prepare("
            SELECT rd.*, c.case_number, c.exam_type, b.name as branch_name
            FROM result_disputes rd
            JOIN cases c ON rd.case_id = c.id
            LEFT JOIN branches b ON rd.branch_id = b.id
            WHERE rd.patient_id = ?
            ORDER BY rd.created_at DESC
        ");
        $stmt->execute([$patientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get active dispute for a specific case
     */
    public function getActiveDisputeByCase($caseId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM result_disputes
            WHERE case_id = ? AND status != 'Resolved' AND status != 'Rejected'
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$caseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get disputes for Clinic with STRICT ROLE FILTERING (Step 3 & Step 4)
     */
    public function getDisputesForClinic($branchId = null, $role = null, $status = null) {
        $sql = "
            SELECT rd.*, 
                   c.case_number, c.exam_type, c.findings, c.impression, c.is_amended,
                   p.first_name, p.last_name, p.middle_name, p.patient_number, p.contact_number, p.email,
                   p.sex, (YEAR(CURDATE()) - YEAR(p.birthdate)) AS age,
                   pu.name as user_account_name,
                   b.name as branch_name,
                   u.name as resolver_name
            FROM result_disputes rd
            JOIN cases c ON rd.case_id = c.id
            JOIN patients p ON rd.patient_id = p.id
            LEFT JOIN users pu ON p.id = pu.patient_id AND pu.role = 'patient'
            LEFT JOIN branches b ON rd.branch_id = b.id
            LEFT JOIN users u ON rd.resolved_by = u.id
            WHERE 1=1
        ";
        $params = [];

        if ($branchId) {
            $sql .= " AND (rd.branch_id = ? OR rd.branch_id IS NULL)";
            $params[] = $branchId;
        }

        // ROLE GATEKEEPING:
        // RadTech sees ALL disputes for their branch so they can track escalated and verified statuses
        // Radiologist ONLY sees tickets explicitly escalated to radiologist (Escalated to Radiologist)
        if ($role === 'radiologist') {
            $sql .= " AND rd.assigned_role = 'radiologist'";
        }

        if ($status) {
            $sql .= " AND rd.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY rd.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Escalate ticket to Radiologist (Step 3: RadTech Escalation)
     */
    public function escalateToRadiologist($disputeId, $radtechNotes) {
        $stmt = $this->pdo->prepare("
            UPDATE result_disputes 
            SET status = 'Escalated to Radiologist', assigned_role = 'radiologist', radtech_notes = ?
            WHERE id = ?
        ");
        return $stmt->execute([$radtechNotes, $disputeId]);
    }

    /**
     * Update dispute status & resolution
     */
    public function updateDisputeStatus($disputeId, $status, $assignedRole = null, $resolutionNotes = null, $userId = null) {
        $sql = "UPDATE result_disputes SET status = ?";
        $params = [$status];

        if ($assignedRole !== null) {
            $sql .= ", assigned_role = ?";
            $params[] = $assignedRole;
        }

        if ($resolutionNotes !== null) {
            $sql .= ", resolution_notes = ?";
            $params[] = $resolutionNotes;
        }

        if ($userId !== null) {
            $sql .= ", resolved_by = ?, resolved_at = NOW()";
            $params[] = $userId;
        }

        $sql .= " WHERE id = ?";
        $params[] = $disputeId;

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Update active dispute status for a case
     */
    public function updateDisputeStatusForCase($caseId, $status, $assignedRole = null, $oldFindings = null, $oldImpression = null) {
        $stmt = $this->pdo->prepare("SELECT * FROM result_disputes WHERE case_id = ? AND status != 'Resolved' AND status != 'Rejected' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$caseId]);
        $active = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($active) {
            $cat = $active['dispute_category'] ?? '';
            $isBoth = ($cat === 'both_error');
            $demoFixed = (int)($active['demographics_fixed'] ?? 0);

            // If both_error and demographics not fixed yet, status remains Pending RadTech Review
            $newStatus = $status;
            if ($isBoth && !$demoFixed) {
                $newStatus = 'Pending RadTech Review';
            }

            $sql = "UPDATE result_disputes SET status = ?, radiologist_amended = 1";
            $params = [$newStatus];

            if ($assignedRole !== null) {
                $sql .= ", assigned_role = ?";
                $params[] = $assignedRole;
            }

            if ($oldFindings !== null) {
                $sql .= ", old_findings = ?";
                $params[] = $oldFindings;
            }
            
            if ($oldImpression !== null) {
                $sql .= ", old_impression = ?";
                $params[] = $oldImpression;
            }

            $sql .= " WHERE id = ?";
            $params[] = $active['id'];

            $stmtUpdate = $this->pdo->prepare($sql);
            return $stmtUpdate->execute($params);
        }
        return false;
    }
}
