<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['tierId']) || !isset($input['codes'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }
    
    $tierId = (int)$input['tierId'];
    $codes = $input['codes'];
    
    if (!is_array($codes) || empty($codes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No codes provided']);
        exit;
    }
    
    // Verify tier exists
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([$tierId]);
    $tier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tier) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Discount tier not found']);
        exit;
    }
    
    // Create tier_coupon_codes table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS tier_coupon_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tier_id INT NOT NULL,
            coupon_code VARCHAR(100) NOT NULL,
            is_used BOOLEAN DEFAULT FALSE,
            used_by_recorded_id VARCHAR(100) NULL,
            used_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tier_id (tier_id),
            INDEX idx_coupon_code (coupon_code),
            INDEX idx_is_used (is_used),
            UNIQUE KEY unique_code (coupon_code),
            FOREIGN KEY (tier_id) REFERENCES coupons(id) ON DELETE CASCADE
        )
    ";
    
    $pdo->exec($createTableSQL);
    
    // Begin transaction
    $pdo->beginTransaction();
    
    $addedCount = 0;
    $errors = [];
    
    foreach ($codes as $code) {
        $cleanCode = trim($code);
        
        if (empty($cleanCode)) {
            continue;
        }
        
        // Validate code format (allow letters, numbers, and spaces)
        if (!preg_match('/^[A-Za-z0-9\s]{1,100}$/', $cleanCode)) {
            $errors[] = "Invalid code format: $cleanCode";
            continue;
        }
        
        try {
            // Check if code already exists
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tier_coupon_codes WHERE coupon_code = ?");
            $checkStmt->execute([$cleanCode]);
            
            if ($checkStmt->fetchColumn() > 0) {
                $errors[] = "Code already exists: $cleanCode";
                continue;
            }
            
            // Insert the code
            $insertStmt = $pdo->prepare("
                INSERT INTO tier_coupon_codes (tier_id, coupon_code) 
                VALUES (?, ?)
            ");
            $insertStmt->execute([$tierId, $cleanCode]);
            $addedCount++;
            
        } catch (Exception $e) {
            $errors[] = "Error adding code '$cleanCode': " . $e->getMessage();
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    $response = [
        'success' => true,
        'message' => "Successfully added $addedCount codes",
        'added' => $addedCount,
        'total_codes' => count($codes)
    ];
    
    if (!empty($errors)) {
        $response['warnings'] = $errors;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Add tier codes error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>