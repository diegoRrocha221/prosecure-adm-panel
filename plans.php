<?php
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/Plan.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$planManager = new Plan($db);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $rules = [];
                if (isset($_POST['rule_qtd']) && is_array($_POST['rule_qtd'])) {
                    foreach ($_POST['rule_qtd'] as $index => $qtd) {
                        if (!empty($qtd) && !empty($_POST['rule_percent'][$index])) {
                            $rules[] = [
                                'qtd' => $qtd,
                                'percent' => $_POST['rule_percent'][$index]
                            ];
                        }
                    }
                }
                
                $data = [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'price' => $_POST['price'],
                    'rules' => $rules
                ];
                
                $imageFile = isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE ? $_FILES['image'] : null;
                
                $result = $planManager->createPlan($data, $imageFile);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
                break;
                
            case 'update':
                $rules = [];
                if (isset($_POST['rule_qtd']) && is_array($_POST['rule_qtd'])) {
                    foreach ($_POST['rule_qtd'] as $index => $qtd) {
                        if (!empty($qtd) && !empty($_POST['rule_percent'][$index])) {
                            $rules[] = [
                                'qtd' => $qtd,
                                'percent' => $_POST['rule_percent'][$index]
                            ];
                        }
                    }
                }
                
                $data = [
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'price' => $_POST['price'],
                    'rules' => $rules
                ];
                
                $imageFile = isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE ? $_FILES['image'] : null;
                
                $result = $planManager->updatePlan($_POST['plan_id'], $data, $imageFile);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
                break;
        }
    }
}

