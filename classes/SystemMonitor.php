<?php
class SystemMonitor {
    private $servers = [
        'lb-web' => [
            'ip' => '172.31.255.148',
            'name' => 'Load Balancer (Web)',
            'type' => 'loadbalancer',
            'services' => ['keepalived', 'haproxy'],
            'config_paths' => ['/etc/haproxy/haproxy.cfg', '/etc/keepalived/keepalived.conf'],
            'check_vip' => true
        ],
        'web1' => [
            'ip' => '172.31.255.66',
            'name' => 'Web Server 1',
            'type' => 'webserver',
            'services' => ['nginx', 'php-fpm', 'redis', 'prosecure-payment-api', 'prosecure-mfa-api'],
            'config_paths' => ['/etc/nginx/nginx.conf', '/etc/nginx/conf.d/'],
            'logs' => ['/var/log/nginx/prosecurelsp.com.error.log'],
            'check_vip' => true
        ],
        'web2' => [
            'ip' => '172.31.255.67',
            'name' => 'Web Server 2',
            'type' => 'webserver',
            'services' => ['nginx', 'php-fpm', 'redis', 'prosecure-payment-api', 'prosecure-mfa-api'],
            'config_paths' => ['/etc/nginx/nginx.conf', '/etc/nginx/conf.d/'],
            'logs' => ['/var/log/nginx/prosecurelsp.com.error.log'],
            'check_vip' => true,
            'use_alt_password' => true
        ],
        'radius1' => [
            'ip' => '172.31.255.50',
            'name' => 'RADIUS Server 1',
            'type' => 'radius',
            'services' => ['radiusd', 'userspadaemon', 'pagrpmonitor'],
            'check_vip' => true
        ],
        'radius2' => [
            'ip' => '172.31.255.52',
            'name' => 'RADIUS Server 2',
            'type' => 'radius',
            'services' => ['radiusd', 'userspadaemon', 'pagrpmonitor'],
            'check_vip' => true
        ],
        'lb-db1' => [
            'ip' => '172.31.255.26',
            'name' => 'Load Balancer (DB) 1',
            'type' => 'db-loadbalancer',
            'services' => ['keepalived', 'haproxy'],
            'check_vip' => true
        ],
        'lb-db2' => [
            'ip' => '172.31.255.27',
            'name' => 'Load Balancer (DB) 2',
            'type' => 'db-loadbalancer',
            'services' => ['keepalived', 'haproxy'],
            'check_vip' => true
        ],
        'db1' => [
            'ip' => '172.31.255.28',
            'name' => 'Database Server 1',
            'type' => 'database',
            'services' => ['mariadb'],
            'check_vip' => true,
            'check_galera' => true
        ],
        'db2' => [
            'ip' => '172.31.255.29',
            'name' => 'Database Server 2',
            'type' => 'database',
            'services' => ['mariadb'],
            'check_vip' => true,
            'check_galera' => true
        ],
        'db3' => [
            'ip' => '172.31.255.30',
            'name' => 'Database Server 3',
            'type' => 'database',
            'services' => ['mariadb'],
            'check_vip' => true,
            'check_galera' => true
        ]
    ];
    
    public function getServers() {
        return $this->servers;
    }
    
    public function getServer($key) {
        return $this->servers[$key] ?? null;
    }
    
    private function getServerPassword($server) {
        if (isset($server['use_alt_password']) && $server['use_alt_password']) {
            return Env::get('SSH_WEB2_PASSWORD');
        }
        return Env::get('SSH_DEFAULT_PASSWORD');
    }
    
    public function checkServiceStatus($serverKey) {
        $server = $this->getServer($serverKey);
        if (!$server) {
            return ['success' => false, 'message' => 'Server not found'];
        }
        
        $results = [];
        
        foreach ($server['services'] as $service) {
            $status = $this->executeSSH($server, "systemctl is-active $service");
            $results[$service] = [
                'status' => trim($status) === 'active' ? 'running' : 'stopped',
                'raw' => trim($status)
            ];
        }
        
        $selinux = $this->executeSSH($server, "getenforce");
        $results['selinux'] = trim($selinux);
        
        if (isset($server['check_vip']) && $server['check_vip']) {
            $vip = $this->checkVIPStatus($server);
            $results['vip_master'] = $vip;
        }
        
        if (isset($server['check_galera']) && $server['check_galera']) {
            $galera = $this->checkGaleraStatus($server);
            $results['galera'] = $galera;
        }
        
        return ['success' => true, 'data' => $results];
    }
    
