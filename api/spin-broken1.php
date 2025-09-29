<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST method allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['recordedId'])) {
        http_response_code(400);
        echo json_encode(['error' => 'recordedId is required']);
        exit;
    }
    
    $recordedId = trim($input['recordedId']);
    
    if (empty($recordedId)) {
        http_response_code(400);
        echo json_encode(['error' => 'recordedId cannot be empty']);
        exit;
    }
    
    // Basic validation
    if (!preg_match('/^[a-zA-Z0-9]{1,50}$/', $recordedId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid recordedId format']);
        exit;
    }
    
    // Rate limiting
    $clientIP = getClientIP();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM spins WHERE ip_address = ? AND timestamp > ?");
    $stmt->execute([$clientIP, (time() - 3600) * 1000]);
    $recentSpins = $stmt->fetchColumn();
    
    if ($recentSpins > 10) {
        http_response_code(429);
        echo json_encode(['error' => 'Too many attempts. Please try again later.']);
        exit;
    }
    
    // Check if user can spin
    if (!canUserSpin($recordedId)) {
        http_response_code(403);
        echo json_encode(['error' => 'You have already used your spin for this campaign']);
        exit;
    }
    
    // Get winning coupon based on current week probabilities
    $winningCoupon = selectWinningCoupon();
    
    if (!$winningCoupon) {
        http_response_code(500);
        echo json_encode(['error' => 'No active coupons available']);
        exit;
    }
    
    // Begin transaction for atomic operation
    $pdo->beginTransaction();
    
    try {
        $assignedCode = null;
        
        // Check if tier_coupon_codes table exists and has codes for this tier
        $tableExists = false;
        try {
            $pdo->query("SELECT 1 FROM tier_coupon_codes LIMIT 1");
            $tableExists = true;
        } catch (Exception $e) {
            // Table doesn't exist
        }
        
        if ($tableExists) {
            // Try to get an unused code for this tier
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
                $assignedCode = $tierCode['coupon_code'];
                
                // Mark this code as used
                $updateStmt = $pdo->prepare("
                    UPDATE tier_coupon_codes 
                    SET is_used = TRUE, used_by_recorded_id = ?, used_at = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->execute([$recordedId, $tierCode['id']]);
            }
        }
        
        // If no tier-specific code available, use the base coupon code
        if (!$assignedCode) {
            $assignedCode = $winningCoupon['coupon_code'];
        }
        
        // Increment user spin count
        incrementUserSpin($recordedId);
        
        // Store the spin result
        $stmt = $pdo->prepare("
            INSERT INTO spins (recorded_id, result, coupon_code, timestamp, ip_address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $recordedId,
            $winningCoupon['discount_text'],
            $assignedCode,
            time() * 1000,
            $clientIP
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        // Return winning result
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'result' => [
                'text' => $winningCoupon['discount_text'],
                'value' => $winningCoupon['discount_value'],
                'code' => $assignedCode,
                'color' => $winningCoupon['color']
            ],
            'currentWeek' => getCurrentWeek()
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Spin error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>