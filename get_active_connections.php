<?php
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'includes/session.php';

requireLogin();

header('Content-Type: application/json');

$masterReference = $_GET['master_reference'] ?? '';

if (empty($masterReference)) {
    echo json_encode(['success' => false, 'message' => 'Master reference is required']);
    exit;
}

try {
    $db = new Database();
    $userManager = new User($db);
    
    $connections = $userManager->getActiveConnections($masterReference);
    
    echo json_encode([
        'success' => true,
        'connections' => $connections,
        'count' => count($connections),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    error_log("Error in get_active_connections.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}