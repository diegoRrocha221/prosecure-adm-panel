<?php
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/SystemMonitor.php';
require_once '../includes/session.php';

requireLogin();

header('Content-Type: application/json');

try {
    $monitor = new SystemMonitor();
    
    // Check if requesting single server or all
    $serverKey = $_GET['server'] ?? null;
    
    if ($serverKey) {
        // Single server request
        $status = $monitor->checkServiceStatus($serverKey);
        echo json_encode($status);
    } else {
        // All servers request (legacy)
        $status = $monitor->getAllServersStatus();
        echo json_encode([
            'success' => true,
            'data' => $status
        ]);
    }
} catch (Exception $e) {
    error_log("Error in sysadmin_status.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching server status'
    ]);
}