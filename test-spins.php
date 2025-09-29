<?php
require_once 'api/config.php';

echo "<h2>🎲 Spin Data Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
</style>";

try {
    // Test 1: Check if spins table exists
    echo "<h3>📋 Database Table Check</h3>";
    $tables = $pdo->query("SHOW TABLES LIKE 'spins'")->fetchAll();
    if (count($tables) > 0) {
        echo "<div class='success'>✅ Spins table exists</div>";
    } else {
        echo "<div class='error'>❌ Spins table does not exist!</div>";
        exit;
    }

    // Test 2: Check table structure
    echo "<h3>🔧 Table Structure</h3>";
    $columns = $pdo->query("DESCRIBE spins")->fetchAll();
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Test 3: Count total spins
    echo "<h3>📊 Spin Data Count</h3>";
    $totalSpins = $pdo->query("SELECT COUNT(*) FROM spins")->fetchColumn();
    echo "<div class='info'>Total spins in database: <strong>$totalSpins</strong></div>";

    // Test 4: Show recent spins if any
    if ($totalSpins > 0) {
        echo "<h3>📝 Recent Spins (Last 10)</h3>";
        $recentSpins = $pdo->query("SELECT * FROM spins ORDER BY timestamp DESC LIMIT 10")->fetchAll();
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Recorded ID</th><th>Result</th><th>Timestamp</th><th>IP Address</th><th>Created At</th></tr>";
        foreach ($recentSpins as $spin) {
            $formattedDate = date('Y-m-d H:i:s', $spin['timestamp'] / 1000); // Convert from JS timestamp
            echo "<tr>";
            echo "<td>{$spin['id']}</td>";
            echo "<td>{$spin['recorded_id']}</td>";
            echo "<td>{$spin['result']}</td>";
            echo "<td>{$formattedDate}</td>";
            echo "<td>{$spin['ip_address']}</td>";
            echo "<td>{$spin['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>No spins found in database.</div>";
        
        // Test 5: Add sample data
        echo "<h3>➕ Adding Sample Spin Data</h3>";
        
        $sampleSpins = [
            ['TEST001', '10K Discount', time() * 1000, '127.0.0.1'],
            ['TEST002', '15K Discount', (time() - 3600) * 1000, '192.168.1.1'],
            ['TEST003', 'Free IVF', (time() - 7200) * 1000, '10.0.0.1'],
            ['TEST004', '20K Discount', (time() - 10800) * 1000, '172.16.0.1'],
            ['TEST005', '50K Discount', (time() - 14400) * 1000, '203.0.113.1']
        ];
        
        $insertStmt = $pdo->prepare("INSERT INTO spins (recorded_id, result, timestamp, ip_address) VALUES (?, ?, ?, ?)");
        
        foreach ($sampleSpins as $spin) {
            $insertStmt->execute($spin);
        }
        
        echo "<div class='success'>✅ Added 5 sample spins</div>";
        
        // Show the sample data
        echo "<h3>📝 Sample Spins Added</h3>";
        $newSpins = $pdo->query("SELECT * FROM spins ORDER BY timestamp DESC LIMIT 10")->fetchAll();
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Recorded ID</th><th>Result</th><th>Timestamp</th><th>IP Address</th><th>Created At</th></tr>";
        foreach ($newSpins as $spin) {
            $formattedDate = date('Y-m-d H:i:s', $spin['timestamp'] / 1000);
            echo "<tr>";
            echo "<td>{$spin['id']}</td>";
            echo "<td>{$spin['recorded_id']}</td>";
            echo "<td>{$spin['result']}</td>";
            echo "<td>{$formattedDate}</td>";
            echo "<td>{$spin['ip_address']}</td>";
            echo "<td>{$spin['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Test 6: Test the API endpoint directly
    echo "<h3>🔗 API Endpoint Test</h3>";
    echo "<div class='info'>Testing: api/admin-data.php?action=spins</div>";
    
    // Simulate the API call
    $_GET['action'] = 'spins';
    
    ob_start();
    
    // Mock session for API test
    $_SESSION['admin_logged_in'] = true;
    
    try {
        require_once 'api/admin-data.php';
    } catch (Exception $e) {
        // This is expected since we're calling it directly
        echo "<div class='info'>API call completed (headers already sent warning is normal)</div>";
    }
    
    $apiOutput = ob_get_clean();
    
    // Try to extract JSON from output
    if (preg_match('/\{.*\}/', $apiOutput, $matches)) {
        $jsonData = $matches[0];
        $decodedData = json_decode($jsonData, true);
        
        if ($decodedData && isset($decodedData['success'])) {
            echo "<div class='success'>✅ API returned valid JSON</div>";
            echo "<div class='info'>Spins found: " . count($decodedData['spins'] ?? []) . "</div>";
            echo "<div class='info'>Total: " . ($decodedData['total'] ?? 'N/A') . "</div>";
        } else {
            echo "<div class='error'>❌ API returned invalid JSON</div>";
            echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";
        }
    } else {
        echo "<div class='error'>❌ No JSON found in API output</div>";
        echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
?>

<div style="margin-top: 30px; text-align: center;">
    <h3>🎯 Next Steps</h3>
    <p>Now test the admin panel Spin History tab to see if data appears.</p>
    <a href="admin.php" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        🔧 Go to Admin Panel
    </a>
</div>