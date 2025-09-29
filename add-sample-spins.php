<?php
require_once 'api/config.php';

echo "<h2>➕ Add Sample Spin Data</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
</style>";

try {
    // Check current count
    $currentCount = $pdo->query("SELECT COUNT(*) FROM spins")->fetchColumn();
    echo "<div class='info'>Current spins in database: <strong>$currentCount</strong></div>";
    
    if ($currentCount == 0) {
        echo "<div class='info'>Adding sample spin data...</div>";
        
        // Sample spin data with realistic timestamps and coupon codes
        $sampleSpins = [
            ['USER001', '10K Discount', 'OASIS10K', time() * 1000, '103.47.12.45'],
            ['USER002', '15K Discount', 'OASIS15K', (time() - 1800) * 1000, '192.168.1.100'],
            ['USER003', 'Free IVF', 'OASISIVF', (time() - 3600) * 1000, '10.0.0.15'],
            ['USER004', '20K Discount', 'OASIS20K', (time() - 5400) * 1000, '172.16.0.25'],
            ['USER005', '50K Discount', 'OASIS50K', (time() - 7200) * 1000, '203.0.113.50'],
            ['USER006', '10K Discount', 'OASIS10K', (time() - 9000) * 1000, '198.51.100.75'],
            ['USER007', '15K Discount', 'OASIS15K', (time() - 10800) * 1000, '104.25.83.12'],
            ['USER008', '1 Lakh Discount', 'OASIS1L', (time() - 12600) * 1000, '185.199.108.153'],
            ['USER009', '10K Discount', 'OASIS10K', (time() - 14400) * 1000, '151.101.193.140'],
            ['USER010', 'Free IVF', 'OASISIVF', (time() - 16200) * 1000, '13.107.42.14']
        ];
        
        $insertStmt = $pdo->prepare("INSERT INTO spins (recorded_id, result, coupon_code, timestamp, ip_address) VALUES (?, ?, ?, ?, ?)");
        
        $successCount = 0;
        foreach ($sampleSpins as $spin) {
            try {
                $insertStmt->execute($spin);
                $successCount++;
                echo "<div class='success'>✅ Added: {$spin[0]} - {$spin[1]}</div>";
            } catch (Exception $e) {
                echo "<div class='error'>❌ Failed to add {$spin[0]}: " . $e->getMessage() . "</div>";
            }
        }
        
        echo "<div class='success'>✅ Successfully added $successCount sample spins!</div>";
        
    } else {
        echo "<div class='info'>Database already has spin data. Skipping sample data insertion.</div>";
        
        // Show recent spins
        echo "<h3>📝 Recent Spins (Last 5)</h3>";
        $recentSpins = $pdo->query("SELECT * FROM spins ORDER BY timestamp DESC LIMIT 5")->fetchAll();
        
        if (count($recentSpins) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
            echo "<tr style='background: #f2f2f2;'><th>Recorded ID</th><th>Result</th><th>Coupon Code</th><th>Timestamp</th><th>IP Address</th></tr>";
            foreach ($recentSpins as $spin) {
                $formattedDate = date('Y-m-d H:i:s', $spin['timestamp'] / 1000);
                echo "<tr>";
                echo "<td>{$spin['recorded_id']}</td>";
                echo "<td>{$spin['result']}</td>";
                echo "<td><strong>{$spin['coupon_code']}</strong></td>";
                echo "<td>{$formattedDate}</td>";
                echo "<td>{$spin['ip_address']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // Final count
    $finalCount = $pdo->query("SELECT COUNT(*) FROM spins")->fetchColumn();
    echo "<div class='info'>Final spins count: <strong>$finalCount</strong></div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
?>

<div style="margin-top: 30px; text-align: center;">
    <h3>🎯 Next Steps</h3>
    <p>Sample data has been added. Now test the admin panel Spin History tab.</p>
    <a href="admin.php" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        🔧 Test Admin Panel
    </a>
    <a href="debug-admin-api.php" style="background: #22c55e; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">
        🔍 Debug API
    </a>
</div>