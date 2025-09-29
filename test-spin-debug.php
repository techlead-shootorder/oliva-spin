<?php
require_once 'api/config.php';

echo "<h2>🔧 Spin Debug Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
</style>";

$testRecordedId = 'DEBUGTEST123';

echo "<div class='section'>";
echo "<h3>🧪 Testing Spin Functions</h3>";

// Test 1: Database connection
echo "<h4>1. Database Connection</h4>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "<div class='success'>✅ Database connected</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
    exit;
}

// Test 2: Check tables
echo "<h4>2. Required Tables</h4>";
$requiredTables = ['coupons', 'spins', 'user_spins'];
foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "<div class='success'>✅ Table '$table' exists with $count records</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ Table '$table' error: " . $e->getMessage() . "</div>";
    }
}

// Test 3: Check functions
echo "<h4>3. Required Functions</h4>";
$functions = ['canUserSpin', 'selectWinningCoupon', 'incrementUserSpin', 'getCurrentWeek', 'getClientIP'];
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "<div class='success'>✅ Function $func exists</div>";
    } else {
        echo "<div class='error'>❌ Function $func missing</div>";
    }
}

// Test 4: Test canUserSpin
echo "<h4>4. Test canUserSpin</h4>";
try {
    $canSpin = canUserSpin($testRecordedId);
    echo "<div class='info'>canUserSpin('$testRecordedId'): " . ($canSpin ? 'true' : 'false') . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ canUserSpin error: " . $e->getMessage() . "</div>";
}

// Test 5: Test selectWinningCoupon
echo "<h4>5. Test selectWinningCoupon</h4>";
try {
    $winner = selectWinningCoupon();
    if ($winner) {
        echo "<div class='success'>✅ Winner selected:</div>";
        echo "<pre>" . print_r($winner, true) . "</pre>";
    } else {
        echo "<div class='error'>❌ No winner selected</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ selectWinningCoupon error: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 6: Test tier_coupon_codes table
echo "<h4>6. Test tier_coupon_codes Table</h4>";
try {
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'tier_coupon_codes'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM tier_coupon_codes");
        $count = $stmt->fetchColumn();
        echo "<div class='success'>✅ tier_coupon_codes table exists with $count codes</div>";
        
        if ($count == 0) {
            echo "<div class='warning'>⚠️ No codes in tier_coupon_codes table. Add codes in 'Manage Codes'.</div>";
        }
    } else {
        echo "<div class='warning'>⚠️ tier_coupon_codes table doesn't exist. Will use fallback method.</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ tier_coupon_codes check error: " . $e->getMessage() . "</div>";
}

// Test 7: Test unique code generation
echo "<h4>7. Test generateUniqueCouponCode</h4>";
if (function_exists('generateUniqueCouponCode')) {
    try {
        $uniqueCode = generateUniqueCouponCode('OASIS10K', $testRecordedId);
        echo "<div class='success'>✅ Generated unique code: $uniqueCode</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ generateUniqueCouponCode error: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='warning'>⚠️ generateUniqueCouponCode function not found. Will use simple fallback.</div>";
}

echo "</div>";

// Test the actual API call
echo "<div class='section'>";
echo "<h3>🎯 Test Actual Spin API</h3>";

$testData = json_encode(['recordedId' => $testRecordedId]);
$ch = curl_init();
$apiUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/spin-fixed.php';

curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $testData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<div class='info'>Testing: $apiUrl</div>";
echo "<div class='info'>HTTP Code: $httpCode</div>";

if ($error) {
    echo "<div class='error'>❌ cURL Error: $error</div>";
} else {
    if ($httpCode == 200) {
        echo "<div class='success'>✅ API call successful!</div>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    } else {
        echo "<div class='error'>❌ API returned HTTP $httpCode</div>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
}

echo "</div>";
?>

<div style="margin-top: 30px; text-align: center;">
    <h3>🎯 Quick Fixes</h3>
    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;">
        <p><strong>If you see errors above:</strong></p>
        <p>1. Replace api/spin.php with api/spin-fixed.php</p>
        <p>2. Add discount codes in "Manage Codes" if using tier system</p>
        <p>3. Check error logs for detailed error messages</p>
    </div>
    
    <div style="margin: 20px 0;">
        <a href="manage-coupon-codes.php" style="background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">
            🎫 Manage Codes
        </a>
        <a href="index.php?recordedId=<?php echo $testRecordedId; ?>" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">
            🎲 Test Real Spin
        </a>
    </div>
</div>