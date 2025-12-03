<?php
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/Ticket.php';
require_once 'classes/User.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$ticketManager = new Ticket($db);
$userManager = new User($db);

$ticketId = $_GET['id'] ?? 0;
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $result = $ticketManager->updateTicketStatus($_POST['ticket_id'], $_POST['status']);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'danger';
    }
}

$ticket = $ticketManager->getTicketById($ticketId);

if (!$ticket) {
    header('Location: support.php');
    exit;
}

$masterAccount = $userManager->getMasterAccountByReference($ticket['reference_uuid']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details - ProSecure Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'views/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'views/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="main-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>
                            <i class="fas fa-ticket-alt me-2"></i>
                            Ticket #<?php echo htmlspecialchars($ticket['ticket_number'] ?? $ticket['id']); ?>
                            <?php echo $ticketManager->getTicketStatusBadge($ticket['status']); ?>
                        </h2>
                        <a href="support.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Tickets
                        </a>
                    </div>
                    
                    <?php if ($message): ?>
                        <?php echo alert($message, $messageType); ?>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Ticket Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="detail-label">Subject</label>
                                        <div class="detail-value">
                                            <strong><?php echo htmlspecialchars($ticket['title']); ?></strong>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="detail-label">Message</label>
                                        <div class="detail-value">
                                            <div style="white-space: pre-wrap; word-wrap: break-word;">
                                                <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="detail-label">Created</label>
                                            <div class="detail-value"><?php echo formatDateTime($ticket['created_at']); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="detail-label">Last Updated</label>
                                            <div class="detail-value"><?php echo formatDateTime($ticket['updated_at']); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Update Status</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                        
                                        <div class="row align-items-end">
                                            <div class="col-md-8">
                                                <label for="status" class="form-label">Ticket Status</label>
                                                <select class="form-select" id="status" name="status" required>
                                                    <option value="0" <?php echo $ticket['status'] == 0 ? 'selected' : ''; ?>>New</option>
                                                    <option value="1" <?php echo $ticket['status'] == 1 ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="2" <?php echo $ticket['status'] == 2 ? 'selected' : ''; ?>>Resolved</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fas fa-save me-1"></i>Update Status
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Customer Information</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($masterAccount): ?>
                                        <div class="mb-3">
                                            <label class="detail-label">Name</label>
                                            <div class="detail-value">
                                                <strong><?php echo htmlspecialchars($masterAccount['name'] . ' ' . $masterAccount['lname']); ?></strong>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="detail-label">Email</label>
                                            <div class="detail-value">
                                                <a href="mailto:<?php echo htmlspecialchars($masterAccount['email']); ?>">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    <?php echo htmlspecialchars($masterAccount['email']); ?>
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="detail-label">Username</label>
                                            <div class="detail-value"><?php echo htmlspecialchars($masterAccount['username']); ?></div>
                                        </div>
                                        
                                        <?php if ($masterAccount['phone_number']): ?>
                                            <div class="mb-3">
                                                <label class="detail-label">Phone</label>
                                                <div class="detail-value">
                                                    <a href="tel:<?php echo htmlspecialchars($masterAccount['phone_number']); ?>">
                                                        <i class="fas fa-phone me-1"></i>
                                                        <?php echo htmlspecialchars($masterAccount['phone_number']); ?>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mb-3">
                                            <label class="detail-label">Address</label>
                                            <div class="detail-value">
                                                <?php echo htmlspecialchars($masterAccount['street']); ?><br>
                                                <?php echo htmlspecialchars($masterAccount['city'] . ', ' . $masterAccount['state'] . ' ' . $masterAccount['zip_code']); ?>
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        
                                        <a href="user_details.php?id=<?php 
                                            $masterUser = $db->fetchOne("SELECT id FROM users WHERE master_reference = ? AND is_master = 1", 
                                                [$ticket['reference_uuid']]);
                                            echo $masterUser['id'] ?? '#'; 
                                        ?>" class="btn btn-primary w-100">
                                            <i class="fas fa-user-shield me-1"></i>View Master Account
                                        </a>
                                    <?php else: ?>
                                        <div class="alert alert-warning">Customer information not found.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>