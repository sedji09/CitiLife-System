<?php

namespace App\Controllers\radtech;

use RecordRequestModel;
use CaseModel;

class ViewRecordRequestController
{
    /**
     * Handles backend logic for viewing detailed record requests.
     *
     * @return array
     */
    public function handle()
    {
        global $pdo;

        $recordModel = new RecordRequestModel($pdo);
        $caseModel = new CaseModel($pdo);

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header("Location: /" . PROJECT_DIR . "/index.php?role=radtech&page=record-request");
            exit;
        }

        $branchId = $_SESSION['branch_id'] ?? null;

        // 1. Fetch Request
        $request = $recordModel->getRequestById($id);

        // 2. Security Check: Ensure the request belongs to the RadTech's branch
        if (!$request || $request['branch_id'] != $branchId) {
            header("Location: /" . PROJECT_DIR . "/index.php?role=radtech&page=record-request");
            exit;
        }

        // 3. Prepare View Data
        $statusColors = [
            'Pending' => 'text-yellow-700 bg-yellow-50 border-yellow-200',
            'Approved' => 'text-green-700 bg-green-50 border-green-200',
            'Denied' => 'text-red-700 bg-red-50 border-red-200'
        ];
        $statusColorClass = $statusColors[$request['status']] ?? 'text-gray-700 bg-gray-50 border-gray-200';

        $caseDetails = null;
        if ($request['status'] === 'Approved') {
            $caseDetails = $caseModel->getCaseByNumber(trim($request['patient_no']));
        }

        return get_defined_vars();
    }
}
