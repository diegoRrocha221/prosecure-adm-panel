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
    $result = $paloAlto->getHighAvailability($fwKey);
    
    echo json_encode($result);
} catch (Exception $e) {
    error_log("Error in paloalto_ha.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching HA status: ' . $e->getMessage()
    ]);
}