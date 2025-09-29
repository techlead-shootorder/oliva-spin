<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo 'Unauthorized';
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
        echo 'No coupon codes generated yet.';
        exit;
    }
    
    // Get all coupon codes with their status
    $stmt = $pdo->query("
        SELECT 
            ucc.unique_code,
            c.discount_text,
            c.coupon_code as base_code,
            c.discount_value,
            ucc.is_used,
            ucc.used_by_recorded_id,
            ucc.used_at,
            ucc.created_at
        FROM unique_coupon_codes ucc
        JOIN coupons c ON ucc.discount_tier_id = c.id
        ORDER BY c.discount_text, ucc.created_at
    ");
    $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="coupon_codes_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // Create CSV content
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'Unique Code',
        'Discount Text',
        'Base Code',
        'Discount Value',
        'Status',
        'Used By',
        'Used At',
        'Created At'
    ]);
    
    // CSV data
    foreach ($codes as $code) {
        fputcsv($output, [
            $code['unique_code'],
            $code['discount_text'],
            $code['base_code'],
            $code['discount_value'],
            $code['is_used'] ? 'Used' : 'Available',
            $code['used_by_recorded_id'] ?? '',
            $code['used_at'] ?? '',
            $code['created_at']
        ]);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    error_log("Download coupon codes error: " . $e->getMessage());
    echo 'Error generating download: ' . $e->getMessage();
}
?>