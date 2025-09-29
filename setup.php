<?php
// WordPress-style setup wizard for Oasis Spin Wheel
session_start();

// Check if config already exists
$configExists = file_exists('api/config.php');

// Setup steps
$currentStep = $_GET['step'] ?? 1;
$maxStep = 5;

// If config exists and no specific step requested, show completion
if ($configExists && !isset($_GET['force']) && !isset($_GET['step'])) {
    $currentStep = 5;
}

// Initialize variables
$error = '';
$success = '';
$dbConnected = false;

// Handle form submissions
if ($_POST) {
    // Debug: Log what's happening
    error_log("Form submitted - Current step: $currentStep, POST data: " . print_r(array_keys($_POST), true));
    
    switch ($currentStep) {
        case 2: // Database configuration
            // Check if user wants to force continue with existing database
            if (isset($_POST['force_continue']) && isset($_SESSION['suggested_db'])) {
                // Use the suggested database and continue
                $_SESSION['db_config'] = $_SESSION['suggested_db'];
                $success = "Proceeding with database '{$_SESSION['suggested_db']['dbname']}'.";
                $currentStep = 3;
            } else {
                handleDatabaseConfig();
            }
            break;
        case 3: // Admin account setup
            handleAdminSetup();
            break;
        case 4: // Site settings
            handleSiteSettings();
            break;
        default:
            error_log("Unknown step: $currentStep");
            $error = "Invalid step: $currentStep";
            break;
    }
}

function handleDatabaseConfig() {
    global $error, $success, $dbConnected, $currentStep;
    
    $host = trim($_POST['db_host'] ?? '');
    $dbname = trim($_POST['db_name'] ?? '');
    $username = trim($_POST['db_username'] ?? '');
    $password = $_POST['db_password'] ?? '';
    
    // Debug: Let's see what's being submitted
    error_log("Database config attempt - Host: '$host', DB: '$dbname', User: '$username', Pass length: " . strlen($password));
    
    if (empty($host) || empty($dbname) || empty($username)) {
        $error = "Please fill in all required database fields. Missing: " . 
                (empty($host) ? "Host " : "") . 
                (empty($dbname) ? "Database " : "") . 
                (empty($username) ? "Username " : "");
        return;
    }
    
    try {
        // First, try to connect to the specified database
        $testPdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Store database config in session
        $_SESSION['db_config'] = [
            'host' => $host,
            'dbname' => $dbname,
            'username' => $username,
            'password' => $password
        ];
        
        $dbConnected = true;
        $success = 'Database connection successful! Proceeding to next step.';
        $currentStep = 3;
        
    } catch (PDOException $e) {
        // Check if it's an access denied error to specific database
        if (strpos($e->getMessage(), 'Access denied') !== false && strpos($e->getMessage(), 'to database') !== false) {
            try {
                // Try to connect without specifying database to test user credentials
                $testPdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
                $testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Get list of databases the user has access to
                $stmt = $testPdo->query("SHOW DATABASES");
                $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Try to create the database if user has privileges
                $dbCreated = false;
                try {
                    $testPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    
                    // Test connection to the newly created database
                    $newTestPdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
                    $newTestPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    $dbCreated = true;
                    $success = "Database '$dbname' created successfully! Proceeding to next step.";
                    
                } catch (PDOException $createError) {
                    // If we can't create the database, suggest using an existing one
                    $userDatabases = array_filter($databases, function($db) {
                        return !in_array($db, ['information_schema', 'performance_schema', 'mysql', 'sys']);
                    });
                    
                    if (!empty($userDatabases)) {
                        $suggestedDb = $userDatabases[0];
                        
                        // Store suggested database config for potential use
                        $_SESSION['suggested_db'] = [
                            'host' => $host,
                            'dbname' => $suggestedDb,
                            'username' => $username,
                            'password' => $password
                        ];
                        
                        $error = "Cannot access database '$dbname'. However, user '$username' has access to these databases: " . 
                                implode(', ', $userDatabases) . ". Click 'Continue with $suggestedDb' below or contact your hosting provider to create '$dbname'.";
                    } else {
                        $error = "User '$username' exists but has no database privileges. Please contact your hosting provider to create database '$dbname' and grant access to user '$username'.";
                    }
                }
                
                if ($dbCreated) {
                    // Store database config in session
                    $_SESSION['db_config'] = [
                        'host' => $host,
                        'dbname' => $dbname,
                        'username' => $username,
                        'password' => $password
                    ];
                    
                    $dbConnected = true;
                    $currentStep = 3;
                } else {
                    // Even if we can't create the database, if user credentials work, 
                    // let them proceed and try with their suggested database
                    if (!empty($userDatabases)) {
                        $_SESSION['db_config'] = [
                            'host' => $host,
                            'dbname' => $userDatabases[0], // Use the first available database
                            'username' => $username,
                            'password' => $password
                        ];
                        
                        $dbConnected = true;
                        $currentStep = 3;
                        $success = "Using database '{$userDatabases[0]}' for installation. You can change this later if needed.";
                        $error = ''; // Clear the error since we're proceeding
                    }
                }
                
            } catch (PDOException $userError) {
                $error = 'Database user authentication failed: ' . $userError->getMessage();
            }
        } else {
            $error = 'Database connection failed: ' . $e->getMessage();
        }
    }
}

