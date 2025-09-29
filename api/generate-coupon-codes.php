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
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }
    
    $discountTierId = $input['discountTierId'] ?? null;
    $codeCount = $input['codeCount'] ?? 100;
    $suffixLength = $input['suffixLength'] ?? 6;
    $codePattern = $input['codePattern'] ?? 'mixed';
    
    // Validate inputs
    if (!$discountTierId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Discount tier ID is required']);
        exit;
    }
    
    if ($codeCount < 1 || $codeCount > 10000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Code count must be between 1 and 10,000']);
        exit;
    }
    
    // Get discount tier information
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([$discountTierId]);
    $discountTier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$discountTier) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Discount tier not found']);
        exit;
    }
    
    // Create unique_coupon_codes table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS unique_coupon_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            discount_tier_id INT NOT NULL,
            base_code VARCHAR(50) NOT NULL,
            unique_code VARCHAR(100) NOT NULL UNIQUE,
            is_used BOOLEAN DEFAULT FALSE,
            used_by_recorded_id VARCHAR(100) NULL,
            used_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_discount_tier (discount_tier_id),
            INDEX idx_unique_code (unique_code),
            INDEX idx_is_used (is_used),
            FOREIGN KEY (discount_tier_id) REFERENCES coupons(id) ON DELETE CASCADE
        )
    ";
    
    $pdo->exec($createTableSQL);
    
    // Generate character set based on pattern
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $chars = '';
    
    switch ($codePattern) {
        case 'letters':
            $chars = $letters;
            break;
        case 'numbers':
            $chars = $numbers;
            break;
        case 'mixed':
        default:
            $chars = $letters . $numbers;
            break;
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    $generatedCodes = [];
    $sampleCodes = [];
    $attempts = 0;
    $maxAttempts = $codeCount * 10; // Allow up to 10x attempts to handle collisions
    
    while (count($generatedCodes) < $codeCount && $attempts < $maxAttempts) {
        $attempts++;
        
        // Generate random suffix
        $suffix = '';
        for ($i = 0; $i < $suffixLength; $i++) {
            $suffix .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        $uniqueCode = $discountTier['coupon_code'] . '-' . $suffix;
        
        // Check if code already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM unique_coupon_codes WHERE unique_code = ?");
        $checkStmt->execute([$uniqueCode]);
        
        if ($checkStmt->fetchColumn() == 0) {
            // Insert the unique code
            $insertStmt = $pdo->prepare("
                INSERT INTO unique_coupon_codes (discount_tier_id, base_code, unique_code) 
                VALUES (?, ?, ?)
            ");
            $insertStmt->execute([$discountTierId, $discountTier['coupon_code'], $uniqueCode]);
            
            $generatedCodes[] = $uniqueCode;
            
            // Store first 5 as samples
            if (count($sampleCodes) < 5) {
                $sampleCodes[] = $uniqueCode;
            }
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    if (count($generatedCodes) < $codeCount) {
        echo json_encode([
            'success' => false,
            'message' => "Only generated " . count($generatedCodes) . " out of $codeCount codes due to collision limits. Try with a longer suffix or different pattern."
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Coupon codes generated successfully',
        'generated' => count($generatedCodes),
        'pattern' => $codePattern,
        'suffix_length' => $suffixLength,
        'sample_codes' => $sampleCodes
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Generate coupon codes error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>