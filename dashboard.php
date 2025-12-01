<?php
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$userManager = new User($db);

// Get filter parameters
$filters = [
    'search' => $_GET['search'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'is_trial' => $_GET['is_trial'] ?? '',
    'masters_only' => isset($_GET['masters_only']) ? 1 : 0,
];

// Pagination
$perPage = 20;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $perPage;

$filters['limit'] = $perPage;
$filters['offset'] = $offset;

// Get users and count
$users = $userManager->getUsers($filters);
$totalUsers = $userManager->countUsers($filters);
$totalPages = ceil($totalUsers / $perPage);

// Build filter URL
$filterParams = [];
foreach ($filters as $key => $value) {
    if ($value !== '' && $key !== 'limit' && $key !== 'offset') {
        $filterParams[] = $key . '=' . urlencode($value);
    }
}
$filterUrl = 'dashboard.php?' . implode('&', $filterParams);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - ProSecure Admin</title>
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
                        <h2><i class="fas fa-users me-2"></i>User Management</h2>
                        <div>
                            <a href="admin_management.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-shield me-1"></i>Manage Admins
                            </a>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="filter-section">
                        <form method="GET" action="dashboard.php" id="searchForm">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="search" class="form-label">Search</label>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           placeholder="Name or Email" value="<?php echo htmlspecialchars($filters['search']); ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="date_from" class="form-label">Date From</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" 
                                           value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="date_to" class="form-label">Date To</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" 
                                           value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="is_trial" class="form-label">Trial Status</label>
                                    <select class="form-select" id="is_trial" name="is_trial">
                                        <option value="">All</option>
                                        <option value="1" <?php echo $filters['is_trial'] === '1' ? 'selected' : ''; ?>>Trial</option>
                                        <option value="0" <?php echo $filters['is_trial'] === '0' ? 'selected' : ''; ?>>Paid</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label d-block">&nbsp;</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="masters_only" 
                                               name="masters_only" value="1" 
                                               <?php echo $filters['masters_only'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="masters_only">
                                            Master Accounts Only
                                        </label>
                                    </div>
                                    <div class="mt-2">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-search me-1"></i>Filter
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm" id="clearFilters">
                                            <i class="fas fa-times me-1"></i>Clear
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Results Info -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <?php 
                            $info = getPaginationInfo($currentPage, $totalPages, $perPage, $totalUsers);
                            echo "Showing {$info['start']} to {$info['end']} of {$info['total']} users";
                            ?>
                        </div>
                    </div>
                    
                    <!-- Users Table -->
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Type</th>
                                            <th>Plan</th>
                                            <th>Created</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($users)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-4">
                                                    <div class="empty-state">
                                                        <i class="fas fa-inbox"></i>
                                                        <p class="mb-0">No users found matching your criteria.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($users as $user): ?>
                                                <tr style="cursor: pointer;" data-href="user_details.php?id=<?php echo $user['id']; ?>">
                                                    <td><?php echo $user['id']; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td>
                                                        <?php 
                                                        if ($user['is_master'] == 1) {
                                                            echo '<span class="badge bg-primary">Master</span>';
                                                        } else {
                                                            echo '<span class="badge bg-secondary">Child</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($user['plan_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo formatDate($user['created_at']); ?></td>
                                                    <td><?php echo formatStatus($user['is_active'] ?? 0); ?></td>
                                                    <td>
                                                        <a href="user_details.php?id=<?php echo $user['id']; ?>" 
                                                           class="btn btn-sm btn-primary" title="View Details">
                                                            <i class="fas fa-eye"></i>
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
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="mt-4">
                            <?php echo renderPagination($currentPage, $totalPages, $filterUrl); ?>
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
