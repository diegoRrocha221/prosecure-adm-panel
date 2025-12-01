<?php
/**
 * Admin Management Class
 */

class Admin {
    private $db;
    
    public function __construct(Database $database) {
        $this->db = $database;
    }
    
    public function authenticate($email, $password) {
        $sql = "SELECT * FROM admins WHERE email = ? LIMIT 1";
        $admin = $this->db->fetchOne($sql, [$email]);
        
        if ($admin && password_verify($password, $admin['password'])) {
            return $admin;
        }
        
        return false;
    }
    
    public function getAllAdmins() {
        $sql = "SELECT id, email, name, role FROM admins ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }
    
    public function getAdminById($id) {
        $sql = "SELECT id, email, name, role FROM admins WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function createAdmin($email, $name, $password, $role = 'admin') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO admins (email, name, password, role) VALUES (?, ?, ?, ?)";
        
        try {
            $this->db->execute($sql, [$email, $name, $hashedPassword, $role]);
            return ['success' => true, 'message' => 'Admin created successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error creating admin: ' . $e->getMessage()];
        }
    }
    
    public function updateAdmin($id, $email, $name, $role, $password = null) {
        if ($password) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE admins SET email = ?, name = ?, role = ?, password = ? WHERE id = ?";
            $params = [$email, $name, $role, $hashedPassword, $id];
        } else {
            $sql = "UPDATE admins SET email = ?, name = ?, role = ? WHERE id = ?";
            $params = [$email, $name, $role, $id];
        }
        
        try {
            $this->db->execute($sql, $params);
            return ['success' => true, 'message' => 'Admin updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating admin: ' . $e->getMessage()];
        }
    }
    
    public function deleteAdmin($id) {
        $sql = "DELETE FROM admins WHERE id = ?";
        
        try {
            $this->db->execute($sql, [$id]);
            return ['success' => true, 'message' => 'Admin deleted successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error deleting admin: ' . $e->getMessage()];
        }
    }
}
