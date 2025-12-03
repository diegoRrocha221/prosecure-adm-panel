<?php
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/SystemMonitor.php';
require_once '../includes/session.php';

requireLogin();

header('Content-Type: application/json');

$serverKey = $_GET['server'] ?? '';

if (empty($serverKey)) {
    echo json_encode(['success' => false, 'message' => 'Server key required']);
    exit;
}

try {
    $monitor = new SystemMonitor();
    $server = $monitor->getServer($serverKey);
    
    if (!$server) {
        echo json_encode(['success' => false, 'message' => 'Server not found']);
        exit;
    }
    
    $configs = $server['config_paths'] ?? [];
    
    echo json_encode([
        'success' => true,
        'configs' => $configs
    ]);
} catch (Exception $e) {
    error_log("Error in sysadmin_list_configs.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error listing configs'
    ]);
}