<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['error' => 'Only POST method allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['username']) || !isset($input['password'])) {
    sendJsonResponse(['error' => 'Username and password are required'], 400);
}

$username = trim($input['username']);
$password = trim($input['password']);

if (empty($username) || empty($password)) {
    sendJsonResponse(['error' => 'Username and password cannot be empty'], 400);
}

try {
    // Check user credentials
    $stmt = $pdo->prepare("SELECT id, password_hash FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Set session
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $username;
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Login successful',
            'redirect' => 'admin.php'
        ]);
    } else {
        sendJsonResponse(['error' => 'Invalid username or password'], 401);
    }
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Internal server error'], 500);
}
?>