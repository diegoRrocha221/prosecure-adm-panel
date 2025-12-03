<?php
class Blog {
    private $db;
    
    public function __construct(Database $database) {
        $this->db = $database;
    }
    
    public function getAllPosts() {
        $sql = "SELECT p.*, bf.filter as filter_name 
                FROM posts p
                LEFT JOIN blog_filter bf ON p.filter = bf.uuid
                ORDER BY p.created_at DESC";
        return $this->db->fetchAll($sql);
    }
    
    public function getPostById($id) {
        $sql = "SELECT p.*, bf.filter as filter_name 
                FROM posts p
                LEFT JOIN blog_filter bf ON p.filter = bf.uuid
                WHERE p.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function createPost($data, $mediaFile = null) {
        $mediaName = null;
        
        if ($mediaFile && $mediaFile['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handleMediaUpload($mediaFile);
            if (!$uploadResult['success']) {
                return $uploadResult;
            }
            $mediaName = $uploadResult['filename'];
        }
        
        $uuid = $this->generateUuid();
        
        $sql = "INSERT INTO posts (title, subtitle, introduction, body, summary, media, uuid, filter, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        try {
            $this->db->execute($sql, [
                $data['title'] ?? '',
                $data['subtitle'] ?? '',
                $data['introduction'] ?? '',
                $data['body'] ?? '',
                $data['summary'] ?? '',
                $mediaName,
                $uuid,
                $data['filter']
            ]);
            
            return ['success' => true, 'message' => 'Post created successfully'];
        } catch (Exception $e) {
            if ($mediaName) {
                $this->cleanupMedia($mediaName);
            }
            return ['success' => false, 'message' => 'Error creating post: ' . $e->getMessage()];
        }
    }
    
    public function updatePost($id, $data, $mediaFile = null) {
        $post = $this->getPostById($id);
        if (!$post) {
            return ['success' => false, 'message' => 'Post not found'];
        }
        
        $mediaName = $post['media'];
        
        if ($mediaFile && $mediaFile['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->handleMediaUpload($mediaFile);
            if (!$uploadResult['success']) {
                return $uploadResult;
            }
            
            if ($mediaName) {
                $this->cleanupMedia($mediaName);
            }
            
            $mediaName = $uploadResult['filename'];
        }
        
        $sql = "UPDATE posts 
                SET title = ?, subtitle = ?, introduction = ?, body = ?, summary = ?, media = ?, filter = ?
                WHERE id = ?";
        
        try {
            $this->db->execute($sql, [
                $data['title'] ?? '',
                $data['subtitle'] ?? '',
                $data['introduction'] ?? '',
                $data['body'] ?? '',
                $data['summary'] ?? '',
                $mediaName,
                $data['filter'],
                $id
            ]);
            
            return ['success' => true, 'message' => 'Post updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating post: ' . $e->getMessage()];
        }
    }
    
    public function deletePost($id) {
        $post = $this->getPostById($id);
        if (!$post) {
            return ['success' => false, 'message' => 'Post not found'];
        }
        
        $sql = "DELETE FROM posts WHERE id = ?";
        
        try {
            $this->db->execute($sql, [$id]);
            
            if ($post['media']) {
                $this->cleanupMedia($post['media']);
            }
            
            return ['success' => true, 'message' => 'Post deleted successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error deleting post: ' . $e->getMessage()];
        }
    }
    
    private function handleMediaUpload($file) {
        $allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/mpeg', 'video/quicktime', 'video/webm'
        ];
        $maxSize = 50 * 1024 * 1024;
        
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type'];
        }
        
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File size too large. Maximum 50MB'];
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('media_') . '.' . $extension;
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
            return ['success' => false, 'message' => 'Failed to save uploaded file'];
        }
        
        $servers = Env::getRemoteServers('blog');
        
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
    
    private function cleanupMedia($filename) {
        if (empty($filename)) {
            return;
        }
        
        $servers = Env::getRemoteServers('blog');
        
        foreach ($servers as $server) {
            $remotePath = $server['path'] . $filename;
            $this->sshRemoveFile($server['host'], $server['user'], $server['pass'], $remotePath);
        }
    }
    
    private function sshRemoveFile($host, $user, $password, $remotePath) {
        $command = sprintf(
            'sshpass -p %s ssh -o StrictHostKeyChecking=no %s@%s "rm -f %s"',
            escapeshellarg($password),
            escapeshellarg($user),
            escapeshellarg($host),
            escapeshellarg($remotePath)
        );
        exec($command);
    }
    
    private function generateUuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    public function getMediaUrl($filename) {
        if (empty($filename)) {
            return '';
        }
        return 'https://prosecurelsp.com/admins/dashboard/dashboard/pages/blog/midia/' . $filename;
    }
}