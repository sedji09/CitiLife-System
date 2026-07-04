<?php

/**
 * FeedbackModel.php
 * Handles database operations for patient ratings and feedback.
 */

class FeedbackModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Ensure the feedbacks table exists
     */
    public function ensureSchema()
    {
        $query = "
            CREATE TABLE IF NOT EXISTS `feedbacks` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `case_id` INT NULL,
                `patient_id` INT NULL,
                `user_id` INT NULL,
                `branch_id` INT NULL,
                `rating` INT NOT NULL DEFAULT 5,
                `comments` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`case_id`) REFERENCES `cases`(`id`) ON DELETE SET NULL,
                FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE SET NULL,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
                FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $this->pdo->exec($query);
    }

    /**
     * Submit new feedback
     */
    public function submitFeedback($caseId, $patientId, $userId, $branchId, $rating, $comments)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO feedbacks (case_id, patient_id, user_id, branch_id, rating, comments) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$caseId, $patientId, $userId, $branchId, $rating, $comments]);
    }

    /**
     * Get feedback history for a specific patient
     */
    public function getPatientFeedback($patientId)
    {
        $stmt = $this->pdo->prepare("
            SELECT f.*, b.name as branch_name, c.case_number, c.exam_type 
            FROM feedbacks f
            LEFT JOIN branches b ON f.branch_id = b.id
            LEFT JOIN cases c ON f.case_id = c.id
            WHERE f.patient_id = ?
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$patientId]);
        return $stmt->fetchAll();
    }

    /**
     * Get array of case IDs that the patient has already given feedback for
     */
    public function getPatientFeedbackCaseIds($patientId)
    {
        $stmt = $this->pdo->prepare("SELECT case_id FROM feedbacks WHERE patient_id = ? AND case_id IS NOT NULL");
        $stmt->execute([$patientId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get feedback for a specific branch
     */
    public function getBranchFeedback($branchId, $limit = null, $offset = 0)
    {
        $sql = "
            SELECT f.*, p.first_name, p.last_name, p.patient_number, b.name as branch_name, c.case_number, c.exam_type, u.avatar
            FROM feedbacks f
            LEFT JOIN patients p ON f.patient_id = p.id
            LEFT JOIN branches b ON f.branch_id = b.id
            LEFT JOIN cases c ON f.case_id = c.id
            LEFT JOIN users u ON f.user_id = u.id
            WHERE f.branch_id = ?
            ORDER BY f.created_at DESC
        ";
        
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$branchId]);
        return $stmt->fetchAll();
    }

    public function countBranchFeedback($branchId)
    {
        $stmt = $this->pdo->prepare("SELECT count(*) FROM feedbacks WHERE branch_id = ?");
        $stmt->execute([$branchId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get all feedback across all branches
     */
    public function getAllFeedback($limit = null, $offset = 0)
    {
        $sql = "
            SELECT f.*, p.first_name, p.last_name, p.patient_number, b.name as branch_name, c.case_number, c.exam_type, u.avatar
            FROM feedbacks f
            LEFT JOIN patients p ON f.patient_id = p.id
            LEFT JOIN branches b ON f.branch_id = b.id
            LEFT JOIN cases c ON f.case_id = c.id
            LEFT JOIN users u ON f.user_id = u.id
            ORDER BY f.created_at DESC
        ";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAllFeedback()
    {
        $stmt = $this->pdo->prepare("SELECT count(*) FROM feedbacks");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get dashboard summary stats for feedback
     */
    public function getFeedbackStats($branchId = null)
    {
        $params = [];
        $where = "";
        
        if ($branchId) {
            $where = "WHERE branch_id = ?";
            $params[] = $branchId;
        }

        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_feedback,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_stars,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_stars,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_stars,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_stars,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_stars
            FROM feedbacks
            $where
        ");
        $stmt->execute($params);
        return $stmt->fetch();
    }
}
