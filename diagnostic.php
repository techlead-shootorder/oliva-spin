<?php
echo "🔍 Oasis Spin Diagnostic Tool\n";
echo "==========================\n\n";

// Check if config.php exists
if (file_exists('api/config.php')) {
    echo "✅ Config file exists: api/config.php\n";
    
    // Try to read the config
    $config = file_get_contents('api/config.php');
    if (preg_match('/\$host = \'([^\']+)\';/', $config, $matches)) {
        echo "📍 Database Host: " . $matches[1] . "\n";
    }
    if (preg_match('/\$dbname = \'([^\']+)\';/', $config, $matches)) {
        echo "📊 Database Name: " . $matches[1] . "\n";
    }
    if (preg_match('/\$username = \'([^\']+)\';/', $config, $matches)) {
        echo "👤 Database User: " . $matches[1] . "\n";
    }
    
    echo "\n";
    
    // Test database connection
    echo "🔌 Testing database connection...\n";
    try {
        require_once 'api/config.php';
        echo "✅ Database connection successful!\n";
        
        // Check if admin_users table exists
        $checkTable = $pdo->query("SHOW TABLES LIKE 'admin_users'");
        if ($checkTable->rowCount() > 0) {
            echo "✅ admin_users table exists\n";
            
            // Check if admin user exists
            $stmt = $pdo->prepare("SELECT username FROM admin_users");
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            if (count($users) > 0) {
                echo "✅ Found " . count($users) . " admin user(s):\n";
                foreach ($users as $user) {
                    echo "   - " . $user['username'] . "\n";
                }
            } else {
                echo "❌ No admin users found in database\n";
            }
        } else {
            echo "❌ admin_users table does not exist\n";
        }
        
        // Check all tables
        echo "\n📋 Database tables:\n";
        $tables = $pdo->query("SHOW TABLES")->fetchAll();
        foreach ($tables as $table) {
            echo "   - " . $table[0] . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "\n";
        echo "\n🔧 TROUBLESHOOTING:\n";
        echo "1. Check if your database server is running\n";
        echo "2. Verify database credentials in api/config.php\n";
        echo "3. Make sure the database exists on your server\n";
        echo "4. Run setup.php to configure the application\n";
    }
} else {
    echo "❌ Config file missing: api/config.php\n";
    echo "Please run setup.php to configure the application\n";
}

echo "\n🎯 Next Steps:\n";
echo "1. If database connection failed, run setup.php\n";
echo "2. If admin users missing, the setup wasn't completed\n";
echo "3. Try logging in with username: admin, password: admin123\n";
echo "4. If still having issues, check the web server error logs\n";
?>