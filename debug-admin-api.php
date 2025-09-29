<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h2>🔍 Debug Admin API - Spins Endpoint</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
</style>";

// Set admin session for testing
$_SESSION['admin_logged_in'] = true;

echo "<div class='section'>";
echo "<h3>🔐 Session Check</h3>";
echo "<div class='success'>✅ Admin session set for testing</div>";
echo "</div>";

try {
    echo "<div class='section'>";
    echo "<h3>🔗 Database Connection Test</h3>";
    require_once 'api/config.php';
    echo "<div class='success'>✅ Config loaded successfully</div>";
    echo "<div class='info'>PDO object type: " . get_class($pdo) . "</div>";
    echo "</div>";

    echo "<div class='section'>";
    echo "<h3>📊 Table Structure Check</h3>";
    
    // Check if spins table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'spins'");
    if ($tableCheck->rowCount() > 0) {
        echo "<div class='success'>✅ Spins table exists</div>";
        
        // Check table structure
        $columns = $pdo->query("DESCRIBE spins")->fetchAll();
        echo "<div class='info'>Table columns:</div>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} ({$column['Type']})</li>";
        }
        echo "</ul>";
        
        // Check row count
        $count = $pdo->query("SELECT COUNT(*) FROM spins")->fetchColumn();
        echo "<div class='info'>Total rows: <strong>$count</strong></div>";
        
    } else {
        echo "<div class='error'>❌ Spins table does not exist!</div>";
        throw new Exception("Spins table not found");
    }
    echo "</div>";

    echo "<div class='section'>";
    echo "<h3>🧪 Test SQL Queries</h3>";
    
    // Test 1: Simple SELECT
    echo "<h4>Test 1: Simple SELECT</h4>";
    try {
        $simpleResult = $pdo->query("SELECT * FROM spins ORDER BY id DESC LIMIT 5");
        $rows = $simpleResult->fetchAll();
        echo "<div class='success'>✅ Simple query works: " . count($rows) . " rows</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ Simple query failed: " . $e->getMessage() . "</div>";
    }
    
    // Test 2: Count query
    echo "<h4>Test 2: Count Query</h4>";
    try {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM spins");
        $countStmt->execute();
        $totalSpins = $countStmt->fetchColumn();
        echo "<div class='success'>✅ Count query works: $totalSpins total spins</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ Count query failed: " . $e->getMessage() . "</div>";
    }
    
    // Test 3: Prepared statement with LIMIT
    echo "<h4>Test 3: Prepared Statement with LIMIT (Original Problem)</h4>";
    try {
        $page = 1;
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        echo "<div class='info'>Parameters: page=$page, limit=$limit, offset=$offset</div>";
        
        // This is the problematic query from the original code
        $stmt = $pdo->prepare("SELECT * FROM spins ORDER BY timestamp DESC LIMIT ? OFFSET ?");
        echo "<div class='info'>Prepared statement created</div>";
        
        $stmt->execute([$limit, $offset]);
        echo "<div class='info'>Statement executed</div>";
        
        $spins = $stmt->fetchAll();
        echo "<div class='success'>✅ Prepared statement with LIMIT works: " . count($spins) . " rows</div>";
        
        if (count($spins) > 0) {
            echo "<div class='info'>Sample result:</div>";
            echo "<pre>" . json_encode($spins[0], JSON_PRETTY_PRINT) . "</pre>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Prepared statement with LIMIT failed: " . $e->getMessage() . "</div>";
        echo "<div class='warning'>This is likely the cause of the 500 error!</div>";
        
        // Try alternative approach
        echo "<h4>Alternative: Using LIMIT without prepared statement</h4>";
        try {
            $query = "SELECT * FROM spins ORDER BY timestamp DESC LIMIT $limit OFFSET $offset";
            $altResult = $pdo->query($query);
            $altSpins = $altResult->fetchAll();
            echo "<div class='success'>✅ Alternative query works: " . count($altSpins) . " rows</div>";
        } catch (Exception $e2) {
            echo "<div class='error'>❌ Alternative also failed: " . $e2->getMessage() . "</div>";
        }
    }
    echo "</div>";

    echo "<div class='section'>";
    echo "<h3>🔧 Test Fixed getSpinsData Function</h3>";
    
    function testGetSpinsData() {
        global $pdo;
        
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 50;
            $offset = ($page - 1) * $limit;
            
            // Ensure parameters are integers
            $limit = (int)$limit;
            $offset = (int)$offset;
            
            // Alternative approach: Use setAttribute to handle LIMIT in prepared statements
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            $stmt = $pdo->prepare("SELECT * FROM spins ORDER BY timestamp DESC LIMIT :limit OFFSET :offset");
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
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
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }
    
    $result = testGetSpinsData();
    
    if ($result['success']) {
        echo "<div class='success'>✅ Fixed function works!</div>";
        echo "<div class='info'>Returned " . count($result['spins']) . " spins out of " . $result['total'] . " total</div>";
    } else {
        echo "<div class='error'>❌ Fixed function failed: " . $result['error'] . "</div>";
        echo "<pre>" . $result['trace'] . "</pre>";
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
    <h3>🎯 Results Summary</h3>
    <p>This debug page will help identify the exact cause of the 500 error.</p>
    <p>The most likely issue is with LIMIT/OFFSET parameters in prepared statements.</p>
</div>