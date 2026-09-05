<?php
/**
 * CaseAmendmentModel.php
 * Handles database operations for case report & demographic amendments made by RadTech.
 */

class CaseAmendmentModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new case amendment record.
     *
     * @param array $data
     * @return int Inserted amendment ID
     */
    public function createAmendment(array $data): int
    {
        $sql = "
            INSERT INTO case_amendments (
                case_id,
                dispute_id,
                amended_by,
                amended_at,
                findings_before,
                findings_after,
                impression_before,
                impression_after,
                dicom_name_before,
                dicom_name_after,
                template_before,
                template_after,
                exam_type_before,
                exam_type_after,
                patient_name_before,
                patient_name_after,
                notes
            ) VALUES (
                :case_id,
                :dispute_id,
                :amended_by,
                NOW(),
                :findings_before,
                :findings_after,
                :impression_before,
                :impression_after,
                :dicom_name_before,
                :dicom_name_after,
                :template_before,
                :template_after,
                :exam_type_before,
                :exam_type_after,
                :patient_name_before,
                :patient_name_after,
                :notes
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':case_id'             => $data['case_id'],
            ':dispute_id'          => $data['dispute_id'] ?? null,
            ':amended_by'          => $data['amended_by'],
            ':findings_before'     => $data['findings_before'] ?? null,
            ':findings_after'      => $data['findings_after'] ?? null,
            ':impression_before'   => $data['impression_before'] ?? null,
            ':impression_after'    => $data['impression_after'] ?? null,
            ':dicom_name_before'   => $data['dicom_name_before'] ?? null,
            ':dicom_name_after'    => $data['dicom_name_after'] ?? null,
            ':template_before'     => $data['template_before'] ?? null,
            ':template_after'      => $data['template_after'] ?? null,
            ':exam_type_before'    => $data['exam_type_before'] ?? null,
            ':exam_type_after'     => $data['exam_type_after'] ?? null,
            ':patient_name_before' => $data['patient_name_before'] ?? null,
            ':patient_name_after'  => $data['patient_name_after'] ?? null,
            ':notes'               => $data['notes'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Fetch all amendments for a specific case with the staff user's name.
     *
     * @param int $caseId
     * @return array
     */
    public function getAmendmentsByCaseId(int $caseId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT ca.*, u.name as amended_by_name, u.role as amended_by_role
            FROM case_amendments ca
            LEFT JOIN users u ON ca.amended_by = u.id
            WHERE ca.case_id = ?
            ORDER BY ca.amended_at DESC
        ");
        $stmt->execute([$caseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch the most recent amendment for a specific case.
     *
     * @param int $caseId
     * @return array|null
     */
    public function getLatestAmendmentByCaseId(int $caseId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT ca.*, u.name as amended_by_name, u.role as amended_by_role
            FROM case_amendments ca
            LEFT JOIN users u ON ca.amended_by = u.id
            WHERE ca.case_id = ?
            ORDER BY ca.amended_at DESC
            LIMIT 1
        ");
        $stmt->execute([$caseId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Fetch an amendment by its ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getAmendmentById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT ca.*, u.name as amended_by_name
            FROM case_amendments ca
            LEFT JOIN users u ON ca.amended_by = u.id
            WHERE ca.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Log amendment shorthand.
     *
     * @param int $caseId
     * @param int|null $userId
     * @param array $data
     * @return int
     */
    public function logAmendment(int $caseId, ?int $userId, array $data): int
    {
        return $this->createAmendment([
            'case_id'          => $caseId,
            'dispute_id'       => $data['dispute_id'] ?? null,
            'amended_by'       => $userId ?? 1,
            'findings_after'   => $data['findings'] ?? null,
            'impression_after' => $data['impression'] ?? null,
            'notes'            => $data['notes'] ?? null,
        ]);
    }
}
