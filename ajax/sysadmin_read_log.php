<?php
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/SystemMonitor.php';
require_once '../includes/session.php';

requireLogin();

header('Content-Type: application/json');

$serverKey = $_GET['server'] ?? '';
$logPath = $_GET['path'] ?? '';
$lines = (int)($_GET['lines'] ?? 100);

if (empty($serverKey) || empty($logPath)) {
    echo json_encode(['success' => false, 'message' => 'Server and path required']);
    exit;
}

try {
    $monitor = new SystemMonitor();
    $result = $monitor->readLogFile($serverKey, $logPath, $lines);
    
    echo json_encode($result);
} catch (Exception $e) {
    error_log("Error in sysadmin_read_log.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error reading log: ' . $e->getMessage()
    ]);
}