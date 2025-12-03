<?php
/**
 * Database Configuration
 */

require_once __DIR__ . '/env.php';

Env::load();

define('DB_HOST', Env::get('DB_HOST', '172.31.255.26'));
define('DB_USER', Env::get('DB_USER', 'root'));
define('DB_PASS', Env::get('DB_PASS', ''));
define('DB_NAME', Env::get('DB_NAME', 'prosecure_web'));
define('DB_CHARSET', Env::get('DB_CHARSET', 'utf8mb4'));

// Radius Database Configuration
define('RADIUS_DB_HOST', Env::get('RADIUS_DB_HOST', '172.31.255.26'));
define('RADIUS_DB_USER', Env::get('RADIUS_DB_USER', 'root'));
define('RADIUS_DB_PASS', Env::get('RADIUS_DB_PASS', ''));
define('RADIUS_DB_NAME', Env::get('RADIUS_DB_NAME', 'radius'));
define('RADIUS_DB_CHARSET', Env::get('RADIUS_DB_CHARSET', 'utf8mb4'));

// Application Settings
define('APP_ENV', Env::get('APP_ENV', 'production'));
define('APP_DEBUG', Env::get('APP_DEBUG', 'false') === 'true');

// Image Base URL
define('IMAGE_BASE_URL', Env::get('IMAGE_BASE_URL', 'https://prosecurelsp.com/admins/dashboard/dashboard/pages/plans/images/'));

// Error reporting based on environment
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}