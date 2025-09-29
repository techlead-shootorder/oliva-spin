<?php
session_start();
require_once 'api/config.php';

// Simple test of the API endpoint
echo "<h2>🧪 Test API Endpoint</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>";

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    echo "<div class='error'>❌ Not logged in as admin</div>";
    echo "<a href='login.php'>Login as Admin</a>";
    exit;
}

echo "<div class='success'>✅ Logged in as admin</div>";

// Test direct database query
echo "<h3>1. Direct Database Query</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM coupons WHERE is_active = 1 ORDER BY discount_value ASC");
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='success'>✅ Found " . count($coupons) . " active coupons:</div>";
    
    if (empty($coupons)) {
        echo "<div class='error'>❌ No coupons found! Create some first.</div>";
        echo "<a href='admin.php' style='background: #667eea; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Go to Admin Panel</a>";
    } else {
        echo "<pre>" . print_r($coupons, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}

// Test API endpoint
echo "<h3>2. Test API Call</h3>";
$ch = curl_init();
$apiUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/admin-data.php?action=coupons';

curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Copy session cookies
$cookieHeader = '';
if (isset($_COOKIE)) {
    $cookies = [];
    foreach ($_COOKIE as $name => $value) {
        $cookies[] = $name . '=' . $value;
    }
    $cookieHeader = implode('; ', $cookies);
    curl_setopt($ch, CURLOPT_COOKIE, $cookieHeader);
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<div class='info'>API URL: $apiUrl</div>";
echo "<div class='info'>HTTP Code: $httpCode</div>";

if ($error) {
    echo "<div class='error'>❌ cURL Error: $error</div>";
} else {
    echo "<div class='success'>✅ API Response:</div>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $data = json_decode($response, true);
    if ($data && isset($data['success'])) {
        if ($data['success']) {
            echo "<div class='success'>✅ API working correctly</div>";
        } else {
            echo "<div class='error'>❌ API returned error: " . ($data['message'] ?? 'Unknown error') . "</div>";
        }
    } else {
        echo "<div class='error'>❌ Invalid JSON response</div>";
    }
}
?>

<div style="margin-top: 30px; text-align: center;">
    <a href="manage-coupon-codes.php" style="background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        🎫 Try Manage Codes Again
    </a>
</div>