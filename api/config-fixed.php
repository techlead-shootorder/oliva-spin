<?php
// Database configuration
$host = 'localhost';
$dbname = 'u955765309_spin';
$username = 'u955765309_wheel';
$password = 'Shoot@Order#123$';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables if they don't exist
    $createSpinsTable = "
        CREATE TABLE IF NOT EXISTS spins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recorded_id VARCHAR(255) NOT NULL,
            result VARCHAR(255) NOT NULL,
            timestamp BIGINT NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_recorded_id (recorded_id),
            INDEX idx_timestamp (timestamp)
        )
    ";
    
    $createCouponsTable = "
        CREATE TABLE IF NOT EXISTS coupons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            discount_text VARCHAR(255) NOT NULL,
            discount_value INT NOT NULL,
            coupon_code VARCHAR(50) NOT NULL UNIQUE,
            week1_probability DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            week2_probability DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            week3_probability DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            week4_probability DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            color VARCHAR(7) NOT NULL DEFAULT '#667eea',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    
    $createSettingsTable = "
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    
    $createUsersTable = "
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL DEFAULT 'admin@oasisindia.in',
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    $createUserSpinsTable = "
        CREATE TABLE IF NOT EXISTS user_spins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recorded_id VARCHAR(255) NOT NULL,
            spin_count INT NOT NULL DEFAULT 0,
            last_spin_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_recorded_id (recorded_id)
        )
    ";
    
    $createWeeklySettingsTable = "
        CREATE TABLE IF NOT EXISTS weekly_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            current_week INT NOT NULL DEFAULT 1,
            week_start_date DATE NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    
    $pdo->exec($createSpinsTable);
    $pdo->exec($createCouponsTable);
    $pdo->exec($createSettingsTable);
    $pdo->exec($createUsersTable);
    $pdo->exec($createUserSpinsTable);
    $pdo->exec($createWeeklySettingsTable);
    
    // Insert default admin user if not exists
    $checkAdmin = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ?");
    $checkAdmin->execute(['admin']);
    
    if ($checkAdmin->fetchColumn() == 0) {
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $insertAdmin = $pdo->prepare("INSERT INTO admin_users (username, email, password_hash) VALUES (?, ?, ?)");
        $insertAdmin->execute(['admin', 'admin@oasisindia.in', $defaultPassword]);
    }
    
    // Insert default coupons if not exists
    $checkCoupons = $pdo->prepare("SELECT COUNT(*) FROM coupons");
    $checkCoupons->execute();
    
    if ($checkCoupons->fetchColumn() == 0) {
        $defaultCoupons = [
            ['10K Discount', 10001, 'OASIS10K', 65.00, 65.00, 65.00, 65.00, '#ef4444'],
            ['15K Discount', 15001, 'OASIS15K', 22.00, 23.00, 22.00, 23.00, '#f97316'],
            ['20K Discount', 20001, 'OASIS20K', 5.00, 5.00, 5.00, 5.00, '#eab308'],
            ['50K Discount', 50001, 'OASIS50K', 3.00, 3.00, 3.00, 3.00, '#22c55e'],
            ['1 Lakh Discount', 100001, 'OASIS1L', 2.00, 2.00, 2.00, 2.00, '#3b82f6'],
            ['Free IVF', 200001, 'OASISIVF', 3.00, 2.00, 3.00, 2.00, '#8b5cf6']
        ];
        
        $insertCoupon = $pdo->prepare("INSERT INTO coupons (discount_text, discount_value, coupon_code, week1_probability, week2_probability, week3_probability, week4_probability, color) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($defaultCoupons as $coupon) {
            $insertCoupon->execute($coupon);
        }
    }
    
    // Insert default settings if not exists
    $defaultSettings = [
        ['wheel_title', '🎯 Oasis Spin Wheel'],
        ['wheel_description', 'Spin once and win amazing discounts on IVF treatments!'],
        ['max_spins', '1'],
        ['enable_tracking', '1']
    ];
    
    // Initialize weekly settings if not exists
    $checkWeekly = $pdo->prepare("SELECT COUNT(*) FROM weekly_settings");
    $checkWeekly->execute();
    
    if ($checkWeekly->fetchColumn() == 0) {
        $insertWeekly = $pdo->prepare("INSERT INTO weekly_settings (current_week, week_start_date) VALUES (?, ?)");
        $insertWeekly->execute([1, date('Y-m-d')]);
    }
    
    $checkSetting = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
    $insertSetting = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
    
    foreach ($defaultSettings as $setting) {
        $checkSetting->execute([$setting[0]]);
        if ($checkSetting->fetchColumn() == 0) {
            $insertSetting->execute($setting);
        }
    }
    
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}

// Helper function to get client IP
function getClientIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
               'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 
               'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// Helper function to send JSON response
function sendJsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Helper function to get current week number
function getCurrentWeek() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT current_week, week_start_date FROM weekly_settings ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        $weekStart = new DateTime($result['week_start_date']);
        $now = new DateTime();
        $daysSinceStart = $now->diff($weekStart)->days;
        
        // Auto-update week if more than 7 days have passed
        if ($daysSinceStart >= 7) {
            $newWeek = ($result['current_week'] % 4) + 1;
            $updateStmt = $pdo->prepare("UPDATE weekly_settings SET current_week = ?, week_start_date = ? WHERE id = (SELECT id FROM (SELECT id FROM weekly_settings ORDER BY id DESC LIMIT 1) AS temp)");
            $updateStmt->execute([$newWeek, $now->format('Y-m-d')]);
            return $newWeek;
        }
        
        return $result['current_week'];
    }
    
    return 1; // Default to week 1
}

// Helper function to get coupon probabilities for current week
function getCouponProbabilities() {
    global $pdo;
    $currentWeek = getCurrentWeek();
    $weekColumn = "week{$currentWeek}_probability";
    
    $stmt = $pdo->prepare("SELECT *, $weekColumn as probability FROM coupons WHERE is_active = 1 ORDER BY probability DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Helper function to select winning coupon based on probability
function selectWinningCoupon() {
    $coupons = getCouponProbabilities();
    
    if (empty($coupons)) {
        return null;
    }
    
    $random = mt_rand(1, 10000) / 100; // Random float between 0.01 and 100.00
    $cumulative = 0;
    
    foreach ($coupons as $coupon) {
        $cumulative += $coupon['probability'];
        if ($random <= $cumulative) {
            return $coupon;
        }
    }
    
    // Fallback to first coupon if no match (shouldn't happen with proper probabilities)
    return $coupons[0];
}

// Helper function to check if user can spin
function canUserSpin($recordedId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT spin_count FROM user_spins WHERE recorded_id = ?");
    $stmt->execute([$recordedId]);
    $result = $stmt->fetch();
    
    if (!$result) {
        // First time user
        $insertStmt = $pdo->prepare("INSERT INTO user_spins (recorded_id, spin_count) VALUES (?, 0)");
        $insertStmt->execute([$recordedId]);
        return true;
    }
    
    return $result['spin_count'] < 1; // Allow only 1 spin per user
}

// Helper function to increment user spin count
function incrementUserSpin($recordedId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE user_spins SET spin_count = spin_count + 1, last_spin_at = CURRENT_TIMESTAMP WHERE recorded_id = ?");
    $stmt->execute([$recordedId]);
}
?>