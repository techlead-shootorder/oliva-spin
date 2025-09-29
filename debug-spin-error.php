<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug Spin Button Error</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
</style>";

try {
    echo "<div class='section'>";
    echo "<h3>🔗 Database Connection Test</h3>";
    require_once 'api/config.php';
    echo "<div class='success'>✅ Config loaded successfully</div>";
    echo "</div>";

    echo "<div class='section'>";
    echo "<h3>📊 Table Structure Check</h3>";
    
    // Check spins table structure
    $columns = $pdo->query("DESCRIBE spins")->fetchAll();
    echo "<div class='info'>Current spins table structure:</div>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f2f2f2;'><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $hasCouponCode = false;
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'coupon_code') {
            $hasCouponCode = true;
        }
    }
    echo "</table>";
    
    if ($hasCouponCode) {
        echo "<div class='success'>✅ coupon_code column exists</div>";
    } else {
        echo "<div class='warning'>⚠️ coupon_code column does NOT exist</div>";
    }
    echo "</div>";

    echo "<div class='section'>";
    echo "<h3>🧪 Test Spin Functions</h3>";
    
    // Test getCurrentWeek function
    echo "<h4>Test 1: getCurrentWeek()</h4>";
    try {
        $currentWeek = getCurrentWeek();
        echo "<div class='success'>✅ Current week: $currentWeek</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ getCurrentWeek failed: " . $e->getMessage() . "</div>";
    }
    
    // Test getCouponProbabilities function
    echo "<h4>Test 2: getCouponProbabilities()</h4>";
    try {
        $coupons = getCouponProbabilities();
        echo "<div class='success'>✅ Found " . count($coupons) . " active coupons</div>";
        if (count($coupons) > 0) {
            echo "<div class='info'>Sample coupon:</div>";
            echo "<pre>" . json_encode($coupons[0], JSON_PRETTY_PRINT) . "</pre>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ getCouponProbabilities failed: " . $e->getMessage() . "</div>";
    }
    
    // Test selectWinningCoupon function
    echo "<h4>Test 3: selectWinningCoupon()</h4>";
    try {
        $winner = selectWinningCoupon();
        if ($winner) {
            echo "<div class='success'>✅ Selected winning coupon: {$winner['discount_text']}</div>";
            echo "<div class='info'>Coupon details:</div>";
            echo "<pre>" . json_encode($winner, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<div class='error'>❌ No winning coupon selected</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ selectWinningCoupon failed: " . $e->getMessage() . "</div>";
    }
    
    // Test canUserSpin function
    echo "<h4>Test 4: canUserSpin()</h4>";
    try {
        $testId = 'DEBUG_' . time();
        $canSpin = canUserSpin($testId);
        echo "<div class='success'>✅ canUserSpin test: " . ($canSpin ? 'User can spin' : 'User cannot spin') . "</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ canUserSpin failed: " . $e->getMessage() . "</div>";
    }
    echo "</div>";

    echo "<div class='section'>";
    echo "<h3>🎲 Test Complete Spin Process</h3>";
    
    $testRecordedId = 'DEBUGTEST_' . time();
    echo "<div class='info'>Testing with recorded ID: $testRecordedId</div>";
    
    try {
        // Step 1: Check if user can spin
        if (!canUserSpin($testRecordedId)) {
            throw new Exception('User cannot spin (spin limit reached)');
        }
        echo "<div class='success'>✅ User can spin</div>";
        
        // Step 2: Get winning coupon
        $winningCoupon = selectWinningCoupon();
        if (!$winningCoupon) {
            throw new Exception('No active coupons available');
        }
        echo "<div class='success'>✅ Winning coupon selected: {$winningCoupon['discount_text']}</div>";
        
        // Step 3: Try to insert into database (test both scenarios)
        if ($hasCouponCode) {
            echo "<div class='info'>Testing with coupon_code column...</div>";
            $stmt = $pdo->prepare("INSERT INTO spins (recorded_id, result, coupon_code, timestamp, ip_address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $testRecordedId,
                $winningCoupon['discount_text'],
                $winningCoupon['coupon_code'],
                time() * 1000,
                '127.0.0.1'
            ]);
        } else {
            echo "<div class='info'>Testing without coupon_code column...</div>";
            $stmt = $pdo->prepare("INSERT INTO spins (recorded_id, result, timestamp, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $testRecordedId,
                $winningCoupon['discount_text'],
                time() * 1000,
                '127.0.0.1'
            ]);
        }
        
        echo "<div class='success'>✅ Database insert successful</div>";
        
        // Step 4: Increment user spin count
        incrementUserSpin($testRecordedId);
        echo "<div class='success'>✅ User spin count incremented</div>";
        
        echo "<div class='success'>🎉 Complete spin process test: SUCCESS</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Spin process failed: " . $e->getMessage() . "</div>";
        echo "<div class='error'>Stack trace:</div>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<div class='error'>❌ Critical Error: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>

<div style="margin-top: 30px; text-align: center;">
    <h3>🎯 Next Steps Based on Results</h3>
    <p>If coupon_code column is missing, run the migration script first.</p>
    <a href="migrate-add-coupon-code.php" style="background: #f59e0b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        🔄 Run Migration
    </a>
    <a href="index.php?recordedId=test<?php echo time(); ?>" style="background: #22c55e; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">
        🎲 Test Spin
    </a>
</div>