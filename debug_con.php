<?php
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'includes/session.php';

requireLogin();

$masterReference = $_GET['master_reference'] ?? '';

echo "<h1>Debug Active Connections</h1>";
echo "<p>Master Reference: " . htmlspecialchars($masterReference) . "</p>";

if (empty($masterReference)) {
    echo "<p style='color: red;'>No master reference provided</p>";
    exit;
}

$db = new Database();
$userManager = new User($db);

echo "<h2>1. Master Account Data</h2>";
$masterAccount = $userManager->getMasterAccountByReference($masterReference);
echo "<pre>";
print_r($masterAccount);
echo "</pre>";

echo "<h2>2. Users from master_reference</h2>";
$sql = "SELECT id, username, email, is_master FROM users WHERE master_reference = ?";
$users = $db->fetchAll($sql, [$masterReference]);
echo "<pre>";
print_r($users);
echo "</pre>";

echo "<h2>3. Usernames extracted</h2>";
$usernames = [];
foreach ($users as $user) {
    if (!empty($user['username'])) {
        $usernames[] = $user['username'];
    }
    if (!empty($user['email'])) {
        $usernames[] = $user['email'];
    }
}

if (!empty($masterAccount['purchased_plans'])) {
    echo "<h3>3a. Purchased Plans JSON:</h3>";
    echo "<pre>" . htmlspecialchars($masterAccount['purchased_plans']) . "</pre>";
    
    $planUsernames = $userManager->getUsernamesFromPurchasedPlans($masterAccount['purchased_plans']);
    echo "<h3>3b. Usernames from purchased_plans:</h3>";
    echo "<pre>";
    print_r($planUsernames);
    echo "</pre>";
    
    $usernames = array_merge($usernames, $planUsernames);
}

$usernames = array_unique($usernames);
echo "<h3>3c. Final usernames list:</h3>";
echo "<pre>";
print_r($usernames);
echo "</pre>";

echo "<h2>4. RADIUS Database Query</h2>";
if (!empty($usernames)) {
    try {
        // Reindex array
        $usernames = array_values($usernames);
        
        echo "<h3>Reindexed usernames:</h3>";
        echo "<pre>";
        print_r($usernames);
        echo "</pre>";
        
        $radiusDb = new Database(true);
        
        $placeholders = str_repeat('?,', count($usernames) - 1) . '?';
        $sql = "SELECT username, acctstarttime, costumerip, nasipaddress, acctsessiontime
                FROM radacct 
                WHERE acctstoptime IS NULL 
                AND username IN ($placeholders)
                ORDER BY acctstarttime DESC";
        
        echo "<h3>SQL Query:</h3>";
        echo "<pre>" . htmlspecialchars($sql) . "</pre>";
        echo "<h3>Parameters:</h3>";
        echo "<pre>";
        print_r($usernames);
        echo "</pre>";
        
        // Test direct query
        echo "<h3>Direct test query:</h3>";
        $testSql = "SELECT username, acctstarttime, costumerip FROM radacct WHERE acctstoptime IS NULL AND username = ?";
        $testResult = $radiusDb->fetchAll($testSql, ['diego.ro.rocha.adm@gmail.com']);
        echo "<pre>";
        print_r($testResult);
        echo "</pre>";
        
        $connections = $radiusDb->fetchAll($sql, $usernames);
        
        echo "<h3>Results:</h3>";
        echo "<pre>";
        print_r($connections);
        echo "</pre>";
        
        echo "<p style='color: green; font-weight: bold;'>Found " . count($connections) . " active connection(s)</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>RADIUS Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
} else {
    echo "<p style='color: red;'>No usernames to search for</p>";
}