<?php
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/SystemMonitor.php';
require_once '../includes/session.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$serverKey = $_POST['server'] ?? '';
$configPath = $_POST['path'] ?? '';
$content = $_POST['content'] ?? '';

if (empty($serverKey) || empty($configPath)) {
    echo json_encode(['success' => false, 'message' => 'Server and path required']);
    exit;
}

try {
    $monitor = new SystemMonitor();
    $result = $monitor->writeConfigFile($serverKey, $configPath, $content);
    
    echo json_encode($result);
} catch (Exception $e) {
    error_log("Error in sysadmin_write_config.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error writing config: ' . $e->getMessage()
    ]);
}