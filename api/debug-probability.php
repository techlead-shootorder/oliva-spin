<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    sendJsonResponse(['error' => 'Unauthorized'], 401);
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        handleGetRequest();
    } elseif ($method === 'POST') {
        handlePostRequest();
    } else {
        sendJsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    error_log("Debug API error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Internal server error'], 500);
}

function handleGetRequest() {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'current_week':
            getCurrentWeekInfo();
            break;
        default:
            sendJsonResponse(['error' => 'Invalid action'], 400);
    }
}

function handlePostRequest() {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'test_probability':
            testProbabilityDistribution($input);
            break;
        case 'single_spin':
            testSingleSpin();
            break;
        default:
            sendJsonResponse(['error' => 'Invalid action'], 400);
    }
}

function getCurrentWeekInfo() {
    $currentWeek = getCurrentWeek();
    $coupons = getCouponProbabilities();
    $totalProb = array_sum(array_column($coupons, 'probability'));
    
    sendJsonResponse([
        'success' => true,
        'currentWeek' => $currentWeek,
        'activeCoupons' => count($coupons),
        'totalProbability' => $totalProb
    ]);
}

function testProbabilityDistribution($input) {
    $testCount = $input['count'] ?? 100;
    
    if ($testCount < 1 || $testCount > 10000) {
        sendJsonResponse(['error' => 'Test count must be between 1 and 10000'], 400);
        return;
    }
    
    $coupons = getCouponProbabilities();
    
    if (empty($coupons)) {
        sendJsonResponse(['error' => 'No active coupons found'], 400);
        return;
    }
    
    // Initialize results
    $results = [];
    foreach ($coupons as $coupon) {
        $results[$coupon['id']] = [
            'name' => $coupon['discount_text'],
            'expected' => $coupon['probability'],
            'count' => 0
        ];
    }
    
    // Run simulations
    for ($i = 0; $i < $testCount; $i++) {
        $winner = selectWinningCoupon();
        if ($winner && isset($results[$winner['id']])) {
            $results[$winner['id']]['count']++;
        }
    }
    
    sendJsonResponse([
        'success' => true,
        'results' => $results,
        'testCount' => $testCount,
        'currentWeek' => getCurrentWeek()
    ]);
}

function testSingleSpin() {
    $winner = selectWinningCoupon();
    
    if (!$winner) {
        sendJsonResponse(['error' => 'No winning coupon selected'], 500);
        return;
    }
    
    sendJsonResponse([
        'success' => true,
        'result' => [
            'text' => $winner['discount_text'],
            'code' => $winner['coupon_code'],
            'value' => $winner['discount_value'],
            'color' => $winner['color'],
            'probability' => $winner['probability']
        ],
        'currentWeek' => getCurrentWeek()
    ]);
}
?>