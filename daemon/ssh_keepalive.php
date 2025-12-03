<?php
// Run this as: php daemon/ssh_keepalive.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/SSHConnectionPool.php';

// Set up daemon
declare(ticks = 1);
pcntl_signal(SIGTERM, "signalHandler");
pcntl_signal(SIGINT, "signalHandler");

$running = true;

function signalHandler($signal) {
    global $running;
    $running = false;
    echo "Received signal $signal, shutting down...\n";
}

// Load environment
Env::load(dirname(__DIR__) . '/.env');

$pool = SSHConnectionPool::getInstance();

// Servers to keep alive
$servers = [
    ['host' => '172.31.255.148', 'user' => 'root', 'pass' => Env::get('SSH_DEFAULT_PASSWORD')],
    ['host' => '172.31.255.66', 'user' => 'root', 'pass' => Env::get('SSH_DEFAULT_PASSWORD')],
    ['host' => '172.31.255.67', 'user' => 'root', 'pass' => Env::get('SSH_WEB2_PASSWORD')],
    ['host' => '172.31.255.50', 'user' => 'root', 'pass' => Env::get('SSH_DEFAULT_PASSWORD')],
    ['host' => '172.31.255.52', 'user' => 'root', 'pass' => Env::get('SSH_DEFAULT_PASSWORD')],
    ['host' => '172.31.255.26', 'user' => 'root', 'pass' => Env::get('SSH_DEFAULT_PASSWORD')],
    ['host' => '172.31.255.27', 'user' => 'root', 'pass' => Env::get('SSH_DEFAULT_PASSWORD')],
    ['host' => '172.31.255.28', 'user' => 'root', 'pass' => Env::get('SSH_DEFAULT_PASSWORD')],
    ['host' => '172.31.255.29', 'user' => 'root', 'pass' => Env::get('SSH_DEFAULT_PASSWORD')],
    ['host' => '172.31.255.30', 'user' => 'root', 'pass' => Env::get('SSH_DEFAULT_PASSWORD')],
];

echo "SSH KeepAlive Daemon started...\n";

while ($running) {
    // Establish/maintain connections
    foreach ($servers as $server) {
        $connection = $pool->getConnection($server['host'], $server['user'], $server['pass']);
        if ($connection) {
            echo "Connection active: {$server['user']}@{$server['host']}\n";
        } else {
            echo "Failed to connect: {$server['user']}@{$server['host']}\n";
        }
    }
    
    // Cleanup idle connections
    $pool->cleanupIdleConnections();
    
    // Sleep for 60 seconds
    sleep(60);
}

// Cleanup
echo "Closing all connections...\n";
$pool->closeAll();
echo "Daemon stopped.\n";