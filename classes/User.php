<?php
/**
 * User Management Class
 */

class User {
    private $db;
    
    public function __construct(Database $database) {
        $this->db = $database;
    }
    
    public function getUsers($filters = []) {
        $sql = "SELECT u.*, p.name as plan_name, 
                CASE WHEN u.is_master = 1 THEN 'Master' ELSE 'Child' END as account_type
                FROM users u
                LEFT JOIN plans p ON u.plan_id = p.id
                WHERE 1=1";
        
        $params = [];
        
        // Search by name or email
        if (!empty($filters['search'])) {
            $sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Filter by date range
        if (!empty($filters['date_from'])) {
            $sql .= " AND u.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND u.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        // Filter by trial status
        if (isset($filters['is_trial']) && $filters['is_trial'] !== '') {
            $masterRefs = $this->getMasterReferencesByTrial($filters['is_trial']);
            if (!empty($masterRefs)) {
                $placeholders = str_repeat('?,', count($masterRefs) - 1) . '?';
                $sql .= " AND u.master_reference IN ($placeholders)";
                $params = array_merge($params, $masterRefs);
            } else {
                // No masters found with this trial status
                return [];
            }
        }
        
        // Filter only master accounts
        if (!empty($filters['masters_only'])) {
            $sql .= " AND u.is_master = 1";
        }
        
        $sql .= " ORDER BY u.created_at DESC";
        
        // Pagination
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
            
            if (!empty($filters['offset'])) {
                $sql .= " OFFSET ?";
                $params[] = (int)$filters['offset'];
            }
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function countUsers($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM users u WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND u.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND u.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (isset($filters['is_trial']) && $filters['is_trial'] !== '') {
            $masterRefs = $this->getMasterReferencesByTrial($filters['is_trial']);
            if (!empty($masterRefs)) {
                $placeholders = str_repeat('?,', count($masterRefs) - 1) . '?';
                $sql .= " AND u.master_reference IN ($placeholders)";
                $params = array_merge($params, $masterRefs);
            } else {
                return 0;
            }
        }
        
        if (!empty($filters['masters_only'])) {
            $sql .= " AND u.is_master = 1";
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['total'];
    }
    
    private function getMasterReferencesByTrial($isTrial) {
        $sql = "SELECT reference_uuid FROM master_accounts WHERE is_trial = ?";
        $results = $this->db->fetchAll($sql, [$isTrial]);
        return array_column($results, 'reference_uuid');
    }
    
    public function getUserById($id) {
        $sql = "SELECT u.*, p.name as plan_name 
                FROM users u
                LEFT JOIN plans p ON u.plan_id = p.id
                WHERE u.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function getMasterAccountByReference($reference) {
        $sql = "SELECT * FROM master_accounts WHERE reference_uuid = ?";
        return $this->db->fetchOne($sql, [$reference]);
    }
    
    public function getChildUsersByMaster($masterReference) {
        $sql = "SELECT u.*, p.name as plan_name 
                FROM users u
                LEFT JOIN plans p ON u.plan_id = p.id
                WHERE u.master_reference = ? AND u.is_master = 0
                ORDER BY u.created_at DESC";
        return $this->db->fetchAll($sql, [$masterReference]);
    }
    
    public function getInvoicesByMaster($masterReference) {
        $sql = "SELECT * FROM invoices 
                WHERE master_reference = ? AND deleted_at IS NULL
                ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$masterReference]);
    }
    
    public function getBillingInfo($masterReference) {
        $sql = "SELECT * FROM billing_infos WHERE master_reference = ?";
        return $this->db->fetchOne($sql, [$masterReference]);
    }
    
    public function getSubscriptionInfo($masterReference) {
        $sql = "SELECT * FROM subscriptions WHERE master_reference = ? ORDER BY created_at DESC LIMIT 1";
        return $this->db->fetchOne($sql, [$masterReference]);
    }
    
    public function getPlanById($planId) {
        $sql = "SELECT * FROM plans WHERE id = ?";
        return $this->db->fetchOne($sql, [$planId]);
    }
    
    public function updateMasterAccount($id, $data) {
        $sql = "UPDATE master_accounts SET 
                name = ?, lname = ?, email = ?, username = ?,
                state = ?, city = ?, street = ?, zip_code = ?, additional_info = ?,
                phone_number = ?, updated_at = NOW()
                WHERE id = ?";
        
        $params = [
            $data['name'],
            $data['lname'],
            $data['email'],
            $data['username'],
            $data['state'],
            $data['city'],
            $data['street'],
            $data['zip_code'],
            $data['additional_info'],
            $data['phone_number'],
            $id
        ];
        
        try {
            $this->db->execute($sql, $params);
            return ['success' => true, 'message' => 'Master account updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating master account: ' . $e->getMessage()];
        }
    }
}
