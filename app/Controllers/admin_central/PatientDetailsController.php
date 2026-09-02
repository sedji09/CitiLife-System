<?php

namespace App\Controllers\admin_central;

class PatientDetailsController
{
    public function handle()
    {
        global $pdo;

        require_once basePath('app/Models/PatientModel.php');
        require_once basePath('app/Models/CaseModel.php');

        $patientModel = new \PatientModel($pdo);

        $patientId = (int)($_GET['id'] ?? 0);
        $patient = $patientModel->getPatientById($patientId);

        if (!$patient) {
            // Redirect back to patient records if patient not found
            $redirectBase = (strpos($_SERVER['HTTP_HOST'] ?? 'localhost', 'localhost') !== false) ? '/' . PROJECT_DIR : '';
            header("Location: {$redirectBase}/patient-records");
            exit;
        }

        // Fetch cases for this patient (most recent first)
        $stmtCases = $pdo->prepare("
            SELECT c.*, b.name AS branch_name
            FROM cases c
            LEFT JOIN branches b ON b.id = c.branch_id
            WHERE c.patient_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmtCases->execute([$patientId]);
        $patientCases = $stmtCases->fetchAll(\PDO::FETCH_ASSOC);

        // Latest case for quick reference
        $latestCase = !empty($patientCases) ? $patientCases[0] : null;

        // Simple stats matching the view's expected keys
        $stmtStats = $pdo->prepare("
            SELECT
                COUNT(*)                                        AS total_exams,
                COUNT(DISTINCT branch_id)                       AS branches_visited,
                MAX(created_at)                                 AS last_visit
            FROM cases
            WHERE patient_id = ?
        ");
        $stmtStats->execute([$patientId]);
        $patientStats = $stmtStats->fetch(\PDO::FETCH_ASSOC) ?: [
            'total_exams'      => 0,
            'branches_visited' => 0,
            'last_visit'       => null,
        ];

        return get_defined_vars();
    }
}
