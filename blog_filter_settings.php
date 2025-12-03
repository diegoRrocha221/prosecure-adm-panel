<?php
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/Settings.php';
require_once 'classes/BlogFilter.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$settingsManager = new Settings($db);
$filterManager = new BlogFilter($db);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_filter':
                $result = $settingsManager->createBlogFilter($_POST['filter_name']);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
                break;
                
            case 'update_visibility':
                $allFilters = $filterManager->getAllFilters();
                $updates = [];
                
                foreach ($allFilters as $filter) {
                    $isChecked = isset($_POST['filter_' . $filter['uuid']]) ? 1 : 0;
                    $updates[$filter['uuid']] = $isChecked;
                }
                
                $result = $settingsManager->updateAllFilterVisibility($updates);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
                break;
        }
    }
}

$allFilters = $filterManager->getAllFilters();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Filter Settings - ProSecure Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-page="blog_filter_settings">
    <?php include 'views/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'views/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="main-content">
                    <h2><i class="fas fa-filter me-2"></i>Blog Filter Settings</h2>
                    
                    <?php if ($message): ?>
                        <?php echo alert($message, $messageType); ?>
                    <?php endif; ?>
                    
                    <!-- Create New Filter -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Create New Filter</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="create_filter">
                                
                                <div class="row align-items-end">
                                    <div class="col-md-8">
                                        <label for="filter_name" class="form-label required-field">Filter Name</label>
                                        <input type="text" class="form-control" id="filter_name" name="filter_name" 
                                               placeholder="e.g., Getting Started" required>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-plus me-1"></i>Create Filter
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Filter Visibility -->
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Which filters will appear in the public section?</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Select which filters should be visible to users on the public website.</p>
                            
                            <?php if (empty($allFilters)): ?>
                                <div class="alert alert-info">No filters available. Create one above to get started.</div>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_visibility">
                                    
                                    <div class="row">
                                        <?php foreach ($allFilters as $filter): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="filter_<?php echo htmlspecialchars($filter['uuid']); ?>" 
                                                           id="filter_<?php echo htmlspecialchars($filter['uuid']); ?>"
                                                           <?php echo $filter['is_show'] == 1 ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="filter_<?php echo htmlspecialchars($filter['uuid']); ?>">
                                                        <strong><?php echo htmlspecialchars($filter['filter']); ?></strong>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success mt-3">
                                        <i class="fas fa-save me-1"></i>Save Visibility Settings
                                    </button>
                                </form>
                            <?php endif; ?>
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
</body>
</html>