<?php
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/SystemMonitor.php';
require_once 'classes/PaloAlto.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

requireLogin();

$monitor = new SystemMonitor();
$paloAlto = new PaloAlto();

$servers = $monitor->getServers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Administration - ProSecure Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .server-card {
            transition: all 0.3s ease;
            border-left: 4px solid #6c757d;
        }
        .server-card.status-good {
            border-left-color: #198754;
        }
        .server-card.status-warning {
            border-left-color: #ffc107;
        }
        .server-card.status-danger {
            border-left-color: #dc3545;
        }
        .service-badge {
            font-size: 0.85rem;
            padding: 0.35rem 0.65rem;
            margin: 0.2rem;
        }
        .resource-bar {
            height: 20px;
            border-radius: 10px;
            background: #e9ecef;
            overflow: hidden;
            position: relative;
        }
        .resource-fill {
            height: 100%;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            color: white;
            font-weight: bold;
        }
        .resource-fill.bg-success { background: #198754; }
        .resource-fill.bg-warning { background: #ffc107; }
        .resource-fill.bg-danger { background: #dc3545; }
        .log-viewer {
            background: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            padding: 15px;
            border-radius: 5px;
            max-height: 500px;
            overflow-y: auto;
        }
        .config-editor {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        .refresh-indicator {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .server-type-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        
        /* Skeleton Loading Styles */
        .skeleton {
            animation: skeleton-loading 1s linear infinite alternate;
        }
        @keyframes skeleton-loading {
            0% { background-color: #e9ecef; }
            100% { background-color: #dee2e6; }
        }
        .skeleton-text {
            height: 20px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .skeleton-badge {
            height: 28px;
            width: 80px;
            display: inline-block;
            margin: 2px;
            border-radius: 4px;
        }
        .skeleton-bar {
            height: 20px;
            border-radius: 10px;
            margin-bottom: 8px;
        }
    </style>
</head>
<body data-page="sysadmin">
    <?php include 'views/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'views/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="main-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-server me-2"></i>System Administration</h2>
                        <div>
                            <button class="btn btn-primary" onclick="refreshAllServers()">
                                <i class="fas fa-sync-alt me-1" id="refresh-icon"></i>Refresh All
                            </button>
                            <small class="text-muted ms-2" id="last-update"></small>
                        </div>
                    </div>
                    
                    <!-- Infrastructure Overview -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-dark text-white">
                                    <h5 class="mb-0"><i class="fas fa-network-wired me-2"></i>Infrastructure Overview</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 text-center">
                                            <i class="fas fa-shield-alt fa-3x text-primary mb-2"></i>
                                            <h6>Palo Alto Firewalls</h6>
                                            <p class="text-muted small">2 Active Firewalls</p>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <i class="fas fa-balance-scale fa-3x text-success mb-2"></i>
                                            <h6>Load Balancers</h6>
                                            <p class="text-muted small">3 HAProxy Instances</p>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <i class="fas fa-server fa-3x text-info mb-2"></i>
                                            <h6>Application Servers</h6>
                                            <p class="text-muted small">2 Web + 2 RADIUS</p>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <i class="fas fa-database fa-3x text-warning mb-2"></i>
                                            <h6>Database Cluster</h6>
                                            <p class="text-muted small">3-Node Galera</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Palo Alto Firewalls -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Palo Alto Firewalls</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border-primary">
                                        <div class="card-header">
                                            <strong>Firewall 1 (5.0.0.1)</strong>
                                        </div>
                                        <div class="card-body">
                                            <button class="btn btn-sm btn-outline-primary me-2" onclick="viewPaloAltoUsers('fw1')">
                                                <i class="fas fa-users"></i> User Mappings
                                            </button>
                                            <button class="btn btn-sm btn-outline-info me-2" onclick="viewPaloAltoSessions('fw1')">
                                                <i class="fas fa-stream"></i> Sessions
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" onclick="viewPaloAltoHA('fw1')">
                                                <i class="fas fa-network-wired"></i> HA Status
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-primary">
                                        <div class="card-header">
                                            <strong>Firewall 2 (5.0.0.3)</strong>
                                        </div>
                                        <div class="card-body">
                                            <button class="btn btn-sm btn-outline-primary me-2" onclick="viewPaloAltoUsers('fw2')">
                                                <i class="fas fa-users"></i> User Mappings
                                            </button>
                                            <button class="btn btn-sm btn-outline-info me-2" onclick="viewPaloAltoSessions('fw2')">
                                                <i class="fas fa-stream"></i> Sessions
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" onclick="viewPaloAltoHA('fw2')">
                                                <i class="fas fa-network-wired"></i> HA Status
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Load Balancers -->
                    <h4 class="mb-3"><i class="fas fa-balance-scale me-2"></i>Load Balancers</h4>
                    <div class="row mb-4" id="loadbalancers-container">
                        <?php foreach ($servers as $key => $server): ?>
                            <?php if ($server['type'] === 'loadbalancer' || $server['type'] === 'db-loadbalancer'): ?>
                                <?php echo renderServerCard($key, $server); ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Web Servers -->
                    <h4 class="mb-3"><i class="fas fa-globe me-2"></i>Web Servers</h4>
                    <div class="row mb-4" id="webservers-container">
                        <?php foreach ($servers as $key => $server): ?>
                            <?php if ($server['type'] === 'webserver'): ?>
                                <?php echo renderServerCard($key, $server); ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- RADIUS Servers -->
                    <h4 class="mb-3"><i class="fas fa-key me-2"></i>RADIUS Servers</h4>
                    <div class="row mb-4" id="radius-container">
                        <?php foreach ($servers as $key => $server): ?>
                            <?php if ($server['type'] === 'radius'): ?>
                                <?php echo renderServerCard($key, $server); ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Database Servers -->
                    <h4 class="mb-3"><i class="fas fa-database me-2"></i>Database Servers (Galera Cluster)</h4>
                    <div class="row mb-4" id="database-container">
                        <?php foreach ($servers as $key => $server): ?>
                            <?php if ($server['type'] === 'database'): ?>
                                <?php echo renderServerCard($key, $server); ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modals permanecem iguais -->
    <div class="modal fade" id="serverModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="serverModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="serverModalBody">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="configModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="configModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">File Path: <strong id="configPath"></strong></label>
                        <textarea class="form-control config-editor" id="configContent" rows="20"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveConfig()">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="logModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="log-viewer" id="logContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="refreshLog()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="paloAltoModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paloAltoModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="paloAltoModalBody">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/navigation.js"></script>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/sysadmin.js"></script>
</body>
</html>

<?php
function renderServerCard($key, $server) {
    $typeIcons = [
        'loadbalancer' => 'fa-balance-scale',
        'db-loadbalancer' => 'fa-balance-scale',
        'webserver' => 'fa-globe',
        'radius' => 'fa-key',
        'database' => 'fa-database'
    ];
    
    $typeColors = [
        'loadbalancer' => 'primary',
        'db-loadbalancer' => 'info',
        'webserver' => 'success',
        'radius' => 'warning',
        'database' => 'danger'
    ];
    
    $icon = $typeIcons[$server['type']] ?? 'fa-server';
    $color = $typeColors[$server['type']] ?? 'secondary';
    
    ob_start();
    ?>
    <div class="col-md-6 mb-3">
        <div class="card server-card" id="server-<?php echo $key; ?>">
            <span class="badge bg-<?php echo $color; ?> server-type-badge"><?php echo strtoupper($server['type']); ?></span>
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas <?php echo $icon; ?> me-2"></i>
                    <?php echo htmlspecialchars($server['name']); ?>
                    <small class="text-muted">(<?php echo $server['ip']; ?>)</small>
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Services:</strong>
                    <div id="services-<?php echo $key; ?>">
                        <div class="skeleton skeleton-badge"></div>
                        <div class="skeleton skeleton-badge"></div>
                        <div class="skeleton skeleton-badge"></div>
                    </div>
                </div>
                
                <div class="mb-3" id="resources-<?php echo $key; ?>">
                    <div class="skeleton skeleton-bar"></div>
                    <div class="skeleton skeleton-bar"></div>
                    <div class="skeleton skeleton-bar"></div>
                </div>
                
                <div class="btn-group btn-group-sm w-100" role="group">
                    <button class="btn btn-outline-primary" onclick="viewServerDetails('<?php echo $key; ?>')">
                        <i class="fas fa-info-circle"></i> Details
                    </button>
                    <button class="btn btn-outline-secondary" onclick="testConnection('<?php echo $key; ?>')">
                        <i class="fas fa-network-wired"></i> Test
                    </button>
                    <?php if (!empty($server['config_paths'])): ?>
                        <button class="btn btn-outline-info" onclick="viewConfigs('<?php echo $key; ?>')">
                            <i class="fas fa-file-code"></i> Configs
                        </button>
                    <?php endif; ?>
                    <?php if (!empty($server['logs'])): ?>
                        <button class="btn btn-outline-warning" onclick="viewLogs('<?php echo $key; ?>')">
                            <i class="fas fa-file-alt"></i> Logs
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>