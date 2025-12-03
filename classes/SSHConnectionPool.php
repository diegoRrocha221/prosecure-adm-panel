<?php
class SSHConnectionPool {
    private static $instance = null;
    private $connections = [];
    private $lastUsed = [];
    private $maxIdleTime = 300; // 5 minutes
    
    private function __construct() {
        // Private constructor for singleton
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection($host, $user, $password) {
        $key = "{$user}@{$host}";
        
        // Check if we have an active connection
        if (isset($this->connections[$key])) {
            // Test if connection is still alive
            if ($this->testConnection($key)) {
                $this->lastUsed[$key] = time();
                return $this->connections[$key];
            } else {
                // Connection is dead, remove it
                $this->closeConnection($key);
            }
        }
        
        // Create new connection
        return $this->createConnection($host, $user, $password);
    }
    
    private function createConnection($host, $user, $password) {
        $key = "{$user}@{$host}";
        
        // Use SSH ControlMaster for persistent connections
        $socketPath = "/var/run/prosecure-ssh/{$key}";
        
        // Establish master connection if not exists
        $checkCmd = sprintf(
            'HOME=/var/lib/nginx /usr/bin/ssh -O check -S %s %s@%s 2>&1',
            escapeshellarg($socketPath),
            escapeshellarg($user),
            escapeshellarg($host)
        );
        
        exec($checkCmd, $output, $returnVar);
        
        if ($returnVar !== 0) {
            // Master not running, start it
            $masterCmd = sprintf(
                'HOME=/var/lib/nginx /usr/bin/sshpass -p %s /usr/bin/ssh -fNM -S %s -o ControlPersist=10m -o ServerAliveInterval=30 -o ServerAliveCountMax=3 -o StrictHostKeyChecking=no -o UserKnownHostsFile=/var/lib/nginx/.ssh/known_hosts %s@%s 2>&1',
                escapeshellarg($password),
                escapeshellarg($socketPath),
                escapeshellarg($user),
                escapeshellarg($host)
            );
            
            exec($masterCmd, $masterOutput, $masterReturn);
            
            if ($masterReturn !== 0) {
                error_log("Failed to create SSH master connection to {$host}: " . implode("\n", $masterOutput));
                return null;
            }
            
            // Give it a moment to establish
            usleep(100000); // 100ms
        }
        
        $this->connections[$key] = $socketPath;
        $this->lastUsed[$key] = time();
        
        return $socketPath;
    }
    
    private function testConnection($key) {
        if (!isset($this->connections[$key])) {
            return false;
        }
        
        $socketPath = $this->connections[$key];
        
        // Parse key to get user and host
        list($user, $host) = explode('@', $key);
        
        $checkCmd = sprintf(
            'HOME=/var/lib/nginx /usr/bin/ssh -O check -S %s %s@%s 2>&1',
            escapeshellarg($socketPath),
            escapeshellarg($user),
            escapeshellarg($host)
        );
        
        exec($checkCmd, $output, $returnVar);
        
        return $returnVar === 0;
    }
    
    private function closeConnection($key) {
        if (!isset($this->connections[$key])) {
            return;
        }
        
        $socketPath = $this->connections[$key];
        
        // Parse key to get user and host
        list($user, $host) = explode('@', $key);
        
        $stopCmd = sprintf(
            'HOME=/var/lib/nginx /usr/bin/ssh -O stop -S %s %s@%s 2>&1',
            escapeshellarg($socketPath),
            escapeshellarg($user),
            escapeshellarg($host)
        );
        
        exec($stopCmd);
        
        unset($this->connections[$key]);
        unset($this->lastUsed[$key]);
    }
    
    public function cleanupIdleConnections() {
        $now = time();
        foreach ($this->lastUsed as $key => $lastTime) {
            if (($now - $lastTime) > $this->maxIdleTime) {
                $this->closeConnection($key);
            }
        }
    }
    
    public function closeAll() {
        foreach (array_keys($this->connections) as $key) {
            $this->closeConnection($key);
        }
    }
}