<?php
require_once 'api/config.php';

echo "<h2>🎯 Test Unique Coupon Code Generation</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .code { font-family: monospace; background: #f5f5f5; padding: 2px 4px; border-radius: 3px; }
</style>";

try {
    echo "<div class='section'>";
    echo "<h3>🧪 Testing Unique Code Generation Functions</h3>";
    
    // Test the generateCouponSuffix function
    echo "<h4>Test 1: Generate Random Suffixes</h4>";
    echo "<table>";
    echo "<tr><th>#</th><th>Method</th><th>Generated Suffix</th><th>Length</th></tr>";
    
    for ($i = 1; $i <= 5; $i++) {
        $suffix1 = generateCouponSuffix();
        $suffix2 = generateCouponSuffix("USER$i");
        
        echo "<tr>";
        echo "<td>$i</td>";
        echo "<td>Random</td>";
        echo "<td class='code'>$suffix1</td>";
        echo "<td>" . strlen($suffix1) . "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td>$i</td>";
        echo "<td>With RecordedId</td>";
        echo "<td class='code'>$suffix2</td>";
        echo "<td>" . strlen($suffix2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test the complete unique code generation
    echo "<h4>Test 2: Generate Complete Unique Codes</h4>";
    $baseCodes = ['OASIS10K', 'OASIS15K', 'OASIS20K', 'OASIS50K', 'OASIS1L', 'OASISIVF'];
    
    echo "<table>";
    echo "<tr><th>Base Code</th><th>Generated Unique Code</th><th>Is Unique?</th></tr>";
    
    foreach ($baseCodes as $baseCode) {
        $uniqueCode = generateUniqueCouponCode($baseCode, 'TEST' . rand(100, 999));
        $isUnique = isCouponCodeUnique($uniqueCode);
        
        echo "<tr>";
        echo "<td class='code'>$baseCode</td>";
        echo "<td class='code' style='font-weight: bold; color: green;'>$uniqueCode</td>";
        echo "<td>" . ($isUnique ? '✅ Yes' : '❌ No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test multiple generations from same base
    echo "<h4>Test 3: Multiple Codes from Same Base (10K Discount)</h4>";
    echo "<table>";
    echo "<tr><th>#</th><th>User ID</th><th>Generated Code</th><th>Suffix</th></tr>";
    
    for ($i = 1; $i <= 8; $i++) {
        $userId = "USER" . sprintf("%03d", $i);
        $uniqueCode = generateUniqueCouponCode('OASIS10K', $userId);
        $suffix = substr($uniqueCode, strpos($uniqueCode, '-') + 1);
        
        echo "<tr>";
        echo "<td>$i</td>";
        echo "<td class='code'>$userId</td>";
        echo "<td class='code' style='font-weight: bold; color: blue;'>$uniqueCode</td>";
        echo "<td class='code'>$suffix</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test simulation of actual spins
    echo "<h4>Test 4: Simulate Real Spin Scenarios</h4>";
    
    $coupons = getCouponProbabilities();
    if (!empty($coupons)) {
        echo "<div class='info'>Found " . count($coupons) . " active coupons. Simulating spins...</div>";
        
        echo "<table>";
        echo "<tr><th>Spin #</th><th>User ID</th><th>Discount Won</th><th>Base Code</th><th>Unique Code Generated</th></tr>";
        
        for ($spin = 1; $spin <= 10; $spin++) {
            $winningCoupon = selectWinningCoupon();
            $userId = "SPIN" . sprintf("%03d", $spin);
            $uniqueCode = generateUniqueCouponCode($winningCoupon['coupon_code'], $userId);
            
            echo "<tr>";
            echo "<td>$spin</td>";
            echo "<td class='code'>$userId</td>";
            echo "<td>{$winningCoupon['discount_text']}</td>";
            echo "<td class='code'>{$winningCoupon['coupon_code']}</td>";
            echo "<td class='code' style='font-weight: bold; color: green;'>$uniqueCode</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ No active coupons found for simulation</div>";
    }
    
    // Test uniqueness check
    echo "<h4>Test 5: Uniqueness Validation</h4>";
    $testCode1 = generateUniqueCouponCode('TESTCODE', 'UNIQUE1');
    $testCode2 = generateUniqueCouponCode('TESTCODE', 'UNIQUE2');
    
    echo "<div class='info'>Generated Code 1: <span class='code'>$testCode1</span></div>";
    echo "<div class='info'>Generated Code 2: <span class='code'>$testCode2</span></div>";
    echo "<div class='info'>Are they different? " . ($testCode1 !== $testCode2 ? '✅ Yes' : '❌ No') . "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Test failed: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<div style="margin-top: 30px; text-align: center;">
    <h3>🎯 Code Generation Summary</h3>
    <div style="background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 10px 0;">
        <p><strong>How it works:</strong></p>
        <ul style="text-align: left; margin: 10px 0;">
            <li><strong>Base Template:</strong> OASIS10K (defined in admin panel)</li>
            <li><strong>Unique Generation:</strong> OASIS10K-A1B2C3 (6-character suffix)</li>
            <li><strong>Suffix Methods:</strong> Hash-based (with user ID) or Random</li>
            <li><strong>Collision Prevention:</strong> Database check ensures uniqueness</li>
            <li><strong>Fallback:</strong> Timestamp-based suffix if all attempts fail</li>
        </ul>
    </div>
    <a href="index.php?recordedId=test<?php echo time(); ?>" style="background: #22c55e; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        🎲 Test Real Spin
    </a>
    <a href="admin.php" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">
        🔧 Admin Panel
    </a>
</div>