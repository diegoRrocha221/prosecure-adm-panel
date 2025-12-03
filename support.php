<?php
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/Ticket.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$ticketManager = new Ticket($db);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $result = $ticketManager->updateTicketStatus($_POST['ticket_id'], $_POST['status']);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'danger';
    }
}

$tickets = $ticketManager->getAllTickets();
$newCount = $ticketManager->getNewTicketsCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - ProSecure Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-page="support">
    <?php include 'views/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'views/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="main-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>
                            <i class="fas fa-headset me-2"></i>Support Tickets
                            <?php if ($newCount > 0): ?>
                                <span class="badge bg-danger"><?php echo $newCount; ?> New</span>
                            <?php endif; ?>
                        </h2>
                        <div>
                            <small class="text-muted" id="last-update"></small>
                        </div>
                    </div>
                    
                    <?php if ($message): ?>
                        <?php echo alert($message, $messageType); ?>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive" id="tickets-container">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Ticket #</th>
                                            <th>Customer</th>
                                            <th>Email</th>
                                            <th>Title</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($tickets)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <div class="empty-state">
                                                        <i class="fas fa-inbox"></i>
                                                        <p class="mb-0">No support tickets found.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($tickets as $ticket): ?>
                                                <tr style="cursor: pointer;" data-href="ticket_details.php?id=<?php echo $ticket['id']; ?>">
                                                    <td><strong><?php echo htmlspecialchars($ticket['ticket_number'] ?? 'N/A'); ?></strong></td>
                                                    <td>
                                                        <?php 
                                                        if ($ticket['name']) {
                                                            echo htmlspecialchars($ticket['name'] . ' ' . $ticket['lname']);
                                                        } else {
                                                            echo '<span class="text-muted">Unknown</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($ticket['email'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                                                    <td><?php echo $ticketManager->getTicketStatusBadge($ticket['status']); ?></td>
                                                    <td><?php echo formatDateTime($ticket['created_at']); ?></td>
                                                    <td>
                                                        <a href="ticket_details.php?id=<?php echo $ticket['id']; ?>" 
                                                           class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/navigation.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        var refreshInterval;
        
        function loadTickets() {
            $.ajax({
                url: 'get_tickets.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        updateTicketsTable(response.tickets);
                        updateNewCount(response.new_count);
                        $('#last-update').text('Last update: ' + new Date().toLocaleTimeString());
                    }
                },
                error: function() {
                    console.error('Failed to fetch tickets');
                }
            });
        }
        
        function updateTicketsTable(tickets) {
            var tbody = $('#tickets-container tbody');
            
            if (tickets.length === 0) {
                tbody.html('<tr><td colspan="7" class="text-center py-4"><div class="empty-state"><i class="fas fa-inbox"></i><p class="mb-0">No support tickets found.</p></div></td></tr>');
                return;
            }
            
            var html = '';
            tickets.forEach(function(ticket) {
                var customerName = ticket.name ? escapeHtml(ticket.name + ' ' + ticket.lname) : '<span class="text-muted">Unknown</span>';
                var ticketNumber = ticket.ticket_number || 'N/A';
                
                html += '<tr style="cursor: pointer;" data-href="ticket_details.php?id=' + ticket.id + '">' +
                        '<td><strong>' + escapeHtml(ticketNumber) + '</strong></td>' +
                        '<td>' + customerName + '</td>' +
                        '<td>' + escapeHtml(ticket.email || 'N/A') + '</td>' +
                        '<td>' + escapeHtml(ticket.title) + '</td>' +
                        '<td>' + ticket.status_badge + '</td>' +
                        '<td>' + formatDateTime(ticket.created_at) + '</td>' +
                        '<td><a href="ticket_details.php?id=' + ticket.id + '" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> View</a></td>' +
                        '</tr>';
            });
            
            tbody.html(html);
        }
        
        function updateNewCount(count) {
            var heading = $('h2');
            heading.find('.badge').remove();
            
            if (count > 0) {
                heading.append('<span class="badge bg-danger">' + count + ' New</span>');
            }
        }
        
        function formatDateTime(datetime) {
            var date = new Date(datetime);
            return date.toLocaleString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        
        $(document).ready(function() {
            // Initial load
            loadTickets();
            
            // Refresh every 20 seconds
            refreshInterval = setInterval(loadTickets, 20000);
        });
        
        $(window).on('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
    </script>
</body>
</html>