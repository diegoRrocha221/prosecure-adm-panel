<?php
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/Admin.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$adminManager = new Admin($db);

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $result = $adminManager->createAdmin(
                    $_POST['email'],
                    $_POST['name'],
                    $_POST['password'],
                    $_POST['role'] ?? 'admin'
                );
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
                break;
                
            case 'update':
                $result = $adminManager->updateAdmin(
                    $_POST['admin_id'],
                    $_POST['email'],
                    $_POST['name'],
                    $_POST['role'],
                    !empty($_POST['password']) ? $_POST['password'] : null
                );
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
                break;
                
            case 'delete':
                if ($_POST['admin_id'] != $_SESSION['admin_id']) {
                    $result = $adminManager->deleteAdmin($_POST['admin_id']);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'danger';
                } else {
                    $message = 'You cannot delete your own account.';
                    $messageType = 'warning';
                }
                break;
        }
    }
}

$admins = $adminManager->getAllAdmins();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - ProSecure Admin</title>
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
                        <h2><i class="fas fa-user-shield me-2"></i>Admin Management</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdminModal">
                            <i class="fas fa-plus me-1"></i>Add New Admin
                        </button>
                    </div>
                    
                    <?php if ($message): ?>
                        <?php echo alert($message, $messageType); ?>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($admins as $admin): ?>
                                            <tr>
                                                <td><?php echo $admin['id']; ?></td>
                                                <td><strong><?php echo htmlspecialchars($admin['name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo htmlspecialchars(ucfirst($admin['role'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning" 
                                                            onclick="editAdmin(<?php echo htmlspecialchars(json_encode($admin)); ?>)">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    
                                                    <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger delete-confirm">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Create Admin Modal -->
    <div class="modal fade" id="createAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="create_name" class="form-label required-field">Name</label>
                            <input type="text" class="form-control" id="create_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="create_email" class="form-label required-field">Email</label>
                            <input type="email" class="form-control" id="create_email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="create_password" class="form-label required-field">Password</label>
                            <input type="password" class="form-control" id="create_password" name="password" required minlength="6">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="create_role" class="form-label required-field">Role</label>
                            <select class="form-select" id="create_role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="super_admin">Super Admin</option>
                                <option value="viewer">Viewer</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Admin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Admin Modal -->
    <div class="modal fade" id="editAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="admin_id" id="edit_admin_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label required-field">Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label required-field">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="edit_password" name="password" minlength="6">
                            <small class="text-muted">Leave blank to keep current password</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_role" class="form-label required-field">Role</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="super_admin">Super Admin</option>
                                <option value="viewer">Viewer</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Admin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        function editAdmin(admin) {
            document.getElementById('edit_admin_id').value = admin.id;
            document.getElementById('edit_name').value = admin.name;
            document.getElementById('edit_email').value = admin.email;
            document.getElementById('edit_role').value = admin.role;
            document.getElementById('edit_password').value = '';
            
            var modal = new bootstrap.Modal(document.getElementById('editAdminModal'));
            modal.show();
        }
    </script>
</body>
</html>
