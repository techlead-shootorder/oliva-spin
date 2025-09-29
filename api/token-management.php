<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    sendJsonResponse(['error' => 'Unauthorized'], 401);
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetRequest();
            break;
        case 'POST':
            handlePostRequest();
            break;
        default:
            sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log("Token management error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Internal server error'], 500);
}

function handleGetRequest() {
    global $pdo;
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'list':
            listTokens();
            break;
        default:
            sendJsonResponse(['error' => 'Invalid action'], 400);
    }
}

function handlePostRequest() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'generate':
            generateTokens($input);
            break;
        case 'remove':
            removeToken($input);
            break;
        case 'clear_all':
            clearAllTokens();
            break;
        default:
            sendJsonResponse(['error' => 'Invalid action'], 400);
    }
}

function listTokens() {
    global $pdo;
    
    try {
        // Create tokens table if it doesn't exist
        createTokensTable();
        
        $stmt = $pdo->prepare("SELECT token, created_at FROM tokens ORDER BY created_at DESC");
        $stmt->execute();
        $tokens = $stmt->fetchAll();
        
        sendJsonResponse([
            'success' => true,
            'tokens' => $tokens
        ]);
    } catch (Exception $e) {
        error_log("Error listing tokens: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to list tokens'], 500);
    }
}

function generateTokens($input) {
    global $pdo;
    
    try {
        $count = max(1, min(1000, (int)($input['count'] ?? 10)));
        $length = max(4, min(64, (int)($input['length'] ?? 16)));
        $type = $input['type'] ?? 'alphanumeric';
        
        // Create tokens table if it doesn't exist
        createTokensTable();
        
        $generated = 0;
        $stmt = $pdo->prepare("INSERT INTO tokens (token, created_at) VALUES (?, NOW())");
        
        for ($i = 0; $i < $count; $i++) {
            $token = generateRandomToken($length, $type);
            
            // Check if token already exists
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tokens WHERE token = ?");
            $checkStmt->execute([$token]);
            
            if ($checkStmt->fetchColumn() == 0) {
                $stmt->execute([$token]);
                $generated++;
            }
        }
        
        sendJsonResponse([
            'success' => true,
            'count' => $generated,
            'message' => "Generated $generated tokens"
        ]);
        
    } catch (Exception $e) {
        error_log("Error generating tokens: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to generate tokens'], 500);
    }
}

function removeToken($input) {
    global $pdo;
    
    try {
        $token = $input['token'] ?? '';
        
        if (!$token) {
            sendJsonResponse(['error' => 'Token is required'], 400);
        }
        
        $stmt = $pdo->prepare("DELETE FROM tokens WHERE token = ?");
        $stmt->execute([$token]);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Token removed successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Error removing token: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to remove token'], 500);
    }
}

function clearAllTokens() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM tokens");
        $stmt->execute();
        
        sendJsonResponse([
            'success' => true,
            'message' => 'All tokens cleared successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Error clearing tokens: " . $e->getMessage());
        sendJsonResponse(['error' => 'Failed to clear tokens'], 500);
    }
}

function generateRandomToken($length, $type) {
    $characters = '';
    
    switch ($type) {
        case 'numeric':
            $characters = '0123456789';
            break;
        case 'alphabetic':
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        case 'alphanumeric':
        default:
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
    }
    
    $token = '';
    $charactersLength = strlen($characters);
    
    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[rand(0, $charactersLength - 1)];
    }
    
    return $token;
}

function createTokensTable() {
    global $pdo;
    
    try {
        $sql = "CREATE TABLE IF NOT EXISTS tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(64) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token)
        )";
        
        $pdo->exec($sql);
    } catch (Exception $e) {
        error_log("Error creating tokens table: " . $e->getMessage());
        throw $e;
    }
}
?>