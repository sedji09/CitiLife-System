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
    public function createBranch($name, $address = null, $additionalAddress = null, $contact1 = null, $contact2 = null, $contact3 = null) {
        $stmt = $this->pdo->prepare("INSERT INTO branches (name, address, additional_address, contact_number_1, contact_number_2, contact_number_3) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$name, $address, $additionalAddress, $contact1, $contact2, $contact3]);
    }

    /**
     * Update an existing branch name.
     */
    public function updateBranch($id, $name, $address = null, $additionalAddress = null, $contact1 = null, $contact2 = null, $contact3 = null) {
        $stmt = $this->pdo->prepare("UPDATE branches SET name = ?, address = ?, additional_address = ?, contact_number_1 = ?, contact_number_2 = ?, contact_number_3 = ? WHERE id = ?");
        return $stmt->execute([$name, $address, $additionalAddress, $contact1, $contact2, $contact3, $id]);
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
        // Fetch directly from the database
        $stmt = $this->pdo->prepare("SELECT * FROM branches WHERE name LIKE ? LIMIT 1");
        $stmt->execute(['%' . trim($branchName) . '%']);
        $branch = $stmt->fetch();

        if ($branch) {
            $addr = trim($branch['address'] ?? '');
            $add_addr = trim($branch['additional_address'] ?? '');
            
            // Format address: Add additional address in parentheses if it exists
            $fullAddress = strtoupper($addr ?: $branch['name'] . ' BRANCH');
            if (!empty($add_addr)) {
                $fullAddress .= "\n(" . strtoupper($add_addr) . ")";
            }

            return [
                'address' => $fullAddress,
                'contact1' => !empty($branch['contact_number_1']) ? $branch['contact_number_1'] : '',
                'contact2' => !empty($branch['contact_number_2']) ? $branch['contact_number_2'] : '',
                'contact3' => !empty($branch['contact_number_3']) ? $branch['contact_number_3'] : ''
            ];
        }

        // Fallback if branch is somehow not found
        return [
            'address' => strtoupper($branchName) . " BRANCH", 
            'contact1' => "0900-000-0000", 
            'contact2' => "",
            'contact3' => ""
        ];
    }
}
