<?php
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/AuthorizeNet.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$userManager = new User($db);
$authorizeNet = new AuthorizeNet();

$userId = $_GET['id'] ?? 0;
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_master') {
        $result = $userManager->updateMasterAccount($_POST['master_id'], $_POST);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'danger';
    }
}

$user = $userManager->getUserById($userId);

if (!$user) {
    header('Location: dashboard.php');
    exit;
}

// Get master account details if user is master or get parent master details if child
$masterAccount = null;
$childUsers = [];
$invoices = [];
$billingInfo = null;
$subscriptionInfo = null;
$authorizeNetData = null;
$purchasedPlans = [];

if ($user['is_master'] == 1) {
    // This is a master account
    $masterAccount = $userManager->getMasterAccountByReference($user['master_reference']);
    $childUsers = $userManager->getChildUsersByMaster($user['master_reference']);
    $invoices = $userManager->getInvoicesByMaster($user['master_reference']);
    $billingInfo = $userManager->getBillingInfo($user['master_reference']);
    $subscriptionInfo = $userManager->getSubscriptionInfo($user['master_reference']);
    
    // Get Authorize.net data if subscription exists
    if ($subscriptionInfo && !empty($subscriptionInfo['subscription_id'])) {
        try {
            $authorizeNetData = $authorizeNet->getSubscriptionDetails($subscriptionInfo['subscription_id']);
        } catch (Exception $e) {
            error_log("Error fetching Authorize.net data: " . $e->getMessage());
        }
    }
    
    // Parse purchased plans JSON
    if ($masterAccount && !empty($masterAccount['purchased_plans'])) {
        $purchasedPlans = json_decode($masterAccount['purchased_plans'], true);
    }
} else {
    // This is a child account - get parent master
    $masterAccount = $userManager->getMasterAccountByReference($user['master_reference']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - ProSecure Admin</title>
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
                            <i class="fas fa-user me-2"></i>
                            <?php echo htmlspecialchars($user['username']); ?>
                            <?php if ($user['is_master'] == 1): ?>
                                <span class="badge bg-primary ms-2">Master Account</span>
                            <?php else: ?>
                                <span class="badge bg-secondary ms-2">Child Account</span>
                            <?php endif; ?>
                        </h2>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to List
                        </a>
                    </div>
                    
                    <?php if ($message): ?>
                        <?php echo alert($message, $messageType); ?>
                    <?php endif; ?>
                    
                    <?php if ($user['is_master'] == 1): ?>
                        <!-- MASTER ACCOUNT VIEW -->
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Master Account Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($masterAccount): ?>
                                            <form method="POST" action="">
                                                <input type="hidden" name="action" value="update_master">
                                                <input type="hidden" name="master_id" value="<?php echo $masterAccount['id']; ?>">
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6 class="border-bottom pb-2 mb-3">Personal Information</h6>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label required-field">First Name</label>
                                                            <input type="text" class="form-control" name="name" 
                                                                   value="<?php echo htmlspecialchars($masterAccount['name']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label required-field">Last Name</label>
                                                            <input type="text" class="form-control" name="lname" 
                                                                   value="<?php echo htmlspecialchars($masterAccount['lname']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label required-field">Email</label>
                                                            <input type="email" class="form-control" name="email" 
                                                                   value="<?php echo htmlspecialchars($masterAccount['email']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label required-field">Username</label>
                                                            <input type="text" class="form-control" name="username" 
                                                                   value="<?php echo htmlspecialchars($masterAccount['username']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Phone Number</label>
                                                            <input type="text" class="form-control" name="phone_number" 
                                                                   value="<?php echo htmlspecialchars($masterAccount['phone_number'] ?? ''); ?>">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <h6 class="border-bottom pb-2 mb-3">Address Information</h6>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label required-field">Street</label>
                                                            <input type="text" class="form-control" name="street" 
                                                                   value="<?php echo htmlspecialchars($masterAccount['street']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label required-field">City</label>
                                                            <input type="text" class="form-control" name="city" 
                                                                   value="<?php echo htmlspecialchars($masterAccount['city']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label required-field">State</label>
                                                            <input type="text" class="form-control" name="state" 
                                                                   value="<?php echo htmlspecialchars($masterAccount['state']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label required-field">ZIP Code</label>
                                                            <input type="text" class="form-control" name="zip_code" 
                                                                   value="<?php echo htmlspecialchars($masterAccount['zip_code']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Additional Info</label>
                                                            <input type="text" class="form-control" name="additional_info" 
                                                                   value="<?php echo htmlspecialchars($masterAccount['additional_info']); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <h6 class="border-bottom pb-2 mb-3">Account Status</h6>
                                                        <div class="row">
                                                            <div class="col-md-3">
                                                                <div class="detail-label">Trial Status</div>
                                                                <div class="detail-value"><?php echo formatTrialStatus($masterAccount['is_trial']); ?></div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="detail-label">Billing Type</div>
                                                                <div class="detail-value">
                                                                    <?php echo $masterAccount['is_annually'] == 1 ? 
                                                                        '<span class="badge bg-info">Annual</span>' : 
                                                                        '<span class="badge bg-info">Monthly</span>'; ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="detail-label">Total Price</div>
                                                                <div class="detail-value"><?php echo formatMoney($masterAccount['total_price']); ?></div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="detail-label">Renew Date</div>
                                                                <div class="detail-value"><?php echo formatDate($masterAccount['renew_date']); ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Subscription & Billing Information -->
                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <h6 class="border-bottom pb-2 mb-3">Subscription & Billing Information</h6>
                                                        <div class="row">
                                                            <div class="col-md-3">
                                                                <div class="detail-label">Subscription ID</div>
                                                                <div class="detail-value">
                                                                    <code><?php echo htmlspecialchars($subscriptionInfo['subscription_id'] ?? 'N/A'); ?></code>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="detail-label">Subscription Status</div>
                                                                <div class="detail-value">
                                                                    <?php 
                                                                    if ($subscriptionInfo) {
                                                                        $status = $subscriptionInfo['status'] ?? 'unknown';
                                                                        $badge = $status === 'active' ? 'success' : 'secondary';
                                                                        echo '<span class="badge bg-' . $badge . '">' . ucfirst($status) . '</span>';
                                                                    } else {
                                                                        echo 'N/A';
                                                                    }
                                                                    ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="detail-label">Card Holder</div>
                                                                <div class="detail-value"><?php echo htmlspecialchars($billingInfo['holder_name'] ?? 'N/A'); ?></div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="detail-label">Card Number</div>
                                                                <div class="detail-value"><?php echo htmlspecialchars($billingInfo['card'] ?? 'N/A'); ?></div>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php if ($subscriptionInfo): ?>
                                                        <div class="row mt-3">
                                                            <div class="col-md-4">
                                                                <div class="detail-label">Next Billing Date</div>
                                                                <div class="detail-value"><?php echo formatDate($subscriptionInfo['next_billing_date'] ?? ''); ?></div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="detail-label">Created At</div>
                                                                <div class="detail-value"><?php echo formatDateTime($subscriptionInfo['created_at'] ?? ''); ?></div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="detail-label">Card Expiry</div>
                                                                <div class="detail-value"><?php echo htmlspecialchars($billingInfo['expiry'] ?? 'N/A'); ?></div>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <!-- Authorize.net Live Data -->
                                                <?php if ($authorizeNetData): ?>
                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <h6 class="border-bottom pb-2 mb-3">
                                                            <i class="fas fa-credit-card me-2"></i>Authorize.net Live Data
                                                            <span class="badge bg-success ms-2">Live</span>
                                                        </h6>
                                                        
                                                        <div class="row">
                                                            <div class="col-md-3">
                                                                <div class="detail-label">Subscription Name</div>
                                                                <div class="detail-value"><?php echo htmlspecialchars($authorizeNetData['name']); ?></div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="detail-label">Amount</div>
                                                                <div class="detail-value">
                                                                    <strong class="text-success">$<?php echo $authorizeNetData['amount']; ?></strong>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="detail-label">Billing Interval</div>
                                                                <div class="detail-value">
                                                                    <?php echo $authorizeNetData['interval_length'] . ' ' . $authorizeNetData['interval_unit']; ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="detail-label">Status</div>
                                                                <div class="detail-value">
                                                                    <?php 
                                                                    $status = strtolower($authorizeNetData['status']);
                                                                    $badge = $status === 'active' ? 'success' : 
                                                                            ($status === 'suspended' ? 'warning' : 'danger');
                                                                    echo '<span class="badge bg-' . $badge . '">' . ucfirst($status) . '</span>';
                                                                    ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="row mt-3">
                                                            <div class="col-md-4">
                                                                <div class="detail-label">Start Date</div>
                                                                <div class="detail-value"><?php echo $authorizeNetData['start_date']; ?></div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="detail-label">Total Occurrences</div>
                                                                <div class="detail-value"><?php echo $authorizeNetData['total_occurrences']; ?></div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="detail-label">Trial Occurrences</div>
                                                                <div class="detail-value"><?php echo $authorizeNetData['trial_occurrences']; ?></div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="row mt-3">
                                                            <div class="col-md-6">
                                                                <h6 class="text-muted mb-2">Payment Details</h6>
                                                                <div class="detail-label">Card Number</div>
                                                                <div class="detail-value">
                                                                    <i class="fas fa-credit-card me-2"></i>
                                                                    <?php echo htmlspecialchars($authorizeNetData['card_number']); ?>
                                                                </div>
                                                                
                                                                <div class="detail-label mt-2">Card Type</div>
                                                                <div class="detail-value"><?php echo htmlspecialchars($authorizeNetData['card_type']); ?></div>
                                                                
                                                                <div class="detail-label mt-2">Expiration</div>
                                                                <div class="detail-value"><?php echo htmlspecialchars($authorizeNetData['expiration_date']); ?></div>
                                                            </div>
                                                            
                                                            <div class="col-md-6">
                                                                <h6 class="text-muted mb-2">Billing Address</h6>
                                                                <div class="detail-value">
                                                                    <?php echo htmlspecialchars($authorizeNetData['billing_first_name'] . ' ' . $authorizeNetData['billing_last_name']); ?><br>
                                                                    <?php echo htmlspecialchars($authorizeNetData['billing_address']); ?><br>
                                                                    <?php echo htmlspecialchars($authorizeNetData['billing_city'] . ', ' . $authorizeNetData['billing_state'] . ' ' . $authorizeNetData['billing_zip']); ?>
                                                                </div>
                                                                
                                                                <div class="detail-label mt-3">Customer Profile ID</div>
                                                                <div class="detail-value">
                                                                    <code><?php echo htmlspecialchars($authorizeNetData['customer_profile_id']); ?></code>
                                                                </div>
                                                                
                                                                <div class="detail-label mt-2">Payment Profile ID</div>
                                                                <div class="detail-value">
                                                                    <code><?php echo htmlspecialchars($authorizeNetData['payment_profile_id']); ?></code>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php elseif ($subscriptionInfo && !empty($subscriptionInfo['subscription_id'])): ?>
                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <div class="alert alert-warning">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                            Unable to fetch live data from Authorize.net. The subscription ID may be invalid or there may be an API connection issue.
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="mt-4">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save me-1"></i>Save Changes
                                                    </button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <div class="alert alert-warning">Master account details not found.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Purchased Plans -->
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Purchased Plans</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($purchasedPlans)): ?>
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Plan Name</th>
                                                            <th>Plan ID</th>
                                                            <th>Billing</th>
                                                            <th>Assigned To</th>
                                                            <th>Type</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($purchasedPlans as $plan): ?>
                                                            <tr>
                                                                <td><strong><?php echo htmlspecialchars($plan['plan_name'] ?? 'N/A'); ?></strong></td>
                                                                <td><?php echo $plan['plan_id'] ?? 'N/A'; ?></td>
                                                                <td>
                                                                    <?php echo ($plan['anually'] ?? 0) == 1 ? 
                                                                        '<span class="badge bg-info">Annual</span>' : 
                                                                        '<span class="badge bg-info">Monthly</span>'; ?>
                                                                </td>
                                                                <td>
                                                                    <?php 
                                                                    $assignedTo = $plan['username'] ?? 'none';
                                                                    if ($assignedTo === 'none') {
                                                                        echo '<span class="text-muted">Not assigned</span>';
                                                                    } else {
                                                                        echo '<strong>' . htmlspecialchars($assignedTo) . '</strong>';
                                                                        if (!empty($plan['email']) && $plan['email'] !== 'none') {
                                                                            echo '<br><small>' . htmlspecialchars($plan['email']) . '</small>';
                                                                        }
                                                                    }
                                                                    ?>
                                                                </td>
                                                                <td>
                                                                    <?php echo ($plan['is_master'] ?? 0) == 1 ? 
                                                                        '<span class="badge bg-primary">Master Plan</span>' : 
                                                                        '<span class="badge bg-secondary">Additional Plan</span>'; ?>
                                                                </td>
                                                                <td>
                                                                    <?php echo $assignedTo === 'none' ? 
                                                                        '<span class="badge bg-warning">Available</span>' : 
                                                                        '<span class="badge bg-success">In Use</span>'; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info">No purchased plans found.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Child Users -->
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Child Users (<?php echo count($childUsers); ?>)</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($childUsers)): ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Username</th>
                                                            <th>Email</th>
                                                            <th>Plan</th>
                                                            <th>Created</th>
                                                            <th>Status</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($childUsers as $child): ?>
                                                            <tr>
                                                                <td><strong><?php echo htmlspecialchars($child['username']); ?></strong></td>
                                                                <td><?php echo htmlspecialchars($child['email']); ?></td>
                                                                <td><?php echo htmlspecialchars($child['plan_name'] ?? 'N/A'); ?></td>
                                                                <td><?php echo formatDate($child['created_at']); ?></td>
                                                                <td><?php echo formatStatus($child['is_active'] ?? 0); ?></td>
                                                                <td>
                                                                    <a href="user_details.php?id=<?php echo $child['id']; ?>" 
                                                                       class="btn btn-sm btn-primary">
                                                                        <i class="fas fa-eye"></i> View
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info">No child users found for this master account.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Invoices -->
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <div class="card">
                                    <div class="card-header bg-warning">
                                        <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Invoices (<?php echo count($invoices); ?>)</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($invoices)): ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Total</th>
                                                            <th>Trial</th>
                                                            <th>Due Date</th>
                                                            <th>Created</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($invoices as $invoice): ?>
                                                            <tr>
                                                                <td><?php echo $invoice['id']; ?></td>
                                                                <td><strong><?php echo formatMoney($invoice['total']); ?></strong></td>
                                                                <td><?php echo formatTrialStatus($invoice['is_trial']); ?></td>
                                                                <td><?php echo formatDate($invoice['due_date']); ?></td>
                                                                <td><?php echo formatDateTime($invoice['created_at']); ?></td>
                                                                <td><?php echo formatPaymentStatus($invoice['is_paid']); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info">No invoices found for this master account.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    <?php else: ?>
                        <!-- CHILD ACCOUNT VIEW -->
                        <div class="row">
                            <div class="col-md-8 mx-auto">
                                <div class="card">
                                    <div class="card-header bg-secondary text-white">
                                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Child Account Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="detail-label">Username</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($user['username']); ?></div>
                                                
                                                <div class="detail-label">Email</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($user['email']); ?></div>
                                                
                                                <div class="detail-label">Plan</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($user['plan_name'] ?? 'N/A'); ?></div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="detail-label">Created At</div>
                                                <div class="detail-value"><?php echo formatDateTime($user['created_at']); ?></div>
                                                
                                                <div class="detail-label">Status</div>
                                                <div class="detail-value"><?php echo formatStatus($user['is_active'] ?? 0); ?></div>
                                                
                                                <div class="detail-label">Email Confirmed</div>
                                                <div class="detail-value"><?php echo formatStatus($user['email_confirmed'] ?? 0); ?></div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($masterAccount): ?>
                                            <hr class="my-4">
                                            <h6 class="mb-3">Parent Master Account</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="detail-label">Master Name</div>
                                                    <div class="detail-value">
                                                        <?php echo htmlspecialchars($masterAccount['name'] . ' ' . $masterAccount['lname']); ?>
                                                    </div>
                                                    
                                                    <div class="detail-label">Master Email</div>
                                                    <div class="detail-value"><?php echo htmlspecialchars($masterAccount['email']); ?></div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <div class="detail-label">Master Username</div>
                                                    <div class="detail-value"><?php echo htmlspecialchars($masterAccount['username']); ?></div>
                                                    
                                                    <div class="detail-label">Master Account Created</div>
                                                    <div class="detail-value"><?php echo formatDateTime($masterAccount['created_at']); ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-3">
                                                <a href="user_details.php?id=<?php 
                                                    // Find master user ID
                                                    $masterUser = $db->fetchOne("SELECT id FROM users WHERE master_reference = ? AND is_master = 1", 
                                                        [$user['master_reference']]);
                                                    echo $masterUser['id'] ?? '#'; 
                                                ?>" class="btn btn-primary">
                                                    <i class="fas fa-user-shield me-1"></i>View Master Account
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
