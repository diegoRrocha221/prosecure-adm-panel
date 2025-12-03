<?php
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/SystemMonitor.php';
require_once '../includes/session.php';

requireLogin();

header('Content-Type: application/json');

$serverKey = $_GET['server'] ?? '';
$configPath = $_GET['path'] ?? '';

if (empty($serverKey) || empty($configPath)) {
    echo json_encode(['success' => false, 'message' => 'Server and path required']);
    exit;
}

try {
    $monitor = new SystemMonitor();
    $result = $monitor->readConfigFile($serverKey, $configPath);
    
    echo json_encode($result);
} catch (Exception $e) {
    error_log("Error in sysadmin_read_config.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error reading config: ' . $e->getMessage()
    ]);
}