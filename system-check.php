<?php
/**
 * ProSecure Admin Panel - System Check
 * This file checks if your server meets the requirements
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Check - ProSecure Admin Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 10px;
        }
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #ddd;
            background-color: #f9f9f9;
        }
        .check-item.success {
            border-left-color: #28a745;
            background-color: #d4edda;
        }
        .check-item.warning {
            border-left-color: #ffc107;
            background-color: #fff3cd;
        }
        .check-item.error {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }
        .status {
            font-weight: bold;
            margin-right: 10px;
        }
        .status.ok { color: #28a745; }
        .status.warning { color: #ffc107; }
        .status.fail { color: #dc3545; }
        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
        code {
            background-color: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 ProSecure Admin Panel - System Check</h1>
        
        <?php
        $errors = 0;
        $warnings = 0;
        
        // Check PHP Version
        $php_version = phpversion();
        $php_ok = version_compare($php_version, '7.4.0', '>=');
        ?>
        
        <div class="check-item <?php echo $php_ok ? 'success' : 'error'; ?>">
            <span class="status <?php echo $php_ok ? 'ok' : 'fail'; ?>">
                <?php echo $php_ok ? '✓' : '✗'; ?>
            </span>
            <strong>PHP Version:</strong> <?php echo $php_version; ?>
            <?php if (!$php_ok): $errors++; ?>
                <br><small>Required: PHP 7.4 or higher</small>
            <?php endif; ?>
        </div>
        
        <?php
        // Check PDO
        $pdo_ok = extension_loaded('pdo') && extension_loaded('pdo_mysql');
        ?>
        
        <div class="check-item <?php echo $pdo_ok ? 'success' : 'error'; ?>">
            <span class="status <?php echo $pdo_ok ? 'ok' : 'fail'; ?>">
                <?php echo $pdo_ok ? '✓' : '✗'; ?>
            </span>
            <strong>PDO Extension:</strong> <?php echo $pdo_ok ? 'Installed' : 'Not installed'; ?>
            <?php if (!$pdo_ok): $errors++; ?>
                <br><small>PDO and PDO_MySQL extensions are required</small>
            <?php endif; ?>
        </div>
        
        <?php
        // Check database connection
        $db_ok = false;
        $db_message = '';
        try {
            $dsn = 'mysql:host=172.31.255.26;dbname=prosecure_web;charset=utf8mb4';
            $pdo = new PDO($dsn, 'root', 'Security.4uall!', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $db_ok = true;
            $db_message = 'Connection successful';
        } catch (PDOException $e) {
            $db_message = 'Connection failed: ' . $e->getMessage();
            $errors++;
        }
        ?>
        
        <div class="check-item <?php echo $db_ok ? 'success' : 'error'; ?>">
            <span class="status <?php echo $db_ok ? 'ok' : 'fail'; ?>">
                <?php echo $db_ok ? '✓' : '✗'; ?>
            </span>
            <strong>Database Connection:</strong> <?php echo $db_message; ?>
        </div>
        
        <?php
        // Check if admins table exists
        $table_ok = false;
        if ($db_ok) {
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
                $table_ok = $stmt->rowCount() > 0;
            } catch (PDOException $e) {
                $table_ok = false;
            }
        }
        ?>
        
        <div class="check-item <?php echo $table_ok ? 'success' : 'error'; ?>">
            <span class="status <?php echo $table_ok ? 'ok' : 'fail'; ?>">
                <?php echo $table_ok ? '✓' : '✗'; ?>
            </span>
            <strong>Admins Table:</strong> <?php echo $table_ok ? 'Exists' : 'Not found'; ?>
            <?php if (!$table_ok && $db_ok): $errors++; ?>
                <br><small>Please ensure the database schema is properly imported</small>
            <?php endif; ?>
        </div>
        
        <?php
        // Check if first admin exists
        $admin_exists = false;
        $admin_count = 0;
        if ($db_ok && $table_ok) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $admin_count = $result['count'];
                $admin_exists = $admin_count > 0;
            } catch (PDOException $e) {
                $admin_exists = false;
            }
        }
        ?>
        
        <div class="check-item <?php echo $admin_exists ? 'success' : 'warning'; ?>">
            <span class="status <?php echo $admin_exists ? 'ok' : 'warning'; ?>">
                <?php echo $admin_exists ? '✓' : '⚠'; ?>
            </span>
            <strong>Admin Users:</strong> <?php echo $admin_count; ?> admin(s) found
            <?php if (!$admin_exists): $warnings++; ?>
                <br><small>You need to create at least one admin user. Run the <code>setup_admin.sql</code> script.</small>
            <?php endif; ?>
        </div>
        
        <?php
        // Check file permissions
        $files_ok = is_readable('config/database.php') && is_readable('classes/Database.php');
        ?>
        
        <div class="check-item <?php echo $files_ok ? 'success' : 'warning'; ?>">
            <span class="status <?php echo $files_ok ? 'ok' : 'warning'; ?>">
                <?php echo $files_ok ? '✓' : '⚠'; ?>
            </span>
            <strong>File Permissions:</strong> <?php echo $files_ok ? 'OK' : 'Check permissions'; ?>
            <?php if (!$files_ok): $warnings++; ?>
                <br><small>Some files may not be readable. Check file permissions.</small>
            <?php endif; ?>
        </div>
        
        <?php
        // Check session
        $session_ok = session_status() !== PHP_SESSION_DISABLED;
        ?>
        
        <div class="check-item <?php echo $session_ok ? 'success' : 'error'; ?>">
            <span class="status <?php echo $session_ok ? 'ok' : 'fail'; ?>">
                <?php echo $session_ok ? '✓' : '✗'; ?>
            </span>
            <strong>Session Support:</strong> <?php echo $session_ok ? 'Enabled' : 'Disabled'; ?>
            <?php if (!$session_ok): $errors++; ?>
                <br><small>PHP sessions are required for authentication</small>
            <?php endif; ?>
        </div>
        
        <?php
        // Check required PHP functions
        $functions = ['password_hash', 'password_verify', 'htmlspecialchars', 'json_decode'];
        $functions_ok = true;
        foreach ($functions as $func) {
            if (!function_exists($func)) {
                $functions_ok = false;
                break;
            }
        }
        ?>
        
        <div class="check-item <?php echo $functions_ok ? 'success' : 'error'; ?>">
            <span class="status <?php echo $functions_ok ? 'ok' : 'fail'; ?>">
                <?php echo $functions_ok ? '✓' : '✗'; ?>
            </span>
            <strong>Required PHP Functions:</strong> <?php echo $functions_ok ? 'All available' : 'Some missing'; ?>
            <?php if (!$functions_ok): $errors++; ?>
                <br><small>Some required PHP functions are not available</small>
            <?php endif; ?>
        </div>
        
        <!-- Summary -->
        <div class="info">
            <h3>Summary</h3>
            <?php if ($errors == 0 && $warnings == 0): ?>
                <p style="color: #28a745; font-weight: bold;">
                    ✓ All checks passed! Your system is ready to run the ProSecure Admin Panel.
                </p>
                <p>You can now access the admin panel at: <a href="index.php">index.php</a></p>
            <?php elseif ($errors == 0): ?>
                <p style="color: #ffc107; font-weight: bold;">
                    ⚠ System is functional but has <?php echo $warnings; ?> warning(s).
                </p>
                <p>Please review the warnings above. The system should work, but some features may require attention.</p>
            <?php else: ?>
                <p style="color: #dc3545; font-weight: bold;">
                    ✗ Found <?php echo $errors; ?> error(s) and <?php echo $warnings; ?> warning(s).
                </p>
                <p>Please fix the errors above before proceeding. The system will not work properly until these issues are resolved.</p>
            <?php endif; ?>
        </div>
        
        <?php if (!$admin_exists && $db_ok && $table_ok): ?>
        <div class="info" style="background-color: #fff3cd; border-color: #ffc107;">
            <h3>Next Step: Create First Admin</h3>
            <p>Run this SQL command in your database:</p>
            <code style="display: block; padding: 10px; background: white;">
                mysql -h 172.31.255.26 -u root -p'Security.4uall!' prosecure_web &lt; setup_admin.sql
            </code>
            <p style="margin-top: 10px;">Or execute the SQL manually from <code>setup_admin.sql</code></p>
        </div>
        <?php endif; ?>
        
        <div class="info">
            <h3>System Information</h3>
            <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
            <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
            <p><strong>Operating System:</strong> <?php echo PHP_OS; ?></p>
            <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></p>
        </div>
        
        <p style="text-align: center; color: #666; margin-top: 30px;">
            <small>ProSecure Admin Panel v1.0 | System Check Tool</small>
        </p>
    </div>
</body>
</html>
