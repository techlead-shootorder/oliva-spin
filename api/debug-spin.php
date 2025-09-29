<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    echo json_encode([
        'step' => 1,
        'message' => 'Starting debug',
        'php_version' => PHP_VERSION
    ]);
    
    // Test config file
    if (!file_exists('config.php')) {
        throw new Exception('config.php not found');
    }
    
    require_once 'config.php';
    
    echo json_encode([
        'step' => 2,
        'message' => 'Config loaded successfully'
    ]);
    
    // Test database connection
    if (!isset($pdo)) {
        throw new Exception('PDO not initialized');
    }
    
    $stmt = $pdo->query("SELECT 1");
    
    echo json_encode([
        'step' => 3,
        'message' => 'Database connection successful'
    ]);
    
    // Test functions
    if (!function_exists('canUserSpin')) {
        throw new Exception('canUserSpin function not found');
    }
    
    if (!function_exists('selectWinningCoupon')) {
        throw new Exception('selectWinningCoupon function not found');
    }
    
    echo json_encode([
        'step' => 4,
        'message' => 'All functions available'
    ]);
    
    // Test coupon selection
    $winningCoupon = selectWinningCoupon();
    
    echo json_encode([
        'step' => 5,
        'message' => 'Coupon selection successful',
        'coupon' => $winningCoupon
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Error $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>