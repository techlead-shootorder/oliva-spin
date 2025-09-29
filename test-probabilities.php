<?php
require_once 'api/config.php';

echo "<h1>🎯 Probability System Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
    .test-section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; font-weight: bold; }
    .prob-bar { height: 20px; background: linear-gradient(to right, #667eea, #764ba2); border-radius: 3px; }
</style>";

echo "<div class='container'>";

// Test 1: Current Week Detection
echo "<div class='test-section'>";
echo "<h2>📅 Current Week Detection</h2>";
$currentWeek = getCurrentWeek();
echo "<div class='success'>Current Week: $currentWeek</div>";

// Check weekly settings
$stmt = $pdo->prepare("SELECT * FROM weekly_settings ORDER BY id DESC LIMIT 1");
$stmt->execute();
$weekSettings = $stmt->fetch();

if ($weekSettings) {
    echo "<div class='info'>Week Start Date: {$weekSettings['week_start_date']}</div>";
    echo "<div class='info'>Configured Week: {$weekSettings['current_week']}</div>";
    
    $weekStart = new DateTime($weekSettings['week_start_date']);
    $now = new DateTime();
    $daysSinceStart = $now->diff($weekStart)->days;
    echo "<div class='info'>Days Since Week Start: $daysSinceStart</div>";
    
    if ($daysSinceStart >= 7) {
        echo "<div class='warning'>⚠️ Week should auto-rotate soon (7+ days passed)</div>";
    }
} else {
    echo "<div class='error'>❌ No weekly settings found!</div>";
}
echo "</div>";

// Test 2: Coupon Probabilities for Current Week
echo "<div class='test-section'>";
echo "<h2>🎁 Current Week Probabilities (Week $currentWeek)</h2>";
$coupons = getCouponProbabilities();

if (empty($coupons)) {
    echo "<div class='error'>❌ No active coupons found!</div>";
} else {
    echo "<table>";
    echo "<tr><th>Discount</th><th>Code</th><th>Week $currentWeek Probability</th><th>Visual</th><th>Status</th></tr>";
    
    $totalProb = 0;
    foreach ($coupons as $coupon) {
        $prob = $coupon['probability'];
        $totalProb += $prob;
        $barWidth = ($prob / 100) * 200; // Scale for visual bar
        
        echo "<tr>";
        echo "<td style='color: {$coupon['color']}'>{$coupon['discount_text']}</td>";
        echo "<td><code>{$coupon['coupon_code']}</code></td>";
        echo "<td><strong>{$prob}%</strong></td>";
        echo "<td><div class='prob-bar' style='width: {$barWidth}px;'></div></td>";
        echo "<td>" . ($coupon['is_active'] ? '✅ Active' : '❌ Inactive') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='info'><strong>Total Probability: {$totalProb}%</strong></div>";
    if (abs($totalProb - 100) > 0.01) {
        echo "<div class='warning'>⚠️ Warning: Total probability should be 100%, current is {$totalProb}%</div>";
    } else {
        echo "<div class='success'>✅ Probability distribution is correct!</div>";
    }
}
echo "</div>";

// Test 3: Probability Algorithm Test
echo "<div class='test-section'>";
echo "<h2>🎲 Probability Algorithm Test (1000 simulations)</h2>";

if (!empty($coupons)) {
    $testResults = [];
    $totalTests = 1000;
    
    // Initialize counters
    foreach ($coupons as $coupon) {
        $testResults[$coupon['id']] = [
            'name' => $coupon['discount_text'],
            'expected_prob' => $coupon['probability'],
            'count' => 0,
            'color' => $coupon['color']
        ];
    }
    
    // Run simulation
    for ($i = 0; $i < $totalTests; $i++) {
        $winningCoupon = selectWinningCoupon();
        if ($winningCoupon && isset($testResults[$winningCoupon['id']])) {
            $testResults[$winningCoupon['id']]['count']++;
        }
    }
    
    // Display results
    echo "<table>";
    echo "<tr><th>Discount</th><th>Expected %</th><th>Actual Count</th><th>Actual %</th><th>Difference</th><th>Status</th></tr>";
    
    foreach ($testResults as $result) {
        $actualPercent = ($result['count'] / $totalTests) * 100;
        $difference = $actualPercent - $result['expected_prob'];
        $status = abs($difference) <= 3 ? '✅ Good' : '⚠️ Check'; // Allow 3% variance
        
        echo "<tr>";
        echo "<td style='color: {$result['color']}'>{$result['name']}</td>";
        echo "<td>{$result['expected_prob']}%</td>";
        echo "<td>{$result['count']}</td>";
        echo "<td>" . number_format($actualPercent, 1) . "%</td>";
        echo "<td>" . ($difference >= 0 ? '+' : '') . number_format($difference, 1) . "%</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='info'>💡 Small variations are normal in probability testing. Differences under 3% are acceptable.</div>";
} else {
    echo "<div class='error'>❌ Cannot test - no coupons available</div>";
}
echo "</div>";

// Test 4: Weekly Probability Variations
echo "<div class='test-section'>";
echo "<h2>📊 All Weekly Probability Variations</h2>";

$stmt = $pdo->prepare("SELECT * FROM coupons WHERE is_active = 1 ORDER BY discount_value");
$stmt->execute();
$allCoupons = $stmt->fetchAll();

if (!empty($allCoupons)) {
    echo "<table>";
    echo "<tr><th>Discount</th><th>Week 1</th><th>Week 2</th><th>Week 3</th><th>Week 4</th><th>Current Week</th></tr>";
    
    foreach ($allCoupons as $coupon) {
        $currentWeekProb = $coupon["week{$currentWeek}_probability"];
        echo "<tr>";
        echo "<td style='color: {$coupon['color']}'>{$coupon['discount_text']}</td>";
        echo "<td>{$coupon['week1_probability']}%</td>";
        echo "<td>{$coupon['week2_probability']}%</td>";
        echo "<td>{$coupon['week3_probability']}%</td>";
        echo "<td>{$coupon['week4_probability']}%</td>";
        echo "<td><strong style='background: yellow; padding: 2px 4px;'>{$currentWeekProb}%</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check totals for each week
    echo "<h3>Weekly Totals Check:</h3>";
    for ($week = 1; $week <= 4; $week++) {
        $total = 0;
        foreach ($allCoupons as $coupon) {
            $total += $coupon["week{$week}_probability"];
        }
        $status = abs($total - 100) <= 0.01 ? '✅' : '❌';
        echo "<div>Week $week Total: <strong>$total%</strong> $status</div>";
    }
} else {
    echo "<div class='error'>❌ No coupons found</div>";
}
echo "</div>";

// Test 5: Sample Spin Test
echo "<div class='test-section'>";
echo "<h2>🎮 Sample Spin Test</h2>";
echo "<div>Testing the actual spin mechanism...</div>";

try {
    // Simulate a spin
    $testRecordedId = 'TEST_' . time();
    
    echo "<div class='info'>Test User ID: $testRecordedId</div>";
    
    // Check if user can spin
    $canSpin = canUserSpin($testRecordedId);
    echo "<div>Can Spin: " . ($canSpin ? '✅ Yes' : '❌ No') . "</div>";
    
    if ($canSpin) {
        // Get winning coupon
        $winningCoupon = selectWinningCoupon();
        
        if ($winningCoupon) {
            echo "<div class='success'>🎉 Winning Result:</div>";
            echo "<div style='color: {$winningCoupon['color']}; font-size: 18px; font-weight: bold;'>";
            echo "{$winningCoupon['discount_text']} - {$winningCoupon['coupon_code']}";
            echo "</div>";
            echo "<div>Value: {$winningCoupon['discount_value']}</div>";
            echo "<div>Week $currentWeek Probability: {$winningCoupon['probability']}%</div>";
            
            // Note: We don't actually increment the user spin count in this test
            echo "<div class='info'>💡 This is a test spin - user count not incremented</div>";
        } else {
            echo "<div class='error'>❌ No winning coupon selected</div>";
        }
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Spin test failed: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 6: Performance Check
echo "<div class='test-section'>";
echo "<h2>⚡ Performance Check</h2>";

$startTime = microtime(true);
for ($i = 0; $i < 100; $i++) {
    selectWinningCoupon();
}
$endTime = microtime(true);
$executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

echo "<div>100 probability calculations completed in: <strong>" . number_format($executionTime, 2) . " ms</strong></div>";
echo "<div>Average per calculation: <strong>" . number_format($executionTime / 100, 2) . " ms</strong></div>";

if ($executionTime < 100) {
    echo "<div class='success'>✅ Performance is excellent!</div>";
} elseif ($executionTime < 500) {
    echo "<div class='info'>✅ Performance is good</div>";
} else {
    echo "<div class='warning'>⚠️ Performance may need optimization</div>";
}
echo "</div>";

echo "</div>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Probability Test Results</title>
</head>
<body>
    <div style="text-align: center; margin: 20px;">
        <h3>🎯 Test Complete!</h3>
        <p>The probability system is now verified. Check each section above for detailed results.</p>
        <a href="admin.php" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            🔧 Go to Admin Panel
        </a>
        <a href="index.php?recordedId=test<?php echo time(); ?>" style="background: #22c55e; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">
            🎲 Test Live Spin
        </a>
    </div>
</body>
</html>