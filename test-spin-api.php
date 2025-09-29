<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Spin API Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block; }
</style>";

// Test with a sample recordedId
$testRecordedId = isset($_GET['recordedId']) ? $_GET['recordedId'] : 'TEST' . time();

echo "<div class='info'>Testing with recordedId: <strong>$testRecordedId</strong></div>";

// Test the API endpoint
$testData = json_encode(['recordedId' => $testRecordedId]);
$url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/spin-bulletproof.php';

echo "<div class='info'>API URL: <strong>$url</strong></div>";

// Use cURL to test the API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $testData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h3>📡 API Response</h3>";
echo "<div class='info'>HTTP Status: <strong>$httpCode</strong></div>";

if ($error) {
    echo "<div class='error'>cURL Error: $error</div>";
} else {
    echo "<div class='success'>✅ API call successful</div>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Try to decode the response
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "<h4>📋 Parsed Response</h4>";
        if (isset($decoded['success']) && $decoded['success']) {
            echo "<div class='success'>✅ Spin successful!</div>";
            echo "<div class='info'>Discount: " . htmlspecialchars($decoded['result']['text']) . "</div>";
            echo "<div class='info'>Code: " . htmlspecialchars($decoded['result']['code']) . "</div>";
            echo "<div class='info'>Week: " . htmlspecialchars($decoded['currentWeek']) . "</div>";
        } else {
            echo "<div class='error'>❌ Spin failed: " . htmlspecialchars($decoded['error']) . "</div>";
        }
    }
}

// Test direct file access
echo "<h3>📁 Direct File Tests</h3>";

// Test config file
echo "<h4>Config Test</h4>";
try {
    require_once 'api/config.php';
    echo "<div class='success'>✅ Config loaded successfully</div>";
    
    if (isset($pdo)) {
        echo "<div class='success'>✅ Database connection available</div>";
    } else {
        echo "<div class='error'>❌ Database connection not available</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Config error: " . $e->getMessage() . "</div>";
}

// Test functions
echo "<h4>Function Tests</h4>";
$functions = ['canUserSpin', 'selectWinningCoupon', 'generateUniqueCouponCode', 'incrementUserSpin', 'getCurrentWeek'];

foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "<div class='success'>✅ Function $func exists</div>";
    } else {
        echo "<div class='error'>❌ Function $func missing</div>";
    }
}

echo "<div style='margin-top: 30px;'>";
echo "<a href='?recordedId=TEST" . time() . "' class='button'>🔄 Test Again</a>";
echo "<a href='api/spin-bulletproof.php' class='button'>📄 View Bulletproof File</a>";
echo "<a href='index.php?recordedId=$testRecordedId' class='button'>🎲 Try Real Spin</a>";
echo "</div>";
?>