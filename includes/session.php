<?php
/**
 * Session Management - Using Database Sessions
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/DatabaseSessionHandler.php';

// Initialize database session handler
try {
    $db = new Database();
    $sessionHandler = new DatabaseSessionHandler($db->getConnection());
    session_set_save_handler($sessionHandler, true);
} catch (Exception $e) {
    error_log("Failed to initialize database session handler: " . $e->getMessage());
}

// Configure session
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 3600);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_email']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function login($adminData) {
    $_SESSION['admin_id'] = $adminData['id'];
    $_SESSION['admin_email'] = $adminData['email'];
    $_SESSION['admin_name'] = $adminData['name'];
    $_SESSION['admin_role'] = $adminData['role'];
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Force session write
    session_write_close();
}

function logout() {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

function getAdminName() {
    return $_SESSION['admin_name'] ?? 'Admin';
}

function getAdminEmail() {
    return $_SESSION['admin_email'] ?? '';
}
