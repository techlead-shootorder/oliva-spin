<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

$recordedId = $_GET['recordedId'] ?? '';

try {
    // Get current week coupons with probabilities
    $coupons = getCouponProbabilities();
    
    $wheelData = [];
    foreach ($coupons as $coupon) {
        $wheelData[] = [
            'text' => $coupon['discount_text'],
            'value' => $coupon['discount_value'],
            'code' => $coupon['coupon_code'],
            'color' => $coupon['color'],
            'probability' => $coupon['probability']
        ];
    }
    
    // Check if user can spin
    $canSpin = true;
    $previousResult = null;
    $previousCouponCode = null;
    
    if (!empty($recordedId)) {
        $canSpin = canUserSpin($recordedId);
        
        // If user can't spin, get their previous result and coupon code
        if (!$canSpin) {
            $stmt = $pdo->prepare("SELECT result, coupon_code FROM spins WHERE recorded_id = ? ORDER BY timestamp DESC LIMIT 1");
            $stmt->execute([$recordedId]);
            $previousData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($previousData) {
                $previousResult = $previousData['result'];
                $previousCouponCode = $previousData['coupon_code'];
            }
        }
    }
    
    // Get wheel settings
    $settingsStmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('wheel_title', 'wheel_description')");
    $settingsStmt->execute();
    $settingsData = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    sendJsonResponse([
        'success' => true,
        'wheelData' => $wheelData,
        'canSpin' => $canSpin,
        'previousResult' => $previousResult,
        'previousCouponCode' => $previousCouponCode,
        'currentWeek' => getCurrentWeek(),
        'settings' => $settingsData
    ]);
    
} catch (Exception $e) {
    error_log("Get wheel data error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Internal server error'], 500);
}
?>