function handleAdminSetup() {
    global $error, $success, $currentStep;
    
    $adminUsername = trim($_POST['admin_username'] ?? '');
    $adminPassword = $_POST['admin_password'] ?? '';
    $adminPasswordConfirm = $_POST['admin_password_confirm'] ?? '';
    $adminEmail = trim($_POST['admin_email'] ?? '');
    
    // Debug: Let's see what's being submitted
    error_log("Admin setup attempt - Username: '$adminUsername', Email: '$adminEmail', Pass length: " . strlen($adminPassword) . ", Confirm length: " . strlen($adminPasswordConfirm));
    
    if (empty($adminUsername) || empty($adminPassword) || empty($adminEmail)) {
        $error = "Please fill in all admin account fields. Missing: " . 
                (empty($adminUsername) ? "Username " : "") . 
                (empty($adminPassword) ? "Password " : "") . 
                (empty($adminEmail) ? "Email " : "");
        return;
    }
    
    if ($adminPassword !== $adminPasswordConfirm) {
        $error = 'Password confirmation does not match.';
        return;
    }
    
    if (strlen($adminPassword) < 6) {
        $error = 'Password must be at least 6 characters long.';
        return;
    }
    
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
        return;
    }
    
    // Store admin config in session
    $_SESSION['admin_config'] = [
        'username' => $adminUsername,
        'password' => $adminPassword,
        'email' => $adminEmail
    ];
    
    $success = 'Admin account configured! Proceeding to site settings.';
    $currentStep = 4;
}

function handleSiteSettings() {
    global $error, $success, $currentStep;
    
    $siteTitle = trim($_POST['site_title'] ?? '');
    $siteDescription = trim($_POST['site_description'] ?? '');
    $siteUrl = trim($_POST['site_url'] ?? '');
    
    if (empty($siteTitle) || empty($siteDescription)) {
        $error = 'Please fill in the site title and description.';
        return;
    }
    
    // Store site config in session
    $_SESSION['site_config'] = [
        'title' => $siteTitle,
        'description' => $siteDescription,
        'url' => $siteUrl
    ];
    
    // Install everything
    if (installApplication()) {
        $success = 'Installation completed successfully!';
        
        // Clear session data
        unset($_SESSION['db_config']);
        unset($_SESSION['admin_config']);
        unset($_SESSION['site_config']);
        
        // Redirect to success page
        header('Location: setup.php?step=5');
        exit;
    } else {
        $error = 'Installation failed. Please check the error logs.';
    }
}

