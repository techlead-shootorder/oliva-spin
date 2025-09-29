<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Debug - Oasis Spin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .debug-section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        pre { background: #eee; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 5px; }
        .btn-danger { background: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Oasis Spin Setup Debug Tool</h1>
        
        <div class="debug-section">
            <h2>📋 Current Session Data</h2>
            <?php if (isset($_SESSION['db_config'])): ?>
                <div class="success">✅ Database config found in session</div>
                <pre><?php print_r($_SESSION['db_config']); ?></pre>
            <?php else: ?>
                <div class="error">❌ No database config in session</div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['admin_config'])): ?>
                <div class="success">✅ Admin config found in session</div>
                <pre>Username: <?php echo $_SESSION['admin_config']['username']; ?>
Email: <?php echo $_SESSION['admin_config']['email']; ?>
Password: [HIDDEN]</pre>
            <?php else: ?>
                <div class="error">❌ No admin config in session</div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['site_config'])): ?>
                <div class="success">✅ Site config found in session</div>
                <pre><?php print_r($_SESSION['site_config']); ?></pre>
            <?php else: ?>
                <div class="error">❌ No site config in session</div>
            <?php endif; ?>
        </div>
        
        <div class="debug-section">
            <h2>🔌 Database Connection Test</h2>
            <?php if (isset($_SESSION['db_config'])): ?>
                <?php
                $dbConfig = $_SESSION['db_config'];
                echo "<div class='info'>Testing connection to: {$dbConfig['host']}/{$dbConfig['dbname']} as {$dbConfig['username']}</div>";
                
                try {
                    $testPdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4", $dbConfig['username'], $dbConfig['password']);
                    $testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    echo "<div class='success'>✅ Database connection successful!</div>";
                    
                    // Test basic query
                    $result = $testPdo->query("SELECT 1 as test");
                    echo "<div class='success'>✅ Database query test successful!</div>";
                    
                    // Show database info
                    $version = $testPdo->query("SELECT VERSION() as version")->fetch();
                    echo "<div class='info'>Database version: {$version['version']}</div>";
                    
                } catch (PDOException $e) {
                    echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
                    echo "<div class='warning'>⚠️ This is why your setup is failing at step 4!</div>";
                }
                ?>
            <?php else: ?>
                <div class="warning">⚠️ No database config to test</div>
            <?php endif; ?>
        </div>
        
        <div class="debug-section">
            <h2>📁 File System Check</h2>
            <?php
            $configPath = 'api/config.php';
            if (file_exists($configPath)) {
                echo "<div class='success'>✅ Config file exists: $configPath</div>";
                if (is_writable($configPath)) {
                    echo "<div class='success'>✅ Config file is writable</div>";
                } else {
                    echo "<div class='error'>❌ Config file is not writable</div>";
                }
            } else {
                echo "<div class='warning'>⚠️ Config file doesn't exist: $configPath</div>";
                if (is_writable('api/')) {
                    echo "<div class='success'>✅ API directory is writable</div>";
                } else {
                    echo "<div class='error'>❌ API directory is not writable</div>";
                }
            }
            ?>
        </div>
        
        <div class="debug-section">
            <h2>🔧 Actions</h2>
            <a href="setup.php?step=1&force=1" class="btn">🔄 Restart Setup</a>
            <a href="setup.php?step=2" class="btn">↩️ Go to Step 2 (Database)</a>
            <a href="?clear_session=1" class="btn btn-danger">🗑️ Clear Session Data</a>
            
            <?php if (isset($_GET['clear_session'])): ?>
                <?php
                session_destroy();
                echo "<div class='success'>✅ Session data cleared!</div>";
                echo "<script>setTimeout(function(){ window.location.href = 'setup.php?step=1'; }, 2000);</script>";
                ?>
            <?php endif; ?>
        </div>
        
        <div class="debug-section">
            <h2>💡 Troubleshooting Tips</h2>
            <ul>
                <li><strong>If database connection fails:</strong> Check your hosting control panel for correct MySQL credentials</li>
                <li><strong>If config file issues:</strong> Check file permissions (should be 644 or 755)</li>
                <li><strong>If session issues:</strong> Clear session data and restart setup</li>
                <li><strong>Common hosting database names:</strong> Usually start with your username (e.g., username_dbname)</li>
            </ul>
        </div>
        
        <div class="debug-section">
            <h2>🎯 Next Steps</h2>
            <p>Based on the debug results above:</p>
            <ol>
                <li>If database connection failed, go back to Step 2 and enter correct credentials</li>
                <li>If file permissions are wrong, contact your hosting provider</li>
                <li>If session data is missing, restart the setup process</li>
                <li>Check your hosting control panel for MySQL database details</li>
            </ol>
        </div>
    </div>
</body>
</html>