<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

require_once 'api/config.php';

// Test the API call directly
echo "<h2>🔍 Debug Manage Codes Page</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>";

echo "<h3>1. Testing Database Connection</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM coupons");
    $count = $stmt->fetchColumn();
    echo "<div class='success'>✅ Database connected. Found $count coupons in database.</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}

echo "<h3>2. Testing API Endpoint</h3>";
try {
    // Simulate the API call
    $stmt = $pdo->query("SELECT * FROM coupons WHERE is_active = 1 ORDER BY discount_value ASC");
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='success'>✅ Found " . count($coupons) . " active coupons:</div>";
    echo "<pre>" . print_r($coupons, true) . "</pre>";
    
    if (empty($coupons)) {
        echo "<div class='error'>❌ No active coupons found! You need to create discount tiers first.</div>";
        echo "<div class='info'>💡 Go to Admin Panel → Add New Coupon to create discount tiers like:</div>";
        echo "<ul>";
        echo "<li>₹10,000 Discount (OASIS10K)</li>";
        echo "<li>₹15,000 Discount (OASIS15K)</li>";
        echo "<li>₹20,000 Discount (OASIS20K)</li>";
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ API simulation error: " . $e->getMessage() . "</div>";
}

echo "<h3>3. Testing Actual API URL</h3>";
$apiUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/admin-data.php?action=coupons';
echo "<div class='info'>API URL: <a href='$apiUrl' target='_blank'>$apiUrl</a></div>";

echo "<h3>4. Testing tier_coupon_codes Table</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM tier_coupon_codes");
    $count = $stmt->fetchColumn();
    echo "<div class='success'>✅ tier_coupon_codes table exists with $count codes.</div>";
} catch (Exception $e) {
    echo "<div class='info'>ℹ️ tier_coupon_codes table doesn't exist yet (will be created when you add codes).</div>";
}

echo "<h3>5. Quick Fix</h3>";
echo "<div class='info'>";
echo "<p><strong>If you see 'No active coupons found' above:</strong></p>";
echo "<ol>";
echo "<li>Go to <a href='admin.php'>Admin Panel</a></li>";
echo "<li>Click 'Add New Coupon' button</li>";
echo "<li>Create discount tiers like:</li>";
echo "<ul>";
echo "<li>Discount Text: ₹10,000 Discount</li>";
echo "<li>Coupon Code: OASIS10K</li>";
echo "<li>Discount Value: 10000</li>";
echo "<li>Set probabilities for each week</li>";
echo "</ul>";
echo "<li>Then come back to <a href='manage-coupon-codes.php'>Manage Codes</a></li>";
echo "</ol>";
echo "</div>";
?>

<div style="margin-top: 30px; text-align: center;">
    <a href="admin.php" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">
        🔧 Go to Admin Panel
    </a>
    <a href="manage-coupon-codes.php" style="background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;">
        🎫 Try Manage Codes Again
    </a>
</div>