function installApplication() {
    global $error;
    
    try {
        if (!isset($_SESSION['db_config']) || !isset($_SESSION['admin_config']) || !isset($_SESSION['site_config'])) {
            throw new Exception('Missing configuration data in session');
        }
        
        $dbConfig = $_SESSION['db_config'];
        $adminConfig = $_SESSION['admin_config'];
        $siteConfig = $_SESSION['site_config'];
        
        // Log the configuration being used
        error_log("Installing with database: " . $dbConfig['host'] . "/" . $dbConfig['dbname'] . " user: " . $dbConfig['username']);
        
        // Test database connection before generating config
        try {
            $testPdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4", $dbConfig['username'], $dbConfig['password']);
            $testPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            error_log("Database connection test successful during installation");
        } catch (PDOException $e) {
            throw new Exception('Database connection failed during installation: ' . $e->getMessage());
        }
        
        // Create config.php file
        $configContent = generateConfigFile($dbConfig, $adminConfig, $siteConfig);
        
        if (!file_put_contents('api/config.php', $configContent)) {
            throw new Exception('Failed to write configuration file. Check directory permissions.');
        }
        
        error_log("Config file written successfully");
        
        // Include the new config to set up database
        require_once 'api/config.php';
        
        error_log("Installation completed successfully");
        return true;
        
    } catch (Exception $e) {
        error_log('Installation error: ' . $e->getMessage());
        $error = 'Installation failed: ' . $e->getMessage();
        return false;
    }
}

