<?php
// Ultra-simple debug version to find the exact error
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Step 1: Check if config exists
    if (!file_exists('config.php')) {
        echo json_encode(['error' => 'config.php not found', 'step' => 1]);
        exit;
    }
    
    // Step 2: Try to include config
    require_once 'config.php';
    
    // Step 3: Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'Only POST method allowed', 'step' => 3]);
        exit;
    }
    
    // Step 4: Get input
    $rawInput = file_get_contents('php://input');
    if (empty($rawInput)) {
        echo json_encode(['error' => 'No input received', 'step' => 4]);
        exit;
    }
    
    // Step 5: Decode JSON
    $input = json_decode($rawInput, true);
    if (!$input) {
        echo json_encode(['error' => 'Invalid JSON', 'step' => 5, 'raw_input' => $rawInput]);
        exit;
    }
    
    // Step 6: Check recordedId
    if (!isset($input['recordedId'])) {
        echo json_encode(['error' => 'recordedId missing', 'step' => 6, 'input' => $input]);
        exit;
    }
    
    $recordedId = trim($input['recordedId']);
    
    // Step 7: Check database connection
    if (!isset($pdo)) {
        echo json_encode(['error' => 'PDO not available', 'step' => 7]);
        exit;
    }
    
    // Step 8: Test database query
    $stmt = $pdo->query("SELECT 1");
    if (!$stmt) {
        echo json_encode(['error' => 'Database query failed', 'step' => 8]);
        exit;
    }
    
    // Step 9: Check required functions
    $functions = ['canUserSpin', 'selectWinningCoupon', 'incrementUserSpin', 'getClientIP', 'getCurrentWeek'];
    $missingFunctions = [];
    
    foreach ($functions as $func) {
        if (!function_exists($func)) {
            $missingFunctions[] = $func;
        }
    }
    
    if (!empty($missingFunctions)) {
        echo json_encode(['error' => 'Missing functions', 'step' => 9, 'missing' => $missingFunctions]);
        exit;
    }
    
    // Step 10: Test canUserSpin
    $canSpin = canUserSpin($recordedId);
    if (!$canSpin) {
        echo json_encode(['error' => 'User cannot spin', 'step' => 10, 'recordedId' => $recordedId]);
        exit;
    }
    
    // Step 11: Test selectWinningCoupon
    $winningCoupon = selectWinningCoupon();
    if (!$winningCoupon) {
        echo json_encode(['error' => 'No winning coupon', 'step' => 11]);
        exit;
    }
    
    // Step 12: Success - return simple result
    echo json_encode([
        'success' => true,
        'step' => 12,
        'result' => [
            'text' => $winningCoupon['discount_text'],
            'value' => $winningCoupon['discount_value'],
            'code' => $winningCoupon['coupon_code'] . '-SIMPLE',
            'color' => $winningCoupon['color']
        ],
        'debug' => [
            'recordedId' => $recordedId,
            'winning_coupon' => $winningCoupon
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Exception caught',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Error $e) {
    echo json_encode([
        'error' => 'Fatal error caught',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>