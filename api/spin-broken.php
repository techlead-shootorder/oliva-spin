<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['error' => 'Only POST method allowed'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['recordedId'])) {
    sendJsonResponse(['error' => 'Invalid input. recordedId is required'], 400);
}

$recordedId = trim($input['recordedId']);

if (empty($recordedId)) {
    sendJsonResponse(['error' => 'recordedId cannot be empty'], 400);
}

try {
    // Rate limiting: Check for suspicious activity
    $clientIP = getClientIP();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM spins WHERE ip_address = ? AND timestamp > ?");
    $stmt->execute([$clientIP, (time() - 3600) * 1000]); // Last hour
    $recentSpins = $stmt->fetchColumn();
    
    if ($recentSpins > 10) { // Max 10 spins per IP per hour
        sendJsonResponse(['error' => 'Too many attempts. Please try again later.'], 429);
    }
    
    // Validate recordedId format (basic security)
    if (!preg_match('/^[a-zA-Z0-9]{1,50}$/', $recordedId)) {
        sendJsonResponse(['error' => 'Invalid recordedId format'], 400);
    }
    
    // Check if user can spin
    if (!canUserSpin($recordedId)) {
        sendJsonResponse(['error' => 'You have already used your spin for this campaign'], 403);
    }
    
    // Get winning coupon based on current week probabilities
    $winningCoupon = selectWinningCoupon();
    
    if (!$winningCoupon) {
        sendJsonResponse(['error' => 'No active coupons available'], 500);
    }
    
    // Generate unique coupon code for this spin
    $uniqueCouponCode = generateUniqueCouponCode($winningCoupon['coupon_code'], $recordedId);
    
    // Increment user spin count
    incrementUserSpin($recordedId);
    
    // Store the spin result
    // Check if coupon_code column exists to maintain compatibility
    try {
        $columns = $pdo->query("DESCRIBE spins")->fetchAll();
        $hasCouponCodeColumn = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'coupon_code') {
                $hasCouponCodeColumn = true;
                break;
            }
        }
        
        if ($hasCouponCodeColumn) {
            // New schema with coupon_code column
            $stmt = $pdo->prepare("INSERT INTO spins (recorded_id, result, coupon_code, timestamp, ip_address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $recordedId,
                $winningCoupon['discount_text'],
                $uniqueCouponCode, // Use unique generated code
                time() * 1000, // JavaScript timestamp format
                getClientIP()
            ]);
        } else {
            // Old schema without coupon_code column
            $stmt = $pdo->prepare("INSERT INTO spins (recorded_id, result, timestamp, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $recordedId,
                $winningCoupon['discount_text'],
                time() * 1000, // JavaScript timestamp format
                getClientIP()
            ]);
        }
    } catch (Exception $e) {
        // Fallback to old schema if there's any issue checking columns
        error_log("Spin database check error: " . $e->getMessage());
        $stmt = $pdo->prepare("INSERT INTO spins (recorded_id, result, timestamp, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $recordedId,
            $winningCoupon['discount_text'],
            time() * 1000, // JavaScript timestamp format
            getClientIP()
        ]);
    }
    
    // Return winning result
    sendJsonResponse([
        'success' => true,
        'result' => [
            'text' => $winningCoupon['discount_text'],
            'value' => $winningCoupon['discount_value'],
            'code' => $hasCouponCodeColumn ? $uniqueCouponCode : $winningCoupon['coupon_code'], // Use unique code if column exists
            'color' => $winningCoupon['color']
        ],
        'currentWeek' => getCurrentWeek()
    ]);
    
} catch (Exception $e) {
    error_log("Spin error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Internal server error'], 500);
}
?>