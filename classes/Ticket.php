<?php
class Ticket {
    private $db;
    
    public function __construct(Database $database) {
        $this->db = $database;
    }
    
    public function getAllTickets() {
        $sql = "SELECT ct.*, ma.name, ma.lname, ma.email, ma.username, ma.phone_number
                FROM customer_tickets ct
                LEFT JOIN master_accounts ma ON ct.reference_uuid = ma.reference_uuid
                ORDER BY ct.created_at DESC";
        return $this->db->fetchAll($sql);
    }
    
    public function getTicketById($id) {
        $sql = "SELECT ct.*, ma.*
                FROM customer_tickets ct
                LEFT JOIN master_accounts ma ON ct.reference_uuid = ma.reference_uuid
                WHERE ct.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function updateTicketStatus($id, $status) {
        $sql = "UPDATE customer_tickets SET status = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $this->db->execute($sql, [$status, $id]);
            return ['success' => true, 'message' => 'Ticket status updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating ticket: ' . $e->getMessage()];
        }
    }
    
    public function getTicketStatusText($status) {
        $statuses = [
            0 => 'New',
            1 => 'In Progress',
            2 => 'Resolved'
        ];
        return $statuses[$status] ?? 'Unknown';
    }
    
    public function getTicketStatusBadge($status) {
        $badges = [
            0 => 'bg-danger',
            1 => 'bg-warning',
            2 => 'bg-success'
        ];
        $class = $badges[$status] ?? 'bg-secondary';
        $text = $this->getTicketStatusText($status);
        return '<span class="badge ' . $class . '">' . $text . '</span>';
    }
    
    public function getNewTicketsCount() {
        $sql = "SELECT COUNT(*) as count FROM customer_tickets WHERE status = 0";
        $result = $this->db->fetchOne($sql);
        return $result['count'];
    }
}