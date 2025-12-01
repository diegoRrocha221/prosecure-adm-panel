<?php
/**
 * Database Session Handler
 */

class DatabaseSessionHandler implements SessionHandlerInterface
{
    private $pdo;
    private $table = 'Session';
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function open($savePath, $sessionName)
    {
        return true;
    }
    
    public function close()
    {
        return true;
    }
    
    public function read($id)
    {
        if (!$this->isValidSessionId($id)) {
            error_log("Invalid session ID attempted: " . $id);
            return "";
        }
        
        try {
            $stmt = $this->pdo->prepare(
                "SELECT Session_Data FROM {$this->table} 
                 WHERE Session_Id = ? 
                 AND Session_Expires > NOW()"
            );
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['Session_Data'] : "";
        } catch (PDOException $e) {
            error_log("Session read failed: " . $e->getMessage());
            return "";
        }
    }
    
    public function write($id, $data)
    {
        if (!$this->isValidSessionId($id)) {
            error_log("Invalid session ID attempted in write: " . $id);
            return false;
        }
        
        try {
            $expires = date('Y-m-d H:i:s', time() + 3600);
            
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} (Session_Id, Session_Expires, Session_Data) 
                 VALUES (?, ?, ?) 
                 ON DUPLICATE KEY UPDATE 
                 Session_Expires = VALUES(Session_Expires),
                 Session_Data = VALUES(Session_Data)"
            );
            
            return $stmt->execute([$id, $expires, $data]);
        } catch (PDOException $e) {
            error_log("Session write failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function destroy($id)
    {
        if (!$this->isValidSessionId($id)) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE Session_Id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Session destroy failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function gc($maxlifetime)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE Session_Expires < NOW()");
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Session GC failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function isValidSessionId($id)
    {
        return preg_match('/^[a-zA-Z0-9,-]{22,256}$/', $id) === 1;
    }
}
