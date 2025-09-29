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
    // Check if unique_coupon_codes table exists
    $tableExists = false;
    try {
        $pdo->query("SELECT 1 FROM unique_coupon_codes LIMIT 1");
        $tableExists = true;
    } catch (Exception $e) {
        // Table doesn't exist yet
    }
    
    if (!$tableExists) {
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_codes' => 0,
                'available_codes' => 0,
                'used_codes' => 0,
                'usage_rate' => 0
            ],
            'warnings' => ['No coupon codes generated yet. Use the generation tool to create codes.'],
            'tier_counts' => []
        ]);
        exit;
    }
    
    // Get overall statistics
    $stats = [];
    
    // Total codes
    $stmt = $pdo->query("SELECT COUNT(*) FROM unique_coupon_codes");
    $stats['total_codes'] = $stmt->fetchColumn();
    
    // Available codes
    $stmt = $pdo->query("SELECT COUNT(*) FROM unique_coupon_codes WHERE is_used = FALSE");
    $stats['available_codes'] = $stmt->fetchColumn();
    
    // Used codes
    $stats['used_codes'] = $stats['total_codes'] - $stats['available_codes'];
    
    // Usage rate
    $stats['usage_rate'] = $stats['total_codes'] > 0 ? round(($stats['used_codes'] / $stats['total_codes']) * 100, 1) : 0;
    
    // Get tier-specific counts
    $tierCountsStmt = $pdo->query("
        SELECT 
            ucc.discount_tier_id as tier_id,
            c.discount_text,
            c.coupon_code as base_code,
            COUNT(*) as total,
            SUM(CASE WHEN ucc.is_used = FALSE THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN ucc.is_used = TRUE THEN 1 ELSE 0 END) as used
        FROM unique_coupon_codes ucc
        JOIN coupons c ON ucc.discount_tier_id = c.id
        GROUP BY ucc.discount_tier_id, c.discount_text, c.coupon_code
        ORDER BY c.discount_text
    ");
    $tierCounts = $tierCountsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate warnings
    $warnings = [];
    
    foreach ($tierCounts as $tier) {
        $availablePercent = $tier['total'] > 0 ? ($tier['available'] / $tier['total']) * 100 : 0;
        
        if ($tier['available'] == 0) {
            $warnings[] = "⚠️ {$tier['discount_text']}: No codes available! All {$tier['total']} codes are used.";
        } elseif ($availablePercent < 10) {
            $warnings[] = "⚠️ {$tier['discount_text']}: Only {$tier['available']} codes remaining ({$availablePercent}% available).";
        } elseif ($availablePercent < 25) {
            $warnings[] = "⚠️ {$tier['discount_text']}: Running low on codes. {$tier['available']} codes remaining.";
        }
    }
    
    // Check for tiers with no codes generated
    $allTiersStmt = $pdo->query("
        SELECT c.id, c.discount_text, c.coupon_code
        FROM coupons c
        LEFT JOIN unique_coupon_codes ucc ON c.id = ucc.discount_tier_id
        WHERE ucc.discount_tier_id IS NULL
        AND c.is_active = 1
    ");
    $tiersWithNoCodes = $allTiersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tiersWithNoCodes as $tier) {
        $warnings[] = "⚠️ {$tier['discount_text']}: No codes generated yet!";
        // Add to tier counts with zero values
        $tierCounts[] = [
            'tier_id' => $tier['id'],
            'discount_text' => $tier['discount_text'],
            'base_code' => $tier['coupon_code'],
            'total' => 0,
            'available' => 0,
            'used' => 0
        ];
    }
    
    if (empty($warnings)) {
        $warnings[] = "✅ All discount tiers have sufficient codes available.";
    }
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'warnings' => $warnings,
        'tier_counts' => $tierCounts
    ]);
    
} catch (Exception $e) {
    error_log("Coupon code stats error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>