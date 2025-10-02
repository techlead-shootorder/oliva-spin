<?php
session_start();

// If already logged in, redirect to admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header('Location: admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if config file exists
    if (!file_exists('api/config.php')) {
        $error = 'Application not configured. Please run setup.php first.';
    } else {
        try {
            require_once 'api/config.php';
        } catch (Exception $e) {
            error_log("Config file error: " . $e->getMessage());
            $error = 'Configuration error. Please check your setup or contact support.';
        }
        
        if (empty($error)) {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $error = 'Please enter both username and password';
            } else {
                try {
                    // Check if admin_users table exists
                    $checkTable = $pdo->query("SHOW TABLES LIKE 'admin_users'");
                    if ($checkTable->rowCount() == 0) {
                        $error = 'Database not properly configured. Please run setup.php to complete installation.';
                    } else {
                        $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM admin_users WHERE username = ?");
                        $stmt->execute([$username]);
                        $user = $stmt->fetch();
                        
                        error_log("Login attempt for user: $username, User found: " . ($user ? 'Yes' : 'No'));
                        
                        if ($user && password_verify($password, $user['password_hash'])) {
                            $_SESSION['admin_logged_in'] = true;
                            $_SESSION['admin_user_id'] = $user['id'];
                            $_SESSION['admin_username'] = $username;
                            $_SESSION['admin_email'] = $user['email'];
                            error_log("Login successful for user: $username");
                            header('Location: admin.php');
                            exit;
                        } else {
                            $error = 'Invalid username or password';
                            error_log("Login failed for user: $username - " . ($user ? 'Password mismatch' : 'User not found'));
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Login database error: " . $e->getMessage());
                    $error = 'Database connection failed. Please check your configuration.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Oasis Spin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.1);
        }
        
        .input-field {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
        }
        
        .input-field:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }
        
        .floating-animation {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .error-message {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Logo/Header -->
            <div class="text-center mb-8">
                <div class="floating-animation">
                    <div class="text-6xl mb-4">🎯</div>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">olivaclinic Spin</h1>
                <p class="text-gray-600">Admin Login</p>
            </div>
            
            <!-- Login Form -->
            <div class="glass-card p-8">
                <form method="POST" action="">
                    <?php if ($error): ?>
                        <div class="error-message">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="space-y-6">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                                Username
                            </label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="input-field" 
                                placeholder="Enter your username"
                                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                required
                                autocomplete="username"
                            >
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                Password
                            </label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="input-field" 
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >
                        </div>
                        
                        <div>
                            <button type="submit" class="btn-primary">
                                🔓 Login to Admin Panel
                            </button>
                        </div>
                    </div>
                </form>
                
               
            </div>
            
            <!-- Back to Wheel -->
            <div class="text-center mt-6">
                <a href="index.php" class="text-gray-600 hover:text-gray-800 text-sm transition-colors">
                    ← Back to Spinning Wheel
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-focus on username field
        document.getElementById('username').focus();
        
        // Enter key handling
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.target.form.submit();
            }
        });
    </script>
</body>
</html>