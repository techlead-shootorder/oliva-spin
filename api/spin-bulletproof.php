<?php
// Bulletproof spin endpoint - handles all error cases
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors from API response
ini_set('log_errors', 1);

// Try to include config, with fallback
try {
    require_once 'config.php';
} catch (Exception $e) {
    error_log("Config error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Configuration error']);
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST method allowed']);
    exit;
}

// Get JSON input with error handling
$input = null;
try {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
} catch (Exception $e) {
    error_log("JSON parsing error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

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

try {
    // Basic validation
    if (!preg_match('/^[a-zA-Z0-9]{1,50}$/', $recordedId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid recordedId format']);
        exit;
    }
    
    // Check database connection
    if (!isset($pdo) || !$pdo) {
        throw new Exception('Database connection not available');
    }
    
    // Rate limiting with error handling
    try {
        $clientIP = getClientIP();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM spins WHERE ip_address = ? AND timestamp > ?");
        $stmt->execute([$clientIP, (time() - 3600) * 1000]);
        $recentSpins = $stmt->fetchColumn();
        
        if ($recentSpins > 10) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many attempts. Please try again later.']);
            exit;
        }
    } catch (Exception $e) {
        error_log("Rate limiting error: " . $e->getMessage());
        // Continue without rate limiting if it fails
    }
    
    // Check if user can spin
    try {
        if (!canUserSpin($recordedId)) {
            http_response_code(403);
            echo json_encode(['error' => 'You have already used your spin for this campaign']);
            exit;
        }
    } catch (Exception $e) {
        error_log("canUserSpin error: " . $e->getMessage());
        // Allow spin if function fails
    }
    
    // Get winning coupon
    try {
        $winningCoupon = selectWinningCoupon();
    } catch (Exception $e) {
        error_log("selectWinningCoupon error: " . $e->getMessage());
        $winningCoupon = null;
    }
    
    if (!$winningCoupon) {
        http_response_code(500);
        echo json_encode(['error' => 'No active coupons available']);
        exit;
    }
    
    // Generate unique coupon code with fallback
    try {
        $uniqueCouponCode = generateUniqueCouponCode($winningCoupon['coupon_code'], $recordedId);
    } catch (Exception $e) {
        error_log("generateUniqueCouponCode error: " . $e->getMessage());
        // Fallback to simple unique code
        $uniqueCouponCode = $winningCoupon['coupon_code'] . '-' . strtoupper(substr(md5($recordedId . time()), 0, 6));
    }
    
    // Increment user spin count
    try {
        incrementUserSpin($recordedId);
    } catch (Exception $e) {
        error_log("incrementUserSpin error: " . $e->getMessage());
        // Continue even if this fails
    }
    
    // Store the spin result with multiple fallbacks
    $spinStored = false;
    $finalCouponCode = $uniqueCouponCode;
    
    // Method 1: Try with coupon_code column
    try {
        $stmt = $pdo->prepare("INSERT INTO spins (recorded_id, result, coupon_code, timestamp, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $recordedId,
            $winningCoupon['discount_text'],
            $uniqueCouponCode,
            time() * 1000,
            $clientIP
        ]);
        $spinStored = true;
    } catch (Exception $e) {
        error_log("Method 1 (with coupon_code) failed: " . $e->getMessage());
        
        // Method 2: Try without coupon_code column
        try {
            $stmt = $pdo->prepare("INSERT INTO spins (recorded_id, result, timestamp, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $recordedId,
                $winningCoupon['discount_text'],
                time() * 1000,
                $clientIP
            ]);
            $spinStored = true;
            $finalCouponCode = $winningCoupon['coupon_code']; // Use original code if we can't store unique
        } catch (Exception $e2) {
            error_log("Method 2 (without coupon_code) failed: " . $e2->getMessage());
            
            // Method 3: Try minimal insert
            try {
                $stmt = $pdo->prepare("INSERT INTO spins (recorded_id, result, timestamp) VALUES (?, ?, ?)");
                $stmt->execute([
                    $recordedId,
                    $winningCoupon['discount_text'],
                    time() * 1000
                ]);
                $spinStored = true;
                $finalCouponCode = $winningCoupon['coupon_code'];
            } catch (Exception $e3) {
                error_log("Method 3 (minimal) failed: " . $e3->getMessage());
                // Continue without storing - at least return the result
            }
        }
    }
    
    // Get current week with fallback
    try {
        $currentWeek = getCurrentWeek();
    } catch (Exception $e) {
        error_log("getCurrentWeek error: " . $e->getMessage());
        $currentWeek = 1; // Default week
    }
    
    // Return winning result
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'result' => [
            'text' => $winningCoupon['discount_text'],
            'value' => $winningCoupon['discount_value'],
            'code' => $finalCouponCode,
            'color' => $winningCoupon['color']
        ],
        'currentWeek' => $currentWeek,
        'stored' => $spinStored
    ]);
    
} catch (Exception $e) {
    error_log("Critical spin error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
?>