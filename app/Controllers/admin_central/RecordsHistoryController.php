<?php

namespace App\Controllers\admin_central;

class RecordsHistoryController
{
    public function handle()
    {
        global $pdo;



        $caseModel = new \CaseModel($pdo);
        $patientModel = new \PatientModel($pdo);

        $patientNumber = $_GET['patient_number'] ?? '';
        $patientId = $_GET['id'] ?? null;
        $source = $_GET['source'] ?? 'profile';
        $patient = null;
        $history = [];

        // Pagination logic
        $itemsPerPage = 5;
        $currentPage = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
        $offset = ($currentPage - 1) * $itemsPerPage;
        $totalItems = 0;
        $totalPages = 1;

        if ($patientNumber) {
            // Get patient details by patient_number
            $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_number = ?");
            $stmt->execute([$patientNumber]);
            $patient = $stmt->fetch();
        } elseif ($patientId) {
            // Get patient details by id
            $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
            $stmt->execute([$patientId]);
            $patient = $stmt->fetch();
            if ($patient) {
                $patientNumber = $patient['patient_number'];
            }
        }

        if ($patient && $patientNumber) {
            $totalItems = $caseModel->countPatientHistory($patientNumber);
            $totalPages = max(1, ceil($totalItems / $itemsPerPage));

            // Ensure currentPage doesn't exceed totalPages
            if ($currentPage > $totalPages) {
                $currentPage = $totalPages;
                $offset = ($currentPage - 1) * $itemsPerPage;
            }

            $history = $caseModel->getPatientHistory($patientNumber, null, $itemsPerPage, $offset);
        }

        return get_defined_vars();
    }
}
