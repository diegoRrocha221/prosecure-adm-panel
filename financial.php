// financial.php
<?php
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/Financial.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$financialManager = new Financial($db);

$totalUsers = $financialManager->getTotalUsers();
$trialUsers = $financialManager->getTrialUsers();
$paidUsers = $financialManager->getPaidUsers();
$currentRevenue = $financialManager->getCurrentMonthRevenue();
$nextMonthRevenue = $financialManager->getNextMonthProjection();
$topPlans = $financialManager->getTopSellingPlans();
$revenueByPlan = $financialManager->getRevenueByPlan();
$monthlyGrowth = $financialManager->getMonthlyGrowth();
$testUsers = $financialManager->getTestUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial - ProSecure Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body data-page="financial">
    <?php include 'views/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'views/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="main-content">
                    <h2><i class="fas fa-chart-line me-2"></i>Financial Overview</h2>
                    
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> The following test accounts are excluded from all reports:
                        <small class="d-block mt-2"><?php echo implode(', ', $testUsers); ?></small>
                    </div>
                    
                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card" style="border-left-color: #0d6efd;">
                                <div class="card-body">
                                    <div class="stat-value text-primary"><?php echo number_format($totalUsers); ?></div>
                                    <div class="stat-label">Total Users</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stat-card" style="border-left-color: #ffc107;">
                                <div class="card-body">
                                    <div class="stat-value text-warning"><?php echo number_format($trialUsers); ?></div>
                                    <div class="stat-label">Trial/Grace Period</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stat-card" style="border-left-color: #198754;">
                                <div class="card-body">
                                    <div class="stat-value text-success"><?php echo number_format($paidUsers); ?></div>
                                    <div class="stat-label">Paid Users</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stat-card" style="border-left-color: #20c997;">
                                <div class="card-body">
                                    <div class="stat-value text-success"><?php echo formatMoney($currentRevenue); ?></div>
                                    <div class="stat-label">This Month Revenue</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Next Month Projection</h5>
                                </div>
                                <div class="card-body text-center">
                                    <h2 class="text-success mb-0"><?php echo formatMoney($nextMonthRevenue); ?></h2>
                                    <small class="text-muted">Estimated revenue for next month</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Top Selling Plans -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Selling Plans</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($topPlans)): ?>
                                        <p class="text-muted text-center">No data available</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Rank</th>
                                                        <th>Plan Name</th>
                                                        <th>Users</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($topPlans as $index => $plan): ?>
                                                        <tr>
                                                            <td><strong>#<?php echo $index + 1; ?></strong></td>
                                                            <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                                            <td><span class="badge bg-primary"><?php echo number_format($plan['total_users']); ?></span></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Revenue by Plan</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($revenueByPlan)): ?>
                                        <p class="text-muted text-center">No data available</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Plan Name</th>
                                                        <th>Customers</th>
                                                        <th>Revenue</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($revenueByPlan as $plan): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                                            <td><?php echo number_format($plan['customers']); ?></td>
                                                            <td><strong><?php echo formatMoney($plan['revenue']); ?></strong></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Monthly Growth Chart -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-dark text-white">
                                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>12-Month Growth</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="growthChart" height="80"></canvas>
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
    <script src="assets/js/navigation.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Monthly Growth Chart
        const ctx = document.getElementById('growthChart').getContext('2d');
        
        const monthlyData = <?php echo json_encode(array_reverse($monthlyGrowth)); ?>;
        const labels = monthlyData.map(item => item.month);
        const newUsers = monthlyData.map(item => parseInt(item.new_users));
        const revenue = monthlyData.map(item => parseFloat(item.revenue));
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'New Users',
                        data: newUsers,
                        borderColor: 'rgb(13, 110, 253)',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Revenue ($)',
                        data: revenue,
                        borderColor: 'rgb(25, 135, 84)',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'New Users'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Revenue ($)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>