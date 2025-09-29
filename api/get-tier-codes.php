<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $tierId = isset($_GET['tierId']) ? (int)$_GET['tierId'] : null;
    
    if (!$tierId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tier ID is required']);
        exit;
    }
    
    // Check if tier_coupon_codes table exists
    $tableExists = false;
    try {
        $pdo->query("SELECT 1 FROM tier_coupon_codes LIMIT 1");
        $tableExists = true;
    } catch (Exception $e) {
        // Table doesn't exist yet
    }
    
    if (!$tableExists) {
        echo json_encode([
            'success' => true,
            'codes' => [],
            'stats' => ['total' => 0, 'available' => 0, 'used' => 0]
        ]);
        exit;
    }
    
    // Get codes for this tier
    $stmt = $pdo->prepare("
        SELECT * FROM tier_coupon_codes 
        WHERE tier_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$tierId]);
    $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_used = FALSE THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN is_used = TRUE THEN 1 ELSE 0 END) as used
        FROM tier_coupon_codes 
        WHERE tier_id = ?
    ");
    $statsStmt->execute([$tierId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'codes' => $codes,
        'stats' => [
            'total' => (int)$stats['total'],
            'available' => (int)$stats['available'],
            'used' => (int)$stats['used']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get tier codes error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>