    public function getAllServersStatus() {
        $status = [];
        
        foreach ($this->servers as $key => $server) {
            $status[$key] = $this->checkServiceStatus($key);
        }
        
        return $status;
    }
    
    private function checkVIPStatus($server) {
        $output = $this->executeSSH($server, "ip addr show | grep 'inet.*secondary' | wc -l");
        $count = intval(trim($output));
        
        if ($count > 0) {
            return 'MASTER';
        }
        
        $keepalivedState = $this->executeSSH($server, "systemctl is-active keepalived 2>/dev/null");
        if (trim($keepalivedState) === 'active') {
            $stateFile = $this->executeSSH($server, "cat /var/run/keepalived.state 2>/dev/null || echo 'UNKNOWN'");
            if (strpos($stateFile, 'MASTER') !== false) {
                return 'MASTER';
            }
        }
        
        return 'BACKUP';
    }
    
    private function checkGaleraStatus($server) {
        $mysqlUser = Env::get('MYSQL_ROOT_USER', 'root');
        $mysqlPass = Env::get('MYSQL_ROOT_PASSWORD');
        
        $clusterCmd = "mysql -u{$mysqlUser} -p'{$mysqlPass}' -e \"SHOW STATUS LIKE 'wsrep_cluster_status';\" 2>/dev/null | tail -n1 | awk '{print \$2}'";
        $clusterStatus = $this->executeSSH($server, $clusterCmd);
        
        $stateCmd = "mysql -u{$mysqlUser} -p'{$mysqlPass}' -e \"SHOW STATUS LIKE 'wsrep_local_state_comment';\" 2>/dev/null | tail -n1 | awk '{print \$2}'";
        $localState = $this->executeSSH($server, $stateCmd);
        
        $isPrimaryCmd = "mysql -u{$mysqlUser} -p'{$mysqlPass}' -e \"SHOW STATUS LIKE 'wsrep_cluster_status';\" 2>/dev/null | grep -c Primary";
        $isPrimary = $this->executeSSH($server, $isPrimaryCmd);
        
        return [
            'cluster_status' => trim($clusterStatus) ?: 'Unknown',
            'local_state' => trim($localState) ?: 'Unknown',
            'is_primary' => intval(trim($isPrimary)) > 0
        ];
    }
    
    public function readLogFile($serverKey, $logPath, $lines = 100) {
        $server = $this->getServer($serverKey);
        if (!$server) {
            return ['success' => false, 'message' => 'Server not found'];
        }
        
        $output = $this->executeSSH($server, "tail -n $lines $logPath 2>&1");
        
        return ['success' => true, 'content' => $output];
    }
    
    public function readConfigFile($serverKey, $configPath) {
        $server = $this->getServer($serverKey);
        if (!$server) {
            return ['success' => false, 'message' => 'Server not found'];
        }
        
        $output = $this->executeSSH($server, "cat $configPath 2>&1");
        
        return ['success' => true, 'content' => $output, 'path' => $configPath];
    }
    
    public function writeConfigFile($serverKey, $configPath, $content) {
        $server = $this->getServer($serverKey);
        if (!$server) {
            return ['success' => false, 'message' => 'Server not found'];
        }
        
        $backupCmd = "cp $configPath $configPath.backup." . date('YmdHis');
        $this->executeSSH($server, $backupCmd);
        
        $tempFile = '/tmp/config_' . uniqid();
        file_put_contents($tempFile, $content);
        
        $scpResult = $this->scpUpload($tempFile, $server, $configPath);
        unlink($tempFile);
        
        if ($scpResult) {
            return ['success' => true, 'message' => 'Configuration updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update configuration'];
        }
    }
    
    public function restartService($serverKey, $service) {
        $server = $this->getServer($serverKey);
        if (!$server) {
            return ['success' => false, 'message' => 'Server not found'];
        }
        
        $output = $this->executeSSH($server, "systemctl restart $service 2>&1");
        $status = $this->executeSSH($server, "systemctl is-active $service");
        
        return [
            'success' => trim($status) === 'active',
            'message' => trim($status) === 'active' ? 'Service restarted successfully' : 'Failed to restart service',
            'output' => $output
        ];
    }
    
    public function listDirectory($serverKey, $path) {
        $server = $this->getServer($serverKey);
        if (!$server) {
            return ['success' => false, 'message' => 'Server not found'];
        }
        
        $output = $this->executeSSH($server, "ls -lah $path 2>&1");
        
        return ['success' => true, 'content' => $output];
    }
    
    private function executeSSH($server, $command) {
        $password = $this->getServerPassword($server);
        $user = Env::get('SSH_DEFAULT_USER', 'root');
        
        $sshCommand = sprintf(
            'HOME=/var/lib/nginx /usr/bin/sshpass -p %s /usr/bin/ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/var/lib/nginx/.ssh/known_hosts -o ConnectTimeout=10 -o LogLevel=ERROR %s@%s %s 2>&1',
            escapeshellarg($password),
            escapeshellarg($user),
            escapeshellarg($server['ip']),
            escapeshellarg($command)
        );
        
        exec($sshCommand, $output, $returnVar);
        
        return implode("\n", $output);
    }
    
    private function scpUpload($localFile, $server, $remotePath) {
        $password = $this->getServerPassword($server);
        $user = Env::get('SSH_DEFAULT_USER', 'root');
        
        $command = sprintf(
            'HOME=/var/lib/nginx /usr/bin/sshpass -p %s /usr/bin/scp -o StrictHostKeyChecking=no -o UserKnownHostsFile=/var/lib/nginx/.ssh/known_hosts %s %s@%s:%s 2>&1',
            escapeshellarg($password),
            escapeshellarg($localFile),
            escapeshellarg($user),
            escapeshellarg($server['ip']),
            escapeshellarg($remotePath)
        );
        
        exec($command, $output, $returnVar);
        
        return $returnVar === 0;
    }
    
    public function getSystemResources($serverKey) {
        $server = $this->getServer($serverKey);
        if (!$server) {
            return ['success' => false, 'message' => 'Server not found'];
        }
        
        $cpu = $this->executeSSH($server, "top -bn1 | grep 'Cpu(s)' | awk '{print \$2}' | cut -d'%' -f1");
        $memory = $this->executeSSH($server, "free | grep Mem | awk '{printf \"%.2f\", \$3/\$2 * 100.0}'");
        $disk = $this->executeSSH($server, "df -h / | tail -1 | awk '{print \$5}' | sed 's/%//'");
        $uptime = $this->executeSSH($server, "uptime -p");
        $load = $this->executeSSH($server, "uptime | awk -F'load average:' '{print \$2}'");
        
        return [
            'success' => true,
            'cpu' => floatval(trim($cpu)),
            'memory' => floatval(trim($memory)),
            'disk' => intval(trim($disk)),
            'uptime' => trim($uptime),
            'load' => trim($load)
        ];
    }
    
    public function testConnection($serverKey) {
        $server = $this->getServer($serverKey);
        if (!$server) {
            return ['success' => false, 'message' => 'Server not found'];
        }
        
        $pingResult = exec("ping -c 1 -W 2 {$server['ip']} > /dev/null 2>&1; echo $?");
        $canPing = $pingResult === '0';
        
        $sshPort = @fsockopen($server['ip'], 22, $errno, $errstr, 5);
        $sshOpen = $sshPort !== false;
        if ($sshPort) {
            fclose($sshPort);
        }
        
        $authTest = $this->executeSSH($server, "echo 'OK'");
        $canAuth = trim($authTest) === 'OK';
        
        return [
            'success' => true,
            'can_ping' => $canPing,
            'ssh_port_open' => $sshOpen,
            'can_authenticate' => $canAuth,
            'details' => [
                'ping' => $canPing ? 'Success' : 'Failed',
                'ssh_port' => $sshOpen ? 'Open' : 'Closed',
                'auth' => $canAuth ? 'Success' : 'Failed - Check password'
            ]
        ];
    }
}