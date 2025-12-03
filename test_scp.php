// test_scp.php - criar este arquivo para debug
<?php
require_once 'config/database.php';

echo "<h1>SCP Configuration Test</h1>";

// Test 1: Check SSH2 extension
echo "<h2>1. SSH2 Extension</h2>";
if (extension_loaded('ssh2')) {
    echo "<p style='color: green;'>✓ SSH2 extension is loaded</p>";
} else {
    echo "<p style='color: red;'>✗ SSH2 extension is NOT loaded</p>";
    echo "<p>Install with: <code>sudo yum install php-ssh2</code> or <code>sudo yum install php74-php-pecl-ssh2</code></p>";
}

// Test 2: Check sshpass
echo "<h2>2. SSHPASS Command</h2>";
exec('which sshpass', $output, $returnVar);
if ($returnVar === 0 && !empty($output)) {
    echo "<p style='color: green;'>✓ sshpass is installed at: " . htmlspecialchars($output[0]) . "</p>";
} else {
    echo "<p style='color: red;'>✗ sshpass is NOT installed</p>";
    echo "<p>Install with: <code>sudo yum install sshpass</code></p>";
}

// Test 3: Check PHP functions
echo "<h2>3. Required PHP Functions</h2>";
$functions = ['exec', 'shell_exec', 'escapeshellarg'];
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "<p style='color: green;'>✓ $func() is available</p>";
    } else {
        echo "<p style='color: red;'>✗ $func() is disabled</p>";
    }
}

// Test 4: Test SCP connection
echo "<h2>4. Test SCP Connection</h2>";

$servers = Env::getRemoteServers();
if (empty($servers)) {
    echo "<p style='color: red;'>No remote servers configured in .env</p>";
} else {
    foreach ($servers as $index => $server) {
        echo "<h3>Server " . ($index + 1) . ": {$server['host']}</h3>";
        
        // Create test file
        $testFile = sys_get_temp_dir() . '/test_scp_' . time() . '.txt';
        file_put_contents($testFile, 'SCP Test - ' . date('Y-m-d H:i:s'));
        
        $remotePath = $server['path'] . 'test_scp_' . time() . '.txt';
        
        // Try with sshpass
        $command = sprintf(
            'sshpass -p %s scp -o StrictHostKeyChecking=no %s %s@%s:%s 2>&1',
            escapeshellarg($server['pass']),
            escapeshellarg($testFile),
            escapeshellarg($server['user']),
            escapeshellarg($server['host']),
            escapeshellarg($remotePath)
        );
        
        echo "<p><strong>Command:</strong></p>";
        echo "<pre style='background: #f5f5f5; padding: 10px;'>" . htmlspecialchars(str_replace($server['pass'], '****', $command)) . "</pre>";
        
        exec($command, $output, $returnVar);
        
        if ($returnVar === 0) {
            echo "<p style='color: green;'>✓ SCP upload successful!</p>";
            
            // Try to remove test file
            $removeCmd = sprintf(
                'sshpass -p %s ssh -o StrictHostKeyChecking=no %s@%s "rm -f %s" 2>&1',
                escapeshellarg($server['pass']),
                escapeshellarg($server['user']),
                escapeshellarg($server['host']),
                escapeshellarg($remotePath)
            );
            exec($removeCmd);
            echo "<p>Test file removed from remote server</p>";
        } else {
            echo "<p style='color: red;'>✗ SCP upload failed (Return code: $returnVar)</p>";
            if (!empty($output)) {
                echo "<p><strong>Error output:</strong></p>";
                echo "<pre style='background: #ffe6e6; padding: 10px;'>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
            }
        }
        
        // Cleanup local test file
        unlink($testFile);
        
        echo "<hr>";
    }
}

echo "<h2>5. PHP Info (SSH2 Section)</h2>";
ob_start();
phpinfo(INFO_MODULES);
$phpinfo = ob_get_clean();

if (preg_match('/SSH2.*?<\/table>/s', $phpinfo, $matches)) {
    echo $matches[0];
} else {
    echo "<p>No SSH2 information found in phpinfo</p>";
}
?>