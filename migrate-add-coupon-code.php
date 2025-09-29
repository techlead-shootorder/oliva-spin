<?php
require_once 'api/config.php';

echo "<h2>🔄 Database Migration: Add Coupon Code to Spins Table</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>";

try {
    echo "<div class='info'>🔍 Checking current table structure...</div>";
    
    // Check if coupon_code column already exists
    $columns = $pdo->query("DESCRIBE spins")->fetchAll();
    $hasCouponCode = false;
    
    echo "<div class='info'>Current columns in spins table:</div>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>{$column['Field']} ({$column['Type']})</li>";
        if ($column['Field'] === 'coupon_code') {
            $hasCouponCode = true;
        }
    }
    echo "</ul>";
    
    if ($hasCouponCode) {
        echo "<div class='warning'>⚠️ Column 'coupon_code' already exists. No migration needed.</div>";
    } else {
        echo "<div class='info'>➕ Adding 'coupon_code' column to spins table...</div>";
        
        // Add the coupon_code column
        $alterQuery = "ALTER TABLE spins ADD COLUMN coupon_code VARCHAR(50) NULL AFTER result";
        $pdo->exec($alterQuery);
        
        echo "<div class='success'>✅ Column added successfully!</div>";
        
        // Now we need to populate existing records with coupon codes
        echo "<div class='info'>🔄 Updating existing records with coupon codes...</div>";
        
        // Get all existing spins
        $existingSpins = $pdo->query("SELECT id, result FROM spins WHERE coupon_code IS NULL")->fetchAll();
        
        if (count($existingSpins) > 0) {
            echo "<div class='info'>Found " . count($existingSpins) . " records to update</div>";
            
            // Get coupon mappings
            $coupons = $pdo->query("SELECT discount_text, coupon_code FROM coupons")->fetchAll();
            $couponMap = [];
            foreach ($coupons as $coupon) {
                $couponMap[$coupon['discount_text']] = $coupon['coupon_code'];
            }
            
            echo "<div class='info'>Available coupon mappings:</div>";
            echo "<ul>";
            foreach ($couponMap as $text => $code) {
                echo "<li>$text → $code</li>";
            }
            echo "</ul>";
            
            // Update existing spins
            $updateStmt = $pdo->prepare("UPDATE spins SET coupon_code = ? WHERE id = ?");
            $updatedCount = 0;
            $notFoundCount = 0;
            
            foreach ($existingSpins as $spin) {
                if (isset($couponMap[$spin['result']])) {
                    $updateStmt->execute([$couponMap[$spin['result']], $spin['id']]);
                    $updatedCount++;
                    echo "<div class='success'>✅ Updated ID {$spin['id']}: {$spin['result']} → {$couponMap[$spin['result']]}</div>";
                } else {
                    $notFoundCount++;
                    echo "<div class='warning'>⚠️ No coupon code found for: {$spin['result']}</div>";
                    
                    // For records without matching coupons, set a default or leave null
                    // You could also manually map these if needed
                }
            }
            
            echo "<div class='success'>✅ Updated $updatedCount records successfully</div>";
            if ($notFoundCount > 0) {
                echo "<div class='warning'>⚠️ $notFoundCount records could not be matched</div>";
            }
        } else {
            echo "<div class='info'>No existing records to update</div>";
        }
    }
    
    // Show final table structure
    echo "<div class='info'>📋 Final table structure:</div>";
    $finalColumns = $pdo->query("DESCRIBE spins")->fetchAll();
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f2f2f2;'><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($finalColumns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show sample data
    echo "<div class='info'>📝 Sample data (showing coupon codes):</div>";
    $sampleData = $pdo->query("SELECT recorded_id, result, coupon_code, timestamp FROM spins ORDER BY timestamp DESC LIMIT 5")->fetchAll();
    
    if (count($sampleData) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f2f2f2;'><th>Recorded ID</th><th>Result</th><th>Coupon Code</th><th>Timestamp</th></tr>";
        foreach ($sampleData as $row) {
            $formattedDate = date('Y-m-d H:i:s', $row['timestamp'] / 1000);
            echo "<tr>";
            echo "<td>{$row['recorded_id']}</td>";
            echo "<td>{$row['result']}</td>";
            echo "<td><strong>{$row['coupon_code']}</strong></td>";
            echo "<td>{$formattedDate}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>No data to display</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Migration failed: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<div style="margin-top: 30px; text-align: center;">
    <h3>✅ Migration Complete!</h3>
    <p>The spins table now includes coupon codes. Next step: Update the spin.php and admin panel.</p>
    <a href="admin.php" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        🔧 Test Admin Panel
    </a>
</div>