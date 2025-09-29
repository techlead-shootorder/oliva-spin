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
    // Check if unique_coupon_codes table exists
    $tableExists = false;
    try {
        $pdo->query("SELECT 1 FROM unique_coupon_codes LIMIT 1");
        $tableExists = true;
    } catch (Exception $e) {
        // Table doesn't exist yet
    }
    
    if (!$tableExists) {
        echo json_encode(['success' => false, 'message' => 'No coupon codes table found']);
        exit;
    }
    
    // Count used codes before deletion
    $stmt = $pdo->query("SELECT COUNT(*) FROM unique_coupon_codes WHERE is_used = TRUE");
    $usedCount = $stmt->fetchColumn();
    
    if ($usedCount == 0) {
        echo json_encode(['success' => true, 'message' => 'No used codes to clear']);
        exit;
    }
    
    // Delete used codes
    $stmt = $pdo->prepare("DELETE FROM unique_coupon_codes WHERE is_used = TRUE");
    $stmt->execute();
    $deletedCount = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully cleared $deletedCount used coupon codes"
    ]);
    
} catch (Exception $e) {
    error_log("Clear used codes error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>