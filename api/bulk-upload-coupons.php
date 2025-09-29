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
    
    if (!$input || !isset($input['action']) || $input['action'] !== 'upload') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }
    
    if (!isset($input['coupons']) || !is_array($input['coupons'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No coupons data provided']);
        exit;
    }
    
    $coupons = $input['coupons'];
    $errors = [];
    $successCount = 0;
    
    // Begin transaction
    $pdo->beginTransaction();
    
    foreach ($coupons as $index => $coupon) {
        try {
            // Validate required fields
            $requiredFields = ['discount_text', 'coupon_code', 'discount_value', 'week1_probability', 'week2_probability', 'week3_probability', 'week4_probability', 'color'];
            
            foreach ($requiredFields as $field) {
                if (!isset($coupon[$field]) || trim($coupon[$field]) === '') {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Validate probabilities
            $probabilities = [
                (int)$coupon['week1_probability'],
                (int)$coupon['week2_probability'],
                (int)$coupon['week3_probability'],
                (int)$coupon['week4_probability']
            ];
            
            foreach ($probabilities as $prob) {
                if ($prob < 0 || $prob > 100) {
                    throw new Exception("Probability must be between 0 and 100");
                }
            }
            
            // Check if coupon code already exists
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM coupons WHERE coupon_code = ?");
            $checkStmt->execute([trim($coupon['coupon_code'])]);
            
            if ($checkStmt->fetchColumn() > 0) {
                throw new Exception("Coupon code already exists: " . $coupon['coupon_code']);
            }
            
            // Insert the coupon
            $insertStmt = $pdo->prepare("
                INSERT INTO coupons (
                    discount_text, coupon_code, discount_value, 
                    week1_probability, week2_probability, week3_probability, week4_probability, 
                    color, is_active, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            
            $insertStmt->execute([
                trim($coupon['discount_text']),
                trim($coupon['coupon_code']),
                (int)$coupon['discount_value'],
                (int)$coupon['week1_probability'],
                (int)$coupon['week2_probability'],
                (int)$coupon['week3_probability'],
                (int)$coupon['week4_probability'],
                trim($coupon['color'])
            ]);
            
            $successCount++;
            
        } catch (Exception $e) {
            $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
        }
    }
    
    // If there are errors, rollback the transaction
    if (!empty($errors)) {
        $pdo->rollback();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Upload failed due to validation errors',
            'errors' => $errors
        ]);
        exit;
    }
    
    // Commit the transaction
    $pdo->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Coupons uploaded successfully',
        'count' => $successCount
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    error_log("Bulk upload error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>