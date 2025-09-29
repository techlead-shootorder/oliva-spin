<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug URL and Spin Issues</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

echo "<div class='section'>";
echo "<h3>🌐 URL Analysis</h3>";
echo "<div class='info'>Current URL: " . $_SERVER['REQUEST_URI'] . "</div>";
echo "<div class='info'>Server Name: " . $_SERVER['SERVER_NAME'] . "</div>";
echo "<div class='info'>Request Method: " . $_SERVER['REQUEST_METHOD'] . "</div>";

// Check URL parameters
if (isset($_GET['recordedId'])) {
    echo "<div class='success'>✅ recordedId parameter found: " . htmlspecialchars($_GET['recordedId']) . "</div>";
} else {
    echo "<div class='warning'>⚠️ No recordedId parameter in URL</div>";
}

// Show all GET parameters
echo "<div class='info'>All GET parameters:</div>";
echo "<pre>" . print_r($_GET, true) . "</pre>";
echo "</div>";

echo "<div class='section'>";
echo "<h3>📁 File System Check</h3>";

$files = [
    'index.php' => 'Main spin wheel page',
    'api/config.php' => 'Database configuration',
    'api/spin.php' => 'Spin API endpoint',
    'api/spin-working.php' => 'Backup spin endpoint',
    '.htaccess' => 'URL rewrite rules'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "<div class='success'>✅ $file ($description) - $size bytes</div>";
    } else {
        echo "<div class='error'>❌ $file ($description) - NOT FOUND</div>";
    }
}
echo "</div>";

echo "<div class='section'>";
echo "<h3>🔗 Database Connection Test</h3>";
try {
    require_once 'api/config.php';
    echo "<div class='success'>✅ Database connected successfully</div>";
    
    // Test table existence
    $tables = ['spins', 'coupons', 'user_spins', 'weekly_settings', 'admin_users', 'settings'];
    foreach ($tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "<div class='success'>✅ Table '$table' exists with $count records</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Table '$table' error: " . $e->getMessage() . "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h3>🧪 Test Spin Functions</h3>";

if (isset($_GET['recordedId']) && !empty($_GET['recordedId'])) {
    $testId = $_GET['recordedId'];
    echo "<div class='info'>Testing with recordedId: $testId</div>";
    
    try {
        // Test each function individually
        echo "<h4>Step 1: Check if user can spin</h4>";
        $canSpin = canUserSpin($testId);
        echo "<div class='info'>Can spin: " . ($canSpin ? 'Yes' : 'No') . "</div>";
        
        echo "<h4>Step 2: Get current week</h4>";
        $currentWeek = getCurrentWeek();
        echo "<div class='info'>Current week: $currentWeek</div>";
        
        echo "<h4>Step 3: Get coupon probabilities</h4>";
        $coupons = getCouponProbabilities();
        echo "<div class='info'>Found " . count($coupons) . " active coupons</div>";
        
        if (count($coupons) > 0) {
            echo "<table>";
            echo "<tr><th>Discount</th><th>Code</th><th>Probability</th></tr>";
            foreach ($coupons as $coupon) {
                echo "<tr>";
                echo "<td>{$coupon['discount_text']}</td>";
                echo "<td>{$coupon['coupon_code']}</td>";
                echo "<td>{$coupon['probability']}%</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<h4>Step 4: Select winning coupon</h4>";
        $winner = selectWinningCoupon();
        if ($winner) {
            echo "<div class='success'>✅ Winner selected: {$winner['discount_text']}</div>";
            echo "<div class='info'>Code: {$winner['coupon_code']}</div>";
            echo "<div class='info'>Probability: {$winner['probability']}%</div>";
        } else {
            echo "<div class='error'>❌ No winner selected</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Function test failed: " . $e->getMessage() . "</div>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<div class='warning'>⚠️ No recordedId provided for testing. Add ?recordedId=TEST123 to URL</div>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h3>🎯 API Endpoint Test</h3>";
echo "<div class='info'>Testing spin API endpoints...</div>";

// Test API endpoints
$endpoints = [
    'api/spin.php' => 'Current spin endpoint',
    'api/spin-working.php' => 'Backup working endpoint',
    'api/admin-data.php?action=spins' => 'Admin spins data'
];

foreach ($endpoints as $endpoint => $description) {
    if (file_exists($endpoint)) {
        echo "<div class='success'>✅ $endpoint ($description) exists</div>";
    } else {
        echo "<div class='error'>❌ $endpoint ($description) NOT FOUND</div>";
    }
}
echo "</div>";
?>

<div style="margin-top: 30px; text-align: center;">
    <h3>🎯 Quick Fixes</h3>
    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;">
        <p><strong>URL Issue:</strong> The "/" before "?" is likely from a redirect or .htaccess rule</p>
        <p><strong>Internal Error:</strong> Check the database connection and table structure</p>
    </div>
    
    <div style="margin: 20px 0;">
        <h4>Test Links (use these URLs):</h4>
        <a href="?recordedId=DEBUG123" style="background: #17a2b8; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin: 5px;">
            Test Debug: ?recordedId=DEBUG123
        </a>
        <a href="index.php?recordedId=TEST456" style="background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin: 5px;">
            Test Index: index.php?recordedId=TEST456
        </a>
    </div>
    
    <div style="margin: 20px 0;">
        <h4>Quick Solutions:</h4>
        <p>1. Replace api/spin.php with api/spin-working.php</p>
        <p>2. Upload the .htaccess file to fix URL issues</p>
        <p>3. Run migrate-add-coupon-code.php if database is missing columns</p>
    </div>
</div>