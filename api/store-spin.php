<?php
require_once 'config.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'error' => 'Only POST requests allowed'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!$input || !isset($input['recordedId']) || !isset($input['result'])) {
    sendJsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
}

$recordedId = trim($input['recordedId']);
$result = trim($input['result']);
$timestamp = isset($input['timestamp']) ? (int)$input['timestamp'] : time() * 1000;
$ipAddress = getClientIP();

// Validate data
if (empty($recordedId) || empty($result)) {
    sendJsonResponse(['success' => false, 'error' => 'Invalid data provided'], 400);
}

try {
    // Check if tracking is enabled
    $settingStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'enable_tracking'");
    $settingStmt->execute();
    $enableTracking = $settingStmt->fetchColumn();
    
    if (!$enableTracking || $enableTracking !== '1') {
        sendJsonResponse(['success' => false, 'error' => 'Spin tracking is disabled'], 403);
    }
    
    // Check max spins limit
    $maxSpinsStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'max_spins'");
    $maxSpinsStmt->execute();
    $maxSpins = (int)$maxSpinsStmt->fetchColumn();
    
    if ($maxSpins > 0) {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM spins WHERE recorded_id = ?");
        $countStmt->execute([$recordedId]);
        $currentSpins = $countStmt->fetchColumn();
        
        if ($currentSpins >= $maxSpins) {
            sendJsonResponse(['success' => false, 'error' => 'Maximum spins exceeded'], 429);
        }
    }
    
    // Insert spin record
    $insertStmt = $pdo->prepare("
        INSERT INTO spins (recorded_id, result, timestamp, ip_address) 
        VALUES (?, ?, ?, ?)
    ");
    
    $insertStmt->execute([$recordedId, $result, $timestamp, $ipAddress]);
    
    // Return success response
    sendJsonResponse([
        'success' => true,
        'message' => 'Spin recorded successfully',
        'data' => [
            'id' => $pdo->lastInsertId(),
            'recordedId' => $recordedId,
            'result' => $result,
            'timestamp' => $timestamp,
            'ipAddress' => $ipAddress
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in store-spin.php: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'error' => 'Database error occurred'], 500);
} catch (Exception $e) {
    error_log("General error in store-spin.php: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'error' => 'An error occurred'], 500);
}
?>