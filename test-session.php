<?php
/**
 * Session Test - Delete this file after testing
 */

require_once 'includes/session.php';

echo "<h1>Session Test</h1>";

// Test session write
$_SESSION['test_key'] = 'test_value_' . time();

echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Test value set: " . $_SESSION['test_key'] . "</p>";

// Force write
session_write_close();

// Start again and read
session_start();

echo "<p>Test value read: " . ($_SESSION['test_key'] ?? 'NOT FOUND') . "</p>";

// Check database
require_once 'config/database.php';
require_once 'classes/Database.php';

$db = new Database();
$sessions = $db->fetchAll("SELECT Session_Id, Session_Expires, LEFT(Session_Data, 100) as Data FROM Session ORDER BY Session_Expires DESC LIMIT 5");

echo "<h2>Sessions in Database:</h2>";
echo "<pre>";
print_r($sessions);
echo "</pre>";

// Check if admin exists
$admins = $db->fetchAll("SELECT id, email, name, role FROM admins");
echo "<h2>Admins in Database:</h2>";
echo "<pre>";
print_r($admins);
echo "</pre>";
