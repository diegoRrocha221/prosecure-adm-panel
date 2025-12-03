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
$service = $_POST['service'] ?? '';

if (empty($serverKey) || empty($service)) {
    echo json_encode(['success' => false, 'message' => 'Server and service required']);
    exit;
}

try {
    $monitor = new SystemMonitor();
    $result = $monitor->restartService($serverKey, $service);
    
    echo json_encode($result);
} catch (Exception $e) {
    error_log("Error in sysadmin_restart.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error restarting service: ' . $e->getMessage()
    ]);
}