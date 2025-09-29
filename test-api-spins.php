<?php
session_start();

// Mock admin session for testing
$_SESSION['admin_logged_in'] = true;

echo "<h2>🔗 Direct API Test for Spins</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>";

try {
    // Include the config to test database connection
    require_once 'api/config.php';
    echo "<div class='success'>✅ Database connected successfully</div>";
    
    // Test direct database query
    echo "<h3>📊 Direct Database Query</h3>";
    $spinsCount = $pdo->query("SELECT COUNT(*) FROM spins")->fetchColumn();
    echo "<div class='info'>Total spins in database: <strong>$spinsCount</strong></div>";
    
    if ($spinsCount == 0) {
        echo "<div class='info'>⚠️ No spins in database. Adding sample data...</div>";
        
        // Add sample spins
        $sampleSpins = [
            ['SAMPLE001', '10K Discount', time() * 1000, '127.0.0.1'],
            ['SAMPLE002', '15K Discount', (time() - 3600) * 1000, '192.168.1.1'],
            ['SAMPLE003', 'Free IVF', (time() - 7200) * 1000, '10.0.0.1']
        ];
        
        $insertStmt = $pdo->prepare("INSERT INTO spins (recorded_id, result, timestamp, ip_address) VALUES (?, ?, ?, ?)");
        foreach ($sampleSpins as $spin) {
            $insertStmt->execute($spin);
        }
        
        echo "<div class='success'>✅ Added 3 sample spins</div>";
        $spinsCount = 3;
    }
    
    // Test the getSpinsData function directly
    echo "<h3>🎯 Testing getSpinsData() Function</h3>";
    
    // Simulate the function call
    function testGetSpinsData() {
        global $pdo;
        
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 50;
        $offset = ($page - 1) * $limit;
        
        $stmt = $pdo->prepare("SELECT * FROM spins ORDER BY timestamp DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        $spins = $stmt->fetchAll();
        
        // Get total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM spins");
        $countStmt->execute();
        $totalSpins = $countStmt->fetchColumn();
        
        return [
            'success' => true,
            'spins' => $spins,
            'total' => $totalSpins,
            'page' => $page,
            'limit' => $limit
        ];
    }
    
    $result = testGetSpinsData();
    echo "<div class='success'>✅ Function executed successfully</div>";
    echo "<div class='info'>Returned spins: " . count($result['spins']) . "</div>";
    echo "<div class='info'>Total spins: " . $result['total'] . "</div>";
    
    echo "<h4>📝 Sample Data:</h4>";
    echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
    
    // Test the actual API endpoint via cURL
    echo "<h3>🌐 Testing API Endpoint via HTTP</h3>";
    
    $url = 'http://localhost' . dirname($_SERVER['PHP_SELF']) . '/api/admin-data.php?action=spins';
    echo "<div class='info'>Testing URL: $url</div>";
    
    // Create a temporary session file for the cURL request
    $tempSessionFile = tempnam(sys_get_temp_dir(), 'sess_');
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $tempSessionFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $tempSessionFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Cookie: PHPSESSID=' . session_id()
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Clean up temp file
    unlink($tempSessionFile);
    
    if ($error) {
        echo "<div class='error'>❌ cURL Error: $error</div>";
    } else {
        echo "<div class='info'>HTTP Status: $httpCode</div>";
        
        if ($httpCode === 200) {
            echo "<div class='success'>✅ API endpoint responded successfully</div>";
            
            $apiData = json_decode($response, true);
            if ($apiData) {
                echo "<div class='success'>✅ Valid JSON response</div>";
                echo "<div class='info'>API returned " . count($apiData['spins'] ?? []) . " spins</div>";
                
                if (isset($apiData['spins']) && count($apiData['spins']) > 0) {
                    echo "<h4>📋 First Spin Example:</h4>";
                    echo "<pre>" . json_encode($apiData['spins'][0], JSON_PRETTY_PRINT) . "</pre>";
                }
            } else {
                echo "<div class='error'>❌ Invalid JSON response</div>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
        } else {
            echo "<div class='error'>❌ HTTP Error: $httpCode</div>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }
    }

} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<div class='error'>Stack trace:</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<div style="margin-top: 30px; text-align: center;">
    <h3>🎯 Next Steps</h3>
    <p>1. If this test shows spins data, the issue is in the frontend JavaScript</p>
    <p>2. If no spins data, the issue is in the database or API</p>
    <p>3. Check browser console for JavaScript errors when clicking Spin History tab</p>
    <a href="admin.php" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        🔧 Test Admin Panel
    </a>
</div>