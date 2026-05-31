<?php
/**
 * BranchModel.php
 * Handles all database interactions related to branches.
 */

class BranchModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all branches.
     */
    public function getAllBranches() {
        $stmt = $this->pdo->prepare("SELECT * FROM branches ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get branch by ID.
     */
    /**
     * Get branch by ID.
     */
    public function getBranchById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM branches WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get branch by name and address.
     */
    public function getBranchByNameAndAddress($name, $address) {
        $stmt = $this->pdo->prepare("SELECT * FROM branches WHERE name = ? AND address = ? LIMIT 1");
        $stmt->execute([$name, $address]);
        return $stmt->fetch();
    }

    /**
     * Get branch by name.
     */
    public function getBranchByName($name) {
        $stmt = $this->pdo->prepare("SELECT * FROM branches WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        return $stmt->fetch();
    }

    /**
     * Get standardized display name for a branch (e.g., "Central Branch").
     */
    public function getBranchDisplayName($branchId) {
        if (!$branchId) return 'All Branches';
        
        $stmt = $this->pdo->prepare("SELECT name FROM branches WHERE id = ?");
        $stmt->execute([$branchId]);
        $name = $stmt->fetchColumn();

        if (!$name) return 'All Branches';

        // Strip existing " Branch" suffix and re-add it consistently
        $cleanName = preg_replace('/\s+Branch$/i', '', trim($name));
        return $cleanName . " Branch";
    }

    /**
     * Create a new branch.
     */
    public function createBranch($name, $address = null) {
        $stmt = $this->pdo->prepare("INSERT INTO branches (name, address) VALUES (?, ?)");
        return $stmt->execute([$name, $address]);
    }

    /**
     * Update an existing branch name.
     */
    public function updateBranch($id, $name, $address = null) {
        $stmt = $this->pdo->prepare("UPDATE branches SET name = ?, address = ? WHERE id = ?");
        return $stmt->execute([$name, $address, $id]);
    }

    /**
     * Update branch status (Active/Inactive).
     */
    public function updateBranchStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE branches SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Delete a branch.
     */
    public function deleteBranch($id) {
        $stmt = $this->pdo->prepare("DELETE FROM branches WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get branch metadata (address, contacts) for reports.
     */
    public function getBranchMetadata($branchName) {
        $branchMapping = [
            'BONGABON' => ['address' => "L. DE LARA STREET, BONGABON NUEVA ECIJA\n(BESIDE BONGABON DISTRICT HOSPITAL)", 'contact1' => '0919-230-5384', 'contact2' => '0954-305-1164'],
            'GAPAN' => ['address' => "BGRY. BAYAHANIHAN, GAPAN CITY, NUEVA ECIJA\n(IN FRONT OF GAPAN DISTRICT HOSPITAL)", 'contact1' => '0933-866-6617', 'contact2' => '0926-048-7980'],
            'PEÑARANDA' => ['address' => "PEÑARANDA, NUEVA ECIJA", 'contact1' => '0919-234-5678', 'contact2' => '0954-234-5678'],
            'GENERAL TINIO' => ['address' => "GENERAL TINIO, NUEVA ECIJA", 'contact1' => '0919-345-6789', 'contact2' => '0954-345-6789'],
            'STO DOMINGO' => ['address' => "STO. DOMINGO, NUEVA ECIJA", 'contact1' => '0919-456-7890', 'contact2' => '0954-456-7890'],
            'SAN ANTONIO' => ['address' => "SAN ANTONIO, NUEVA ECIJA", 'contact1' => '0919-567-8901', 'contact2' => '0954-567-8901'],
            'PANTABANGAN' => ['address' => "PANTABANGAN, NUEVA ECIJA", 'contact1' => '0919-678-9012', 'contact2' => '0954-678-9012']
        ];

        $metadata = ['address' => strtoupper($branchName) . " BRANCH", 'contact1' => "0900-000-0000", 'contact2' => ""];
        foreach ($branchMapping as $key => $info) {
            if (stripos($branchName, $key) !== false || stripos($key, $branchName) !== false) {
                $metadata = $info;
                break;
            }
        }
        return $metadata;
    }
}