function generateConfigFile($dbConfig, $adminConfig, $siteConfig) {
    $escapedPassword = addslashes($dbConfig['password']);
    $escapedHost = addslashes($dbConfig['host']);
    $escapedDbname = addslashes($dbConfig['dbname']);
    $escapedUsername = addslashes($dbConfig['username']);
    $adminPasswordHash = password_hash($adminConfig['password'], PASSWORD_DEFAULT);
    $escapedAdminUsername = addslashes($adminConfig['username']);
    $escapedAdminEmail = addslashes($adminConfig['email']);
    $escapedSiteTitle = addslashes($siteConfig['title']);
    $escapedSiteDescription = addslashes($siteConfig['description']);
    $escapedSiteUrl = addslashes($siteConfig['url']);
    
    return "<?php
// Database configuration - Generated by Oasis Spin Setup Wizard
\$host = '$escapedHost';
\$dbname = '$escapedDbname';
\$username = '$escapedUsername';
\$password = '$escapedPassword';

try {
    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\", \$username, \$password);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables if they don't exist
    \$createSpinsTable = \"
        CREATE TABLE IF NOT EXISTS spins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recorded_id VARCHAR(255) NOT NULL,
            result VARCHAR(255) NOT NULL,
            timestamp BIGINT NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_recorded_id (recorded_id),
            INDEX idx_timestamp (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    \";
    
    \$createCouponsTable = \"
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    \";
    
    \$createSettingsTable = \"
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    \";
    
    \$createUsersTable = \"
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    \";
    
    \$createUserSpinsTable = \"
        CREATE TABLE IF NOT EXISTS user_spins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recorded_id VARCHAR(255) NOT NULL,
            spin_count INT NOT NULL DEFAULT 0,
            last_spin_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_recorded_id (recorded_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    \";
    
    \$createWeeklySettingsTable = \"
        CREATE TABLE IF NOT EXISTS weekly_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            current_week INT NOT NULL DEFAULT 1,
            week_start_date DATE NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    \";
    
    // Execute table creation
    \$pdo->exec(\$createSpinsTable);
    \$pdo->exec(\$createCouponsTable);
    \$pdo->exec(\$createSettingsTable);
    \$pdo->exec(\$createUsersTable);
    \$pdo->exec(\$createUserSpinsTable);
    \$pdo->exec(\$createWeeklySettingsTable);
    
    // Insert admin user
    \$insertAdmin = \$pdo->prepare(\"INSERT INTO admin_users (username, email, password_hash) VALUES (?, ?, ?)\");
    \$insertAdmin->execute(['$escapedAdminUsername', '$escapedAdminEmail', '$adminPasswordHash']);
    
    // Insert default coupons
    \$defaultCoupons = [
        ['10K Discount', 10001, 'OASIS10K', 65.00, 65.00, 65.00, 65.00, '#ef4444'],
        ['15K Discount', 15001, 'OASIS15K', 22.00, 23.00, 22.00, 23.00, '#f97316'],
        ['20K Discount', 20001, 'OASIS20K', 5.00, 5.00, 5.00, 5.00, '#eab308'],
        ['50K Discount', 50001, 'OASIS50K', 3.00, 3.00, 3.00, 3.00, '#22c55e'],
        ['1 Lakh Discount', 100001, 'OASIS1L', 2.00, 2.00, 2.00, 2.00, '#3b82f6'],
        ['Free IVF', 200001, 'OASISIVF', 3.00, 2.00, 3.00, 2.00, '#8b5cf6']
    ];
    
    \$insertCoupon = \$pdo->prepare(\"INSERT INTO coupons (discount_text, discount_value, coupon_code, week1_probability, week2_probability, week3_probability, week4_probability, color) VALUES (?, ?, ?, ?, ?, ?, ?, ?)\");
    foreach (\$defaultCoupons as \$coupon) {
        \$insertCoupon->execute(\$coupon);
    }
    
    // Insert default settings
    \$defaultSettings = [
        ['wheel_title', '$escapedSiteTitle'],
        ['wheel_description', '$escapedSiteDescription'],
        ['site_url', '$escapedSiteUrl'],
        ['max_spins', '1'],
        ['enable_tracking', '1'],
        ['installation_date', '" . date('Y-m-d H:i:s') . "']
    ];
    
    \$insertSetting = \$pdo->prepare(\"INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)\");
    foreach (\$defaultSettings as \$setting) {
        \$insertSetting->execute(\$setting);
    }
    
    // Initialize weekly settings
    \$insertWeekly = \$pdo->prepare(\"INSERT INTO weekly_settings (current_week, week_start_date) VALUES (?, ?)\");
    \$insertWeekly->execute([1, date('Y-m-d')]);
    
} catch (PDOException \$e) {
    error_log(\"Database error: \" . \$e->getMessage());
    die(\"Database connection failed. Please check your configuration.\");
}

// Site configuration
define('SITE_TITLE', '$escapedSiteTitle');
define('SITE_DESCRIPTION', '$escapedSiteDescription');
define('SITE_URL', '$escapedSiteUrl');

// Helper functions
function getClientIP() {
    \$ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
               'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 
               'REMOTE_ADDR'];
    
    foreach (\$ipKeys as \$key) {
        if (array_key_exists(\$key, \$_SERVER) === true) {
            foreach (explode(',', \$_SERVER[\$key]) as \$ip) {
                \$ip = trim(\$ip);
                if (filter_var(\$ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return \$ip;
                }
            }
        }
    }
    
    return \$_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function sendJsonResponse(\$data, \$status = 200) {
    http_response_code(\$status);
    header('Content-Type: application/json');
    echo json_encode(\$data);
    exit;
}

function getCurrentWeek() {
    global \$pdo;
    \$stmt = \$pdo->prepare(\"SELECT current_week, week_start_date FROM weekly_settings ORDER BY id DESC LIMIT 1\");
    \$stmt->execute();
    \$result = \$stmt->fetch();
    
    if (\$result) {
        \$weekStart = new DateTime(\$result['week_start_date']);
        \$now = new DateTime();
        \$daysSinceStart = \$now->diff(\$weekStart)->days;
        
        if (\$daysSinceStart >= 7) {
            \$newWeek = (\$result['current_week'] % 4) + 1;
            \$updateStmt = \$pdo->prepare(\"UPDATE weekly_settings SET current_week = ?, week_start_date = ? WHERE id = (SELECT id FROM (SELECT id FROM weekly_settings ORDER BY id DESC LIMIT 1) AS temp)\");
            \$updateStmt->execute([\$newWeek, \$now->format('Y-m-d')]);
            return \$newWeek;
        }
        
        return \$result['current_week'];
    }
    
    return 1;
}

function getCouponProbabilities() {
    global \$pdo;
    \$currentWeek = getCurrentWeek();
    \$weekColumn = \"week{\$currentWeek}_probability\";
    
    \$stmt = \$pdo->prepare(\"SELECT *, \$weekColumn as probability FROM coupons WHERE is_active = 1 ORDER BY probability DESC\");
    \$stmt->execute();
    return \$stmt->fetchAll();
}

function selectWinningCoupon() {
    \$coupons = getCouponProbabilities();
    
    if (empty(\$coupons)) {
        return null;
    }
    
    \$random = mt_rand(1, 10000) / 100;
    \$cumulative = 0;
    
    foreach (\$coupons as \$coupon) {
        \$cumulative += \$coupon['probability'];
        if (\$random <= \$cumulative) {
            return \$coupon;
        }
    }
    
    return \$coupons[0];
}

function canUserSpin(\$recordedId) {
    global \$pdo;
    \$stmt = \$pdo->prepare(\"SELECT spin_count FROM user_spins WHERE recorded_id = ?\");
    \$stmt->execute([\$recordedId]);
    \$result = \$stmt->fetch();
    
    if (!\$result) {
        \$insertStmt = \$pdo->prepare(\"INSERT INTO user_spins (recorded_id, spin_count) VALUES (?, 0)\");
        \$insertStmt->execute([\$recordedId]);
        return true;
    }
    
    return \$result['spin_count'] < 1;
}

function incrementUserSpin(\$recordedId) {
    global \$pdo;
    \$stmt = \$pdo->prepare(\"UPDATE user_spins SET spin_count = spin_count + 1, last_spin_at = CURRENT_TIMESTAMP WHERE recorded_id = ?\");
    \$stmt->execute([\$recordedId]);
}
?>";
}

// Get current site URL for default
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['REQUEST_URI']);
$siteUrl = $protocol . '://' . $host . $path;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oasis Spin Wheel - Setup Wizard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.1);
        }
        
        .step-indicator {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }
        
        .step-active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .step-completed {
            background: #10b981;
            color: white;
        }
        
        .step-pending {
            background: #e5e7eb;
            color: #6b7280;
        }
        
        .step-line {
            flex: 1;
            height: 2px;
            background: #e5e7eb;
            margin: 0 16px;
        }
        
        .step-line.completed {
            background: #10b981;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 32px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 10px 32px;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-4xl">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="text-6xl mb-4">🎯</div>
                <h1 class="text-4xl font-bold text-gray-800 mb-2">Oasis Spin Wheel</h1>
                <p class="text-xl text-gray-600">Setup Wizard</p>
            </div>
            
            <!-- Progress Steps -->
            <div class="glass-card p-6 mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="step-indicator <?php echo $currentStep >= 1 ? ($currentStep > 1 ? 'step-completed' : 'step-active') : 'step-pending'; ?>">
                            <?php echo $currentStep > 1 ? '✓' : '1'; ?>
                        </div>
                        <span class="ml-3 font-medium text-gray-700">Welcome</span>
                    </div>
                    
                    <div class="step-line <?php echo $currentStep > 2 ? 'completed' : ''; ?>"></div>
                    
                    <div class="flex items-center">
                        <div class="step-indicator <?php echo $currentStep >= 2 ? ($currentStep > 2 ? 'step-completed' : 'step-active') : 'step-pending'; ?>">
                            <?php echo $currentStep > 2 ? '✓' : '2'; ?>
                        </div>
                        <span class="ml-3 font-medium text-gray-700">Database</span>
                    </div>
                    
                    <div class="step-line <?php echo $currentStep > 3 ? 'completed' : ''; ?>"></div>
                    
                    <div class="flex items-center">
                        <div class="step-indicator <?php echo $currentStep >= 3 ? ($currentStep > 3 ? 'step-completed' : 'step-active') : 'step-pending'; ?>">
                            <?php echo $currentStep > 3 ? '✓' : '3'; ?>
                        </div>
                        <span class="ml-3 font-medium text-gray-700">Admin Account</span>
                    </div>
                    
                    <div class="step-line <?php echo $currentStep > 4 ? 'completed' : ''; ?>"></div>
                    
                    <div class="flex items-center">
                        <div class="step-indicator <?php echo $currentStep >= 4 ? ($currentStep > 4 ? 'step-completed' : 'step-active') : 'step-pending'; ?>">
                            <?php echo $currentStep > 4 ? '✓' : '4'; ?>
                        </div>
                        <span class="ml-3 font-medium text-gray-700">Site Settings</span>
                    </div>
                    
                    <div class="step-line <?php echo $currentStep >= 5 ? 'completed' : ''; ?>"></div>
                    
                    <div class="flex items-center">
                        <div class="step-indicator <?php echo $currentStep >= 5 ? 'step-completed' : 'step-pending'; ?>">
                            <?php echo $currentStep >= 5 ? '✓' : '5'; ?>
                        </div>
                        <span class="ml-3 font-medium text-gray-700">Complete</span>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="glass-card p-8">
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-6">
                        <div class="flex">
                            <div class="text-xl mr-3">❌</div>
                            <div>
                                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg mb-6">
                        <div class="flex">
                            <div class="text-xl mr-3">✅</div>
                            <div>
                                <strong>Success:</strong> <?php echo htmlspecialchars($success); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($currentStep == 1): ?>
                    <!-- Step 1: Welcome -->
                    <div class="text-center">
                        <h2 class="text-3xl font-bold text-gray-800 mb-6">Welcome to Oasis Spin Wheel!</h2>
                        <div class="max-w-2xl mx-auto">
                            <p class="text-lg text-gray-600 mb-8">
                                Thank you for choosing Oasis Spin Wheel! This setup wizard will guide you through 
                                configuring your spinning wheel application with probability-based mechanics, 
                                admin panel, and comprehensive tracking system.
                            </p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                                <div class="p-6 bg-blue-50 rounded-lg">
                                    <div class="text-3xl mb-3">🎯</div>
                                    <h3 class="font-bold text-gray-800 mb-2">Spin Wheel</h3>
                                    <p class="text-sm text-gray-600">Interactive wheel with weekly probability variations</p>
                                </div>
                                
                                <div class="p-6 bg-green-50 rounded-lg">
                                    <div class="text-3xl mb-3">👤</div>
                                    <h3 class="font-bold text-gray-800 mb-2">Admin Panel</h3>
                                    <p class="text-sm text-gray-600">Complete management dashboard for coupons and analytics</p>
                                </div>
                                
                                <div class="p-6 bg-purple-50 rounded-lg">
                                    <div class="text-3xl mb-3">📊</div>
                                    <h3 class="font-bold text-gray-800 mb-2">Analytics</h3>
                                    <p class="text-sm text-gray-600">Track spins, users, and performance metrics</p>
                                </div>
                            </div>
                            
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
                                <h4 class="font-bold text-yellow-800 mb-2">Before you begin:</h4>
                                <ul class="text-left text-sm text-yellow-700 space-y-1">
                                    <li>• Ensure you have a MySQL database created</li>
                                    <li>• Have your database credentials ready</li>
                                    <li>• Choose a secure admin username and password</li>
                                    <li>• This setup will take approximately 2-3 minutes</li>
                                </ul>
                            </div>
                            
                            <a href="?step=2" class="btn-primary inline-block">
                                🚀 Start Setup
                            </a>
                        </div>
                    </div>

                <?php elseif ($currentStep == 2): ?>
                    <!-- Step 2: Database Configuration -->
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800 mb-6">Database Configuration</h2>
                        <p class="text-gray-600 mb-8">Please provide your MySQL database connection details. The database must already exist.</p>
                        
                        <form method="POST" action="?step=2" class="space-y-6">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Database Host <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="db_host" 
                                           value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>"
                                           placeholder="localhost" class="form-input" required>
                                    <p class="text-xs text-gray-500 mt-1">Usually 'localhost' for local installations</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Database Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="db_name" 
                                           value="<?php echo htmlspecialchars($_POST['db_name'] ?? 'oasispin'); ?>"
                                           placeholder="oasispin" class="form-input" required>
                                    <p class="text-xs text-gray-500 mt-1">The database must already exist</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Database Username <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="db_username" 
                                           value="<?php echo htmlspecialchars($_POST['db_username'] ?? ''); ?>"
                                           placeholder="root" class="form-input" required>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Database Password
                                    </label>
                                    <input type="password" name="db_password" 
                                           placeholder="Enter password (leave blank if none)" class="form-input">
                                </div>
                            </div>
                            
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                                <div class="flex">
                                    <div class="text-blue-500 mr-3">💡</div>
                                    <div class="text-sm text-blue-700">
                                        <strong>Database Tips:</strong>
                                        <ul class="mt-2 space-y-1">
                                            <li>• If the database doesn't exist, we'll try to create it automatically</li>
                                            <li>• For shared hosting, use the database name provided by your host</li>
                                            <li>• The username format is often like: username_dbname</li>
                                            <li>• Contact your hosting provider if you need help with database access</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-between pt-6">
                                <a href="?step=1" class="btn-secondary">← Back</a>
                                <div class="space-x-3">
                                    <?php if (isset($_SESSION['suggested_db'])): ?>
                                        <button type="submit" name="force_continue" value="1" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                                            Continue with <?php echo $_SESSION['suggested_db']['dbname']; ?> →
                                        </button>
                                    <?php endif; ?>
                                    <button type="submit" class="btn-primary">Test Connection & Continue →</button>
                                </div>
                            </div>
                        </form>
                    </div>

                <?php elseif ($currentStep == 3): ?>
                    <!-- Step 3: Admin Account -->
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800 mb-6">Create Admin Account</h2>
                        <p class="text-gray-600 mb-8">Set up your administrator account to manage the spin wheel and view analytics.</p>
                        
                        <form method="POST" action="?step=3" class="space-y-6">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Username <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="admin_username" 
                                           value="<?php echo htmlspecialchars($_POST['admin_username'] ?? 'admin'); ?>"
                                           placeholder="admin" class="form-input" required>
                                    <p class="text-xs text-gray-500 mt-1">Choose a unique username for admin login</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" name="admin_email" 
                                           value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>"
                                           placeholder="admin@example.com" class="form-input" required>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Password <span class="text-red-500">*</span>
                                    </label>
                                    <input type="password" name="admin_password" 
                                           placeholder="Enter secure password" class="form-input" required>
                                    <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Confirm Password <span class="text-red-500">*</span>
                                    </label>
                                    <input type="password" name="admin_password_confirm" 
                                           placeholder="Confirm password" class="form-input" required>
                                </div>
                            </div>
                            
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex">
                                    <div class="text-blue-500 mr-3">💡</div>
                                    <div class="text-sm text-blue-700">
                                        <strong>Security Tip:</strong> Use a strong password with a mix of letters, numbers, and symbols. 
                                        You can change these credentials later from the admin panel.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-between pt-6">
                                <a href="?step=2" class="btn-secondary">← Back</a>
                                <button type="submit" class="btn-primary">Create Account & Continue →</button>
                            </div>
                        </form>
                    </div>

                <?php elseif ($currentStep == 4): ?>
                    <!-- Step 4: Site Settings -->
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800 mb-6">Site Configuration</h2>
                        <p class="text-gray-600 mb-8">Configure your spin wheel title, description, and basic settings.</p>
                        
                        <form method="POST" action="?step=4" class="space-y-6">
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Site Title <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="site_title" 
                                           value="<?php echo htmlspecialchars($_POST['site_title'] ?? '🎯 Oasis Spin Wheel'); ?>"
                                           placeholder="🎯 Oasis Spin Wheel" class="form-input" required>
                                    <p class="text-xs text-gray-500 mt-1">This will appear as the main heading on your spin wheel</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Site Description <span class="text-red-500">*</span>
                                    </label>
                                    <textarea name="site_description" rows="3" class="form-input" required 
                                              placeholder="Spin once and win amazing discounts on IVF treatments!"><?php echo htmlspecialchars($_POST['site_description'] ?? 'Spin once and win amazing discounts on IVF treatments!'); ?></textarea>
                                    <p class="text-xs text-gray-500 mt-1">Brief description shown below the title</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Site URL
                                    </label>
                                    <input type="url" name="site_url" 
                                           value="<?php echo htmlspecialchars($_POST['site_url'] ?? $siteUrl); ?>"
                                           placeholder="<?php echo $siteUrl; ?>" class="form-input">
                                    <p class="text-xs text-gray-500 mt-1">Base URL for your installation (auto-detected)</p>
                                </div>
                            </div>
                            
                            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                                <h4 class="font-bold text-green-800 mb-3">What happens next?</h4>
                                <ul class="text-sm text-green-700 space-y-2">
                                    <li class="flex items-center">
                                        <span class="mr-2">✓</span>
                                        Create all database tables and indexes
                                    </li>
                                    <li class="flex items-center">
                                        <span class="mr-2">✓</span>
                                        Install default coupons with probability settings
                                    </li>
                                    <li class="flex items-center">
                                        <span class="mr-2">✓</span>
                                        Set up your admin account
                                    </li>
                                    <li class="flex items-center">
                                        <span class="mr-2">✓</span>
                                        Configure weekly probability rotation
                                    </li>
                                    <li class="flex items-center">
                                        <span class="mr-2">✓</span>
                                        Generate configuration files
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="flex justify-between pt-6">
                                <a href="?step=3" class="btn-secondary">← Back</a>
                                <button type="submit" class="btn-primary">🎉 Install Oasis Spin Wheel!</button>
                            </div>
                        </form>
                    </div>

                <?php elseif ($currentStep == 5): ?>
                    <!-- Step 5: Installation Complete -->
                    <div class="text-center">
                        <div class="text-8xl mb-6">🎉</div>
                        <h2 class="text-4xl font-bold text-green-600 mb-4">
                            <?php echo $configExists && !isset($_GET['step']) ? 'Setup Already Complete!' : 'Installation Complete!'; ?>
                        </h2>
                        <p class="text-xl text-gray-600 mb-8">
                            <?php if ($configExists && !isset($_GET['step'])): ?>
                                Your Oasis Spin Wheel application is already installed and ready to use!
                            <?php else: ?>
                                Congratulations! Your Oasis Spin Wheel application has been successfully installed and configured.
                            <?php endif; ?>
                        </p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 max-w-2xl mx-auto">
                            <div class="p-6 bg-blue-50 rounded-lg">
                                <h3 class="font-bold text-blue-800 mb-3">🎯 Spin Wheel</h3>
                                <p class="text-sm text-blue-600 mb-4">Test your spin wheel with a demo user</p>
                                <a href="index.php?recordedId=demo123" 
                                   class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    Try Spin Wheel
                                </a>
                            </div>
                            
                            <div class="p-6 bg-green-50 rounded-lg">
                                <h3 class="font-bold text-green-800 mb-3">🔐 Admin Panel</h3>
                                <p class="text-sm text-green-600 mb-4">Manage coupons, view analytics, and configure settings</p>
                                <a href="login.php" 
                                   class="inline-block px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                    Access Admin
                                </a>
                            </div>
                        </div>
                        
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
                            <h4 class="font-bold text-yellow-800 mb-3">📋 Important Information</h4>
                            <div class="text-left text-sm text-yellow-700 space-y-2">
                                <p><strong>Admin Login:</strong> Use your configured username and password</p>
                                <p><strong>Spin URL Format:</strong> <code class="bg-yellow-100 px-2 py-1 rounded">yoursite.com/index.php?recordedId=USER_ID</code></p>
                                <p><strong>Default Coupons:</strong> 6 discount tiers with weekly probability variations</p>
                                <p><strong>Security:</strong> Remember to secure your database and use HTTPS in production</p>
                            </div>
                        </div>
                        
                        <div class="space-x-4">
                            <a href="README.md" 
                               class="inline-block px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                📚 Read Documentation
                            </a>
                            <a href="index.php" 
                               class="inline-block px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                🏠 Go to Home
                            </a>
                            <?php if ($configExists && !isset($_GET['step'])): ?>
                            <a href="?step=1&force=1" 
                               class="inline-block px-6 py-3 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                                🔄 Re-run Setup
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>