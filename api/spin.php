<?php
require_once 'config.php';

// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/spin_errors.log');

// Custom logging function
function logDebug($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data !== null) {
        $logMessage .= " | Data: " . json_encode($data);
    }
    error_log($logMessage, 3, __DIR__ . '/spin_debug.log');
}

// Set headers for JSON response and CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    logDebug("OPTIONS request received");
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logDebug("Invalid method", $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    logDebug("Starting spin request");
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    logDebug("Raw input received", $input);
    
    if (!$input || !isset($input['recordedId'])) {
        logDebug("Missing recordedId in request");
        http_response_code(400);
        echo json_encode(['error' => 'recordedId is required']);
        exit;
    }
    
    $recordedId = trim($input['recordedId']);
    logDebug("Processing recordedId", $recordedId);
    
    // Check if user can spin
    logDebug("Checking if user can spin");
    if (!canUserSpin($recordedId)) {
        logDebug("User cannot spin - already spun", $recordedId);
        http_response_code(400);
        echo json_encode(['error' => 'You have already spun the wheel']);
        exit;
    }
    
    logDebug("User can spin - proceeding");
    
    // Select winning coupon based on probability
    logDebug("Selecting winning coupon");
    $winningCoupon = selectWinningCoupon();
    
    if (!$winningCoupon) {
        logDebug("No winning coupon found");
        http_response_code(500);
        echo json_encode(['error' => 'No coupons available']);
        exit;
    }
    
    logDebug("Winning coupon selected", $winningCoupon);
    
    // Create tier_coupon_codes table if it doesn't exist
    logDebug("Creating tier_coupon_codes table if needed");
    $createTierCodesTable = "
        CREATE TABLE IF NOT EXISTS tier_coupon_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tier_id INT NOT NULL,
            coupon_code VARCHAR(100) NOT NULL,
            is_used BOOLEAN DEFAULT FALSE,
            used_by_recorded_id VARCHAR(100) NULL,
            used_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_code (coupon_code),
            FOREIGN KEY (tier_id) REFERENCES coupons(id) ON DELETE CASCADE
        )
    ";
    
    try {
        $pdo->exec($createTierCodesTable);
        logDebug("tier_coupon_codes table created/verified");
    } catch (Exception $e) {
        logDebug("Error creating tier_coupon_codes table", $e->getMessage());
        // Continue anyway - table might already exist
    }
    
    // Try to get an unused code that was manually added for this tier
    logDebug("Looking for tier-specific codes", $winningCoupon['id']);
    $stmt = $pdo->prepare("
        SELECT id, coupon_code 
        FROM tier_coupon_codes 
        WHERE tier_id = ? AND is_used = FALSE 
        ORDER BY created_at ASC 
        LIMIT 1
    ");
    $stmt->execute([$winningCoupon['id']]);
    $tierCode = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tierCode) {
        logDebug("Found tier-specific code", $tierCode);
        // Use the specific code that was manually added
        $assignedCode = $tierCode['coupon_code'];
        $codeSource = 'tier_specific';
        
        // Mark this code as used
        logDebug("Marking tier code as used");
        $updateStmt = $pdo->prepare("
            UPDATE tier_coupon_codes 
            SET is_used = TRUE, used_by_recorded_id = ?, used_at = NOW() 
            WHERE id = ?
        ");
        $updateStmt->execute([$recordedId, $tierCode['id']]);
        logDebug("Tier code marked as used successfully");
    } else {
        logDebug("No tier-specific codes found, generating base code");
        // Fallback to base coupon code with unique suffix
        $assignedCode = generateUniqueCouponCode($winningCoupon['coupon_code'], $recordedId);
        $codeSource = 'base_generated';
        logDebug("Generated base code", $assignedCode);
    }
    
    // Increment user spin count
    logDebug("Incrementing user spin count");
    incrementUserSpin($recordedId);
    logDebug("User spin count incremented");
    
    // Record the spin result
    logDebug("Recording spin result");
    $stmt = $pdo->prepare("
        INSERT INTO spins (recorded_id, result, coupon_code, timestamp, ip_address) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $recordedId,
        $winningCoupon['discount_text'],
        $assignedCode,
        time(),
        getClientIP()
    ]);
    logDebug("Spin result recorded successfully");
    
    // Prepare response
    $response = [
        'success' => true,
        'result' => [
            'text' => $winningCoupon['discount_text'],
            'value' => $winningCoupon['discount_value'],
            'code' => $assignedCode,
            'color' => $winningCoupon['color']
        ],
        'debug' => [
            'recordedId' => $recordedId,
            'tier_id' => $winningCoupon['id'],
            'code_source' => $codeSource
        ]
    ];
    
    logDebug("Sending successful response", $response);
    
    // Return success response
    echo json_encode($response);
    
} catch (Exception $e) {
    logDebug("Exception caught", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    error_log("Spin error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    logDebug("Fatal error caught", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    error_log("Spin fatal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Fatal error',
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
?>