<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(60);

require_once 'config/database.php';

echo "<h1>Test SCP Upload</h1>";

// Create test file
$testFile = sys_get_temp_dir() . '/test_' . time() . '.txt';
file_put_contents($testFile, 'Test content ' . date('Y-m-d H:i:s'));

echo "<p>Test file created: $testFile</p>";

$servers = Env::getRemoteServers('blog');

echo "<h2>Servers to test:</h2>";
echo "<pre>";
print_r($servers);
echo "</pre>";

foreach ($servers as $index => $server) {
    echo "<hr>";
    echo "<h3>Testing Server $index: {$server['host']}</h3>";
    
    $remotePath = $server['path'] . 'test_' . time() . '.txt';
    
    echo "<p>Remote path: $remotePath</p>";
    
    // Test 1: Check if timeout command exists
    exec('which timeout', $timeoutCheck, $timeoutReturn);
    echo "<p>Timeout command: " . ($timeoutReturn === 0 ? "Available at " . $timeoutCheck[0] : "NOT AVAILABLE") . "</p>";
    
    // Test 2: Try SSH connection
    echo "<h4>Test SSH Connection:</h4>";
    $sshTest = sprintf(
        'timeout 10 sshpass -p %s ssh -o StrictHostKeyChecking=no -o ConnectTimeout=5 %s@%s "echo connected" 2>&1',
        escapeshellarg($server['pass']),
        escapeshellarg($server['user']),
        escapeshellarg($server['host'])
    );
    
    $startTime = microtime(true);
    exec($sshTest, $sshOutput, $sshReturn);
    $endTime = microtime(true);
    
    echo "<p>Time taken: " . round($endTime - $startTime, 2) . " seconds</p>";
    echo "<p>Return code: $sshReturn</p>";
    echo "<p>Output: <pre>" . htmlspecialchars(implode("\n", $sshOutput)) . "</pre></p>";
    
    if ($sshReturn === 0) {
        // Test 3: Try SCP
        echo "<h4>Test SCP Upload:</h4>";
        
        $scpCommand = sprintf(
            'timeout 30 sshpass -p %s scp -o StrictHostKeyChecking=no -o ConnectTimeout=10 %s %s@%s:%s 2>&1',
            escapeshellarg($server['pass']),
            escapeshellarg($testFile),
            escapeshellarg($server['user']),
            escapeshellarg($server['host']),
            escapeshellarg($remotePath)
        );
        
        $startTime = microtime(true);
        exec($scpCommand, $scpOutput, $scpReturn);
        $endTime = microtime(true);
        
        echo "<p>Time taken: " . round($endTime - $startTime, 2) . " seconds</p>";
        echo "<p>Return code: $scpReturn</p>";
        echo "<p>Output: <pre>" . htmlspecialchars(implode("\n", $scpOutput)) . "</pre></p>";
        
        if ($scpReturn === 0) {
            echo "<p style='color: green; font-weight: bold;'>✓ SCP SUCCESS</p>";
            
            // Cleanup
            $rmCommand = sprintf(
                'sshpass -p %s ssh -o StrictHostKeyChecking=no %s@%s "rm -f %s" 2>&1',
                escapeshellarg($server['pass']),
                escapeshellarg($server['user']),
                escapeshellarg($server['host']),
                escapeshellarg($remotePath)
            );
            exec($rmCommand);
            echo "<p>Test file removed from remote server</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>✗ SCP FAILED</p>";
        }
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ SSH CONNECTION FAILED - Skipping SCP test</p>";
    }
}

// Cleanup local test file
unlink($testFile);
echo "<hr>";
echo "<p>Local test file deleted</p>";
?>