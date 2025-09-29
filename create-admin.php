<?php
// Script to create admin user if it doesn't exist
echo "<h2>🔧 Admin User Setup</h2>";

// Database configuration
$host = 'localhost';
$dbname = 'u955765309_spin';
$username = 'u955765309_wheel';
$password = 'Shoot@Order#123$';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful!<br>";
    
    // Check if admin_users table exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'admin_users'");
    if ($checkTable->rowCount() == 0) {
        echo "❌ admin_users table doesn't exist. Creating it...<br>";
        
        $createUsersTable = "
            CREATE TABLE admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL DEFAULT 'admin@oasisindia.in',
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $pdo->exec($createUsersTable);
        echo "✅ admin_users table created!<br>";
    } else {
        echo "✅ admin_users table exists<br>";
    }
    
    // Check if admin user exists
    $checkAdmin = $pdo->prepare("SELECT id, username FROM admin_users WHERE username = ?");
    $checkAdmin->execute(['admin']);
    $existingAdmin = $checkAdmin->fetch();
    
    if ($existingAdmin) {
        echo "⚠️ Admin user already exists (ID: {$existingAdmin['id']})<br>";
        echo "🔄 Updating password to 'admin123'...<br>";
        
        // Update the password
        $newPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $updatePassword = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE username = ?");
        $updatePassword->execute([$newPassword, 'admin']);
        
        echo "✅ Admin password updated!<br>";
    } else {
        echo "➕ Creating new admin user...<br>";
        
        // Create admin user
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $insertAdmin = $pdo->prepare("INSERT INTO admin_users (username, email, password_hash) VALUES (?, ?, ?)");
        $insertAdmin->execute(['admin', 'admin@oasisindia.in', $defaultPassword]);
        
        echo "✅ Admin user created!<br>";
    }
    
    // Show all admin users
    echo "<h3>📋 Current Admin Users:</h3>";
    $allAdmins = $pdo->query("SELECT id, username, email, created_at FROM admin_users");
    $users = $allAdmins->fetchAll();
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Created</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test password verification
    echo "<h3>🔐 Password Test:</h3>";
    $testUser = $pdo->prepare("SELECT password_hash FROM admin_users WHERE username = ?");
    $testUser->execute(['admin']);
    $userRecord = $testUser->fetch();
    
    if ($userRecord && password_verify('admin123', $userRecord['password_hash'])) {
        echo "✅ Password verification successful!<br>";
    } else {
        echo "❌ Password verification failed!<br>";
    }
    
    echo "<h3>🎯 Login Information:</h3>";
    echo "<strong>Username:</strong> admin<br>";
    echo "<strong>Password:</strong> admin123<br>";
    echo "<strong>Login URL:</strong> <a href='login.php'>login.php</a><br>";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}
?>