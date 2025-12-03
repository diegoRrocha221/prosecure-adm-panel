<?php
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/Ticket.php';
require_once 'includes/session.php';

requireLogin();

header('Content-Type: application/json');

try {
    $db = new Database();
    $ticketManager = new Ticket($db);
    
    $tickets = $ticketManager->getAllTickets();
    $newCount = $ticketManager->getNewTicketsCount();
    
    // Add status badge to each ticket
    foreach ($tickets as &$ticket) {
        $ticket['status_badge'] = $ticketManager->getTicketStatusBadge($ticket['status']);
    }
    
    echo json_encode([
        'success' => true,
        'tickets' => $tickets,
        'new_count' => $newCount,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    error_log("Error in get_tickets.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching tickets'
    ]);
}