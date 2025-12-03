<?php
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/PaloAlto.php';
require_once '../includes/session.php';

requireLogin();

header('Content-Type: application/json');

$fwKey = $_GET['fw'] ?? 'fw1';

try {
    $paloAlto = new PaloAlto();
    $result = $paloAlto->getIPUserMapping($fwKey);
    
    if ($result['success']) {
        $users = $paloAlto->parseUserMapping($result['data']);
        
        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
    } else {
        echo json_encode($result);
    }
} catch (Exception $e) {
    error_log("Error in paloalto_users.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching user mappings: ' . $e->getMessage()
    ]);
}