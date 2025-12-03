<?php
class Financial {
    private $db;
    private $testUsers = [
        'isabela', 'drocha15', 'ddddd@gmail.com', 'gamerdidi221@gmail.com',
        'fernandasoaressantos764@gmail.com', 'avcetjmgTEST', 'lb.97301@yahoo.com',
        'diego.adm.rocha4@gmail.com', 'Testcase', 'craig@gmail.com',
        'lyndaleagoodall@gmail.com', 'molina.fefe@gmail.com', 'jcosta@prosecure.com',
        'isabelabcosta@gmail.com', 'diego.ro.rocha.adm@gmail.com', 'scottjc16@gmail.com',
        'msimpson@michaelsimpsonphd.com'
    ];
    
    public function __construct(Database $database) {
        $this->db = $database;
    }
    
    public function getTestUsers() {
        return $this->testUsers;
    }
    
    private function getTestUsersPlaceholders() {
        return str_repeat('?,', count($this->testUsers) - 1) . '?';
    }
    
    public function getTotalUsers() {
        $placeholders = $this->getTestUsersPlaceholders();
        $sql = "SELECT COUNT(*) as total FROM master_accounts 
                WHERE username NOT IN ($placeholders) 
                AND email NOT IN ($placeholders)";
        
        $params = array_merge($this->testUsers, $this->testUsers);
        $result = $this->db->fetchOne($sql, $params);
        return $result['total'];
    }
    
    public function getTrialUsers() {
        $placeholders = $this->getTestUsersPlaceholders();
        $sql = "SELECT COUNT(*) as total FROM master_accounts 
                WHERE is_trial = 1 
                AND username NOT IN ($placeholders) 
                AND email NOT IN ($placeholders)";
        
        $params = array_merge($this->testUsers, $this->testUsers);
        $result = $this->db->fetchOne($sql, $params);
        return $result['total'];
    }
    
    public function getPaidUsers() {
        $placeholders = $this->getTestUsersPlaceholders();
        $sql = "SELECT COUNT(*) as total FROM master_accounts 
                WHERE is_trial = 0 
                AND username NOT IN ($placeholders) 
                AND email NOT IN ($placeholders)";
        
        $params = array_merge($this->testUsers, $this->testUsers);
        $result = $this->db->fetchOne($sql, $params);
        return $result['total'];
    }
    
    public function getCurrentMonthRevenue() {
        $placeholders = $this->getTestUsersPlaceholders();
        $sql = "SELECT SUM(total_price) as revenue FROM master_accounts 
                WHERE is_trial = 0 
                AND username NOT IN ($placeholders) 
                AND email NOT IN ($placeholders)
                AND MONTH(created_at) = MONTH(CURRENT_DATE())
                AND YEAR(created_at) = YEAR(CURRENT_DATE())";
        
        $params = array_merge($this->testUsers, $this->testUsers);
        $result = $this->db->fetchOne($sql, $params);
        return $result['revenue'] ?? 0;
    }
    
    public function getNextMonthProjection() {
        $placeholders = $this->getTestUsersPlaceholders();
        $sql = "SELECT SUM(total_price) as revenue FROM master_accounts 
                WHERE username NOT IN ($placeholders) 
                AND email NOT IN ($placeholders)
                AND renew_date >= DATE_FORMAT(DATE_ADD(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m-01')
                AND renew_date < DATE_FORMAT(DATE_ADD(CURRENT_DATE(), INTERVAL 2 MONTH), '%Y-%m-01')";
        
        $params = array_merge($this->testUsers, $this->testUsers);
        $result = $this->db->fetchOne($sql, $params);
        return $result['revenue'] ?? 0;
    }
    
    public function getTopSellingPlans() {
        $placeholders = $this->getTestUsersPlaceholders();
        
        $sql = "SELECT p.id, p.name, COUNT(u.id) as total_users
                FROM users u
                INNER JOIN plans p ON u.plan_id = p.id
                INNER JOIN master_accounts ma ON u.master_reference = ma.reference_uuid
                WHERE ma.username NOT IN ($placeholders) 
                AND ma.email NOT IN ($placeholders)
                GROUP BY p.id, p.name
                ORDER BY total_users DESC
                LIMIT 10";
        
        $params = array_merge($this->testUsers, $this->testUsers);
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getRevenueByPlan() {
        $placeholders = $this->getTestUsersPlaceholders();
        
        $sql = "SELECT p.name, SUM(ma.total_price) as revenue, COUNT(DISTINCT ma.id) as customers
                FROM master_accounts ma
                INNER JOIN plans p ON ma.plan = p.id
                WHERE ma.is_trial = 0
                AND ma.username NOT IN ($placeholders) 
                AND ma.email NOT IN ($placeholders)
                GROUP BY p.id, p.name
                ORDER BY revenue DESC";
        
        $params = array_merge($this->testUsers, $this->testUsers);
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getMonthlyGrowth() {
        $placeholders = $this->getTestUsersPlaceholders();
        
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as new_users,
                    SUM(total_price) as revenue
                FROM master_accounts
                WHERE username NOT IN ($placeholders) 
                AND email NOT IN ($placeholders)
                AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month DESC
                LIMIT 12";
        
        $params = array_merge($this->testUsers, $this->testUsers);
        return $this->db->fetchAll($sql, $params);
    }
}