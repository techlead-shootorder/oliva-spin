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
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['codeId'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Code ID is required']);
        exit;
    }
    
    $codeId = (int)$input['codeId'];
    
    // Check if code exists and is not used
    $stmt = $pdo->prepare("SELECT * FROM tier_coupon_codes WHERE id = ?");
    $stmt->execute([$codeId]);
    $code = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$code) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Code not found']);
        exit;
    }
    
    if ($code['is_used']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot remove used code']);
        exit;
    }
    
    // Delete the code
    $deleteStmt = $pdo->prepare("DELETE FROM tier_coupon_codes WHERE id = ?");
    $deleteStmt->execute([$codeId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Code removed successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Remove tier code error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>