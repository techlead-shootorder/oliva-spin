<?php
// Minimal working version - step by step testing
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Step 1: Check method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'Only POST method allowed', 'step' => 1]);
        exit;
    }
    
    // Step 2: Get input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['recordedId'])) {
        echo json_encode(['error' => 'recordedId required', 'step' => 2]);
        exit;
    }
    
    $recordedId = trim($input['recordedId']);
    
    // Step 3: Try to load config
    if (!file_exists('config.php')) {
        echo json_encode(['error' => 'config.php not found', 'step' => 3]);
        exit;
    }
    
    require_once 'config.php';
    
    // Step 4: Check database
    if (!isset($pdo)) {
        echo json_encode(['error' => 'Database not available', 'step' => 4]);
        exit;
    }
    
    // Step 5: Test database connection
    $stmt = $pdo->query("SELECT 1");
    if (!$stmt) {
        echo json_encode(['error' => 'Database query failed', 'step' => 5]);
        exit;
    }
    
    // Step 6: Check functions exist
    if (!function_exists('canUserSpin')) {
        echo json_encode(['error' => 'canUserSpin function missing', 'step' => 6]);
        exit;
    }
    
    if (!function_exists('selectWinningCoupon')) {
        echo json_encode(['error' => 'selectWinningCoupon function missing', 'step' => 6]);
        exit;
    }
    
    // Step 7: Test canUserSpin
    $canSpin = canUserSpin($recordedId);
    if (!$canSpin) {
        echo json_encode(['error' => 'User already spun', 'step' => 7, 'recordedId' => $recordedId]);
        exit;
    }
    
    // Step 8: Test selectWinningCoupon
    $winningCoupon = selectWinningCoupon();
    if (!$winningCoupon) {
        echo json_encode(['error' => 'No winning coupon', 'step' => 8]);
        exit;
    }
    
    // Step 9: Simple success with basic fallback code
    echo json_encode([
        'success' => true,
        'step' => 9,
        'result' => [
            'text' => $winningCoupon['discount_text'],
            'value' => $winningCoupon['discount_value'],
            'code' => $winningCoupon['coupon_code'] . '-BASIC',
            'color' => $winningCoupon['color']
        ],
        'debug' => [
            'recordedId' => $recordedId,
            'tier_id' => $winningCoupon['id']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Minimal spin error: " . $e->getMessage());
    echo json_encode([
        'error' => 'Exception caught',
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    error_log("Minimal spin fatal error: " . $e->getMessage());
    echo json_encode([
        'error' => 'Fatal error',
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
?>