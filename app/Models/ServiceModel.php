<?php
/**
 * ServiceModel.php
 * Handles database interactions for X-Ray services and pricing.
 */

class ServiceModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all active services ordered by category and exam_type.
     */
    public function getActiveServices() {
        $stmt = $this->pdo->prepare("SELECT * FROM xray_services WHERE status = 'active' ORDER BY category ASC, exam_type ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get all services (active and inactive) ordered by category and exam_type.
     */
    public function getAllServices() {
        $stmt = $this->pdo->prepare("SELECT * FROM xray_services ORDER BY category ASC, exam_type ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get single service by ID.
     */
    public function getServiceById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM xray_services WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get all unique categories.
     */
    public function getCategories() {
        $stmt = $this->pdo->prepare("SELECT DISTINCT category FROM xray_services ORDER BY category ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Create a new service.
     */
    public function createService($category, $examType, $price, $isPhilhealthCovered = 0, $philhealthDiscount = 0.00, $status = 'active') {
        $stmt = $this->pdo->prepare("INSERT INTO xray_services (category, exam_type, price, is_philhealth_covered, philhealth_discount, status) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$category, $examType, $price, (int)$isPhilhealthCovered, (float)$philhealthDiscount, $status]);
    }

    /**
     * Update an existing service.
     */
    public function updateService($id, $category, $examType, $price, $isPhilhealthCovered = 0, $philhealthDiscount = 0.00, $status = 'active') {
        $stmt = $this->pdo->prepare("UPDATE xray_services SET category = ?, exam_type = ?, price = ?, is_philhealth_covered = ?, philhealth_discount = ?, status = ? WHERE id = ?");
        return $stmt->execute([$category, $examType, $price, (int)$isPhilhealthCovered, (float)$philhealthDiscount, $status, $id]);
    }

    /**
     * Toggle or update service status ('active' / 'inactive').
     */
    public function updateServiceStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE xray_services SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Delete a service permanently.
     */
    public function deleteService($id) {
        $stmt = $this->pdo->prepare("DELETE FROM xray_services WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