$plans = $planManager->getAllPlans();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plans - ProSecure Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-page="plans">
    <?php include 'views/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'views/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="main-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-box me-2"></i>Plans Management</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPlanModal">
                            <i class="fas fa-plus me-1"></i>Add New Plan
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
                                            <th>Image</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Price</th>
                                            <th>Rules</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($plans as $plan): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($plan['image']): ?>
                                                        <img src="https://prosecurelsp.com/admins/dashboard/dashboard/pages/plans/images/<?php echo htmlspecialchars($plan['image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($plan['name']); ?>"
                                                             class="plan-image-thumb">
                                                    <?php else: ?>
                                                        <div class="plan-image-thumb bg-light d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><strong><?php echo htmlspecialchars($plan['name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars(substr($plan['description'], 0, 100)) . (strlen($plan['description']) > 100 ? '...' : ''); ?></td>
                                                <td><strong><?php echo formatMoney($plan['price']); ?></strong></td>
                                                <td>
                                                    <?php if ($plan['rules']): ?>
                                                        <?php 
                                                        $rules = json_decode($plan['rules'], true);
                                                        echo count($rules) . ' rule(s)';
                                                        ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">No rules</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning" 
                                                            onclick="editPlan(<?php echo htmlspecialchars(json_encode($plan)); ?>)">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
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
    
    <!-- Create Plan Modal -->
    <div class="modal fade" id="createPlanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Plan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="create_name" class="form-label required-field">Plan Name</label>
                            <input type="text" class="form-control" id="create_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="create_description" class="form-label required-field">Description</label>
                            <textarea class="form-control" id="create_description" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="create_price" class="form-label required-field">Price</label>
                            <input type="number" class="form-control" id="create_price" name="price" 
                                   step="0.01" min="0.01" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="create_image" class="form-label">Image</label>
                            <input type="file" class="form-control" id="create_image" name="image" 
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   onchange="previewImage(this, 'create_image_preview')">
                            <img id="create_image_preview" class="image-preview" alt="Preview">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Discount Rules <span class="text-muted">(Optional)</span></label>
                            <div class="rules-container" id="create_rules_container">
                                <p class="text-muted small mb-2">Add quantity-based discount rules for this plan.</p>
                                <div id="create_rules_list"></div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRule('create')">
                                    <i class="fas fa-plus me-1"></i>Add Rule
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Plan Modal -->
    <div class="modal fade" id="editPlanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="plan_id" id="edit_plan_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Plan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label required-field">Plan Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label required-field">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_price" class="form-label required-field">Price</label>
                            <input type="number" class="form-control" id="edit_price" name="price" 
                                   step="0.01" min="0.01" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_image" class="form-label">Image <span class="text-muted">(Leave empty to keep current)</span></label>
                            <div id="edit_current_image" class="mb-2"></div>
                            <input type="file" class="form-control" id="edit_image" name="image" 
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   onchange="previewImage(this, 'edit_image_preview')">
                            <img id="edit_image_preview" class="image-preview" alt="Preview">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Discount Rules <span class="text-muted">(Optional)</span></label>
                            <div class="rules-container" id="edit_rules_container">
                                <p class="text-muted small mb-2">Add quantity-based discount rules for this plan.</p>
                                <div id="edit_rules_list"></div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRule('edit')">
                                    <i class="fas fa-plus me-1"></i>Add Rule
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/navigation.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        var ruleCounter = { create: 0, edit: 0 };
        
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.add('show');
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.classList.remove('show');
            }
        }
        
        function addRule(mode) {
            const counter = ruleCounter[mode]++;
            const container = document.getElementById(mode + '_rules_list');
            
            const ruleHtml = `
                <div class="rule-item" id="${mode}_rule_${counter}">
                    <button type="button" class="btn btn-sm btn-danger btn-remove-rule" 
                            onclick="removeRule('${mode}', ${counter})">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="rule_qtd[]" 
                                   min="1" required placeholder="e.g., 3">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Discount %</label>
                            <input type="number" class="form-control" name="rule_percent[]" 
                                   min="0" max="100" step="0.01" required placeholder="e.g., 15">
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', ruleHtml);
        }
        
        function removeRule(mode, counter) {
            const element = document.getElementById(mode + '_rule_' + counter);
            if (element) {
                element.remove();
            }
        }
        
        function editPlan(plan) {
            document.getElementById('edit_plan_id').value = plan.id;
            document.getElementById('edit_name').value = plan.name;
            document.getElementById('edit_description').value = plan.description;
            document.getElementById('edit_price').value = plan.price;
            
            // Show current image
            const currentImageDiv = document.getElementById('edit_current_image');
            if (plan.image) {
                currentImageDiv.innerHTML = `
                    <div class="mb-2">
                        <strong>Current Image:</strong><br>
                        <img src="https://prosecurelsp.com/admins/dashboard/dashboard/pages/plans/images/${plan.image}" 
                             class="plan-image-thumb" alt="${plan.name}">
                    </div>
                `;
            } else {
                currentImageDiv.innerHTML = '<p class="text-muted small">No image set</p>';
            }
            
            // Clear and reset rules
            document.getElementById('edit_rules_list').innerHTML = '';
            document.getElementById('edit_image_preview').classList.remove('show');
            document.getElementById('edit_image').value = '';
            ruleCounter.edit = 0;
            
            // Load existing rules
            if (plan.rules) {
                try {
                    const rules = JSON.parse(plan.rules);
                    rules.forEach(rule => {
                        addRule('edit');
                        const lastRule = document.getElementById('edit_rules_list').lastElementChild;
                        lastRule.querySelector('input[name="rule_qtd[]"]').value = rule.qtd;
                        lastRule.querySelector('input[name="rule_percent[]"]').value = rule.percent;
                    });
                } catch (e) {
                    console.error('Error parsing rules:', e);
                }
            }
            
            const modal = new bootstrap.Modal(document.getElementById('editPlanModal'));
            modal.show();
        }
        
        // Reset modals on close
        $('#createPlanModal, #editPlanModal').on('hidden.bs.modal', function() {
            const mode = this.id === 'createPlanModal' ? 'create' : 'edit';
            document.getElementById(mode + '_rules_list').innerHTML = '';
            ruleCounter[mode] = 0;
        });
    </script>
</body>
</html>