<?php
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/Settings.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$settingsManager = new Settings($db);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_rule':
                $result = $settingsManager->updateDiscountRuleApplied($_POST['discount_rule']);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
                break;
                
            case 'update_general':
                $discounts = [];
                if (isset($_POST['discount_qtd']) && is_array($_POST['discount_qtd'])) {
                    foreach ($_POST['discount_qtd'] as $index => $qtd) {
                        if (!empty($qtd) && !empty($_POST['discount_percent'][$index])) {
                            $discounts[] = [
                                'qtd' => $qtd,
                                'percent' => $_POST['discount_percent'][$index]
                            ];
                        }
                    }
                }
                $result = $settingsManager->updateGeneralDiscount($discounts);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'danger';
                break;
        }
    }
}

$currentRule = $settingsManager->getDiscountRuleApplied();
$generalDiscount = $settingsManager->getGeneralDiscount();
$generalDiscountArray = $generalDiscount ? json_decode($generalDiscount, true) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discount Settings - ProSecure Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-page="discount_settings">
    <?php include 'views/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'views/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="main-content">
                    <h2><i class="fas fa-percentage me-2"></i>Discount Settings</h2>
                    
                    <?php if ($message): ?>
                        <?php echo alert($message, $messageType); ?>
                    <?php endif; ?>
                    
                    <!-- Discount Rule -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Which rule should be applied?</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_rule">
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="discount_rule" id="rule_0" value="0" 
                                           <?php echo $currentRule == 0 ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="rule_0">
                                        <strong>No discount</strong> - No discounts will be applied
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="discount_rule" id="rule_1" value="1" 
                                           <?php echo $currentRule == 1 ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="rule_1">
                                        <strong>Only individual discount</strong> - Apply only plan-specific discount rules
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="discount_rule" id="rule_2" value="2" 
                                           <?php echo $currentRule == 2 ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="rule_2">
                                        <strong>Only general discount</strong> - Apply general discount rules below
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Save Rule
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- General Discount -->
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">General Discount Rules</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Define quantity-based discount rules that apply to all plans.</p>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="update_general">
                                
                                <div class="rules-container">
                                    <div id="general_discount_list">
                                        <?php if (!empty($generalDiscountArray)): ?>
                                            <?php foreach ($generalDiscountArray as $index => $discount): ?>
                                                <div class="rule-item" id="general_discount_<?php echo $index; ?>">
                                                    <button type="button" class="btn btn-sm btn-danger btn-remove-rule" 
                                                            onclick="removeDiscount(<?php echo $index; ?>)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Quantity</label>
                                                            <input type="number" class="form-control" name="discount_qtd[]" 
                                                                   value="<?php echo htmlspecialchars($discount['qtd']); ?>"
                                                                   min="1" placeholder="e.g., 3">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Discount %</label>
                                                            <input type="number" class="form-control" name="discount_percent[]" 
                                                                   value="<?php echo htmlspecialchars($discount['percent']); ?>"
                                                                   min="0" max="100" step="0.01" placeholder="e.g., 10">
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addDiscount()">
                                        <i class="fas fa-plus me-1"></i>Add Discount Rule
                                    </button>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i>Save General Discount
                                    </button>
                                </div>
                            </form>
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
        var discountCounter = <?php echo count($generalDiscountArray); ?>;
        
        function addDiscount() {
            const container = document.getElementById('general_discount_list');
            
            const discountHtml = `
                <div class="rule-item" id="general_discount_${discountCounter}">
                    <button type="button" class="btn btn-sm btn-danger btn-remove-rule" 
                            onclick="removeDiscount(${discountCounter})">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="discount_qtd[]" 
                                   min="1" placeholder="e.g., 3">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Discount %</label>
                            <input type="number" class="form-control" name="discount_percent[]" 
                                   min="0" max="100" step="0.01" placeholder="e.g., 10">
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', discountHtml);
            discountCounter++;
        }
        
        function removeDiscount(counter) {
            const element = document.getElementById('general_discount_' + counter);
            if (element) {
                element.remove();
            }
        }
    </script>
</body>
</html>