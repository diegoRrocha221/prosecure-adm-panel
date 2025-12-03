<?php
/**
 * Plan Management Class
 */

class Plan {
    private $db;
    
    public function __construct(Database $database) {
        $this->db = $database;
    }
    
    public function getAllPlans() {
        $sql = "SELECT id, image, name, description, price, rules 
                FROM plans 
                WHERE deleted_at IS NULL 
                ORDER BY id ASC";
        return $this->db->fetchAll($sql);
    }
    
    public function getPlanById($id) {
        $sql = "SELECT * FROM plans WHERE id = ? AND deleted_at IS NULL";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function createPlan($data, $imageFile = null) {
        $imageName = null;
        
        if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handleImageUpload($imageFile);
            if (!$uploadResult['success']) {
                return $uploadResult;
            }
            $imageName = $uploadResult['filename'];
        }
        
        $rules = $this->formatRules($data['rules'] ?? []);
        
        $sql = "INSERT INTO plans (image, name, description, price, rules) 
                VALUES (?, ?, ?, ?, ?)";
        
        try {
            $this->db->execute($sql, [
                $imageName,
                $data['name'],
                $data['description'],
                $data['price'],
                $rules
            ]);
            
            return ['success' => true, 'message' => 'Plan created successfully'];
        } catch (Exception $e) {
            if ($imageName) {
                $this->cleanupImage($imageName);
            }
            return ['success' => false, 'message' => 'Error creating plan: ' . $e->getMessage()];
        }
    }
    
    public function updatePlan($id, $data, $imageFile = null) {
        $plan = $this->getPlanById($id);
        if (!$plan) {
            return ['success' => false, 'message' => 'Plan not found'];
        }
        
        $imageName = $plan['image'];
        
        if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handleImageUpload($imageFile);
            if (!$uploadResult['success']) {
                return $uploadResult;
            }
            
            if ($imageName) {
                $this->cleanupImage($imageName);
            }
            
            $imageName = $uploadResult['filename'];
        }
        
        $rules = $this->formatRules($data['rules'] ?? []);
        
        $sql = "UPDATE plans 
                SET image = ?, name = ?, description = ?, price = ?, rules = ?
                WHERE id = ?";
        
        try {
            $this->db->execute($sql, [
                $imageName,
                $data['name'],
                $data['description'],
                $data['price'],
                $rules,
                $id
            ]);
            
            return ['success' => true, 'message' => 'Plan updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating plan: ' . $e->getMessage()];
        }
    }
    
    private function handleImageUpload($file) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid image type. Only JPG, PNG, GIF and WEBP allowed'];
        }
        
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'Image size too large. Maximum 5MB allowed'];
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('plan_') . '.' . $extension;
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
            return ['success' => false, 'message' => 'Failed to save uploaded file'];
        }
        
        $servers = Env::getRemoteServers();
        
        foreach ($servers as $server) {
            $remotePath = $server['path'] . $filename;
            $scpResult = $this->scpUpload($tempPath, $server['host'], $server['user'], $server['pass'], $remotePath);
            if (!$scpResult) {
                error_log("Failed to upload to {$server['host']}");
            }
        }
        
        unlink($tempPath);
        
        return ['success' => true, 'filename' => $filename];
    }
    
    private function scpUpload($localFile, $host, $user, $password, $remotePath) {
        if (!extension_loaded('ssh2')) {
            error_log("SSH2 extension not loaded, attempting rsync fallback");
            return $this->rsyncUpload($localFile, $host, $user, $password, $remotePath);
        }
        
        $connection = ssh2_connect($host, 22);
        if (!$connection) {
            error_log("Could not connect to $host");
            return false;
        }
        
        if (!ssh2_auth_password($connection, $user, $password)) {
            error_log("Authentication failed for $host");
            return false;
        }
        
        $result = ssh2_scp_send($connection, $localFile, $remotePath, 0644);
        
        return $result;
    }
    
    private function rsyncUpload($localFile, $host, $user, $password, $remotePath) {
        $command = sprintf(
            'sshpass -p %s scp -o StrictHostKeyChecking=no %s %s@%s:%s',
            escapeshellarg($password),
            escapeshellarg($localFile),
            escapeshellarg($user),
            escapeshellarg($host),
            escapeshellarg($remotePath)
        );
        
        exec($command . ' 2>&1', $output, $returnVar);
        
        return $returnVar === 0;
    }
    
    private function cleanupImage($filename) {
        if (empty($filename)) {
            return;
        }
        
        $servers = Env::getRemoteServers();
        
        foreach ($servers as $server) {
            $remotePath = $server['path'] . $filename;
            $this->sshRemoveFile($server['host'], $server['user'], $server['pass'], $remotePath);
        }
    }
    
    private function sshRemoveFile($host, $user, $password, $remotePath) {
        if (!extension_loaded('ssh2')) {
            $command = sprintf(
                'sshpass -p %s ssh -o StrictHostKeyChecking=no %s@%s "rm -f %s"',
                escapeshellarg($password),
                escapeshellarg($user),
                escapeshellarg($host),
                escapeshellarg($remotePath)
            );
            exec($command);
            return;
        }
        
        $connection = ssh2_connect($host, 22);
        if (!$connection) {
            return;
        }
        
        if (!ssh2_auth_password($connection, $user, $password)) {
            return;
        }
        
        ssh2_exec($connection, "rm -f " . escapeshellarg($remotePath));
    }
    
    private function formatRules($rules) {
        if (empty($rules) || !is_array($rules)) {
            return null;
        }
        
        $formattedRules = [];
        foreach ($rules as $rule) {
            if (!empty($rule['qtd']) && !empty($rule['percent'])) {
                $formattedRules[] = [
                    'qtd' => (string)$rule['qtd'],
                    'percent' => (string)$rule['percent']
                ];
            }
        }
        
        return empty($formattedRules) ? null : json_encode($formattedRules);
    }
}