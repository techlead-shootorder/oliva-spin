<?php
/**
 * Migration Script: Update discount_text column to TEXT for HTML support
 *
 * This script modifies the coupons table to change discount_text
 * from VARCHAR(255) to TEXT to support rich HTML content.
 */

require_once 'api/config.php';

try {
    echo "Starting migration: Converting discount_text to TEXT column...\n";

    // Check if column exists and get its current type
    $checkQuery = "SHOW COLUMNS FROM coupons LIKE 'discount_text'";
    $stmt = $pdo->query($checkQuery);
    $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($columnInfo) {
        echo "Current column type: " . $columnInfo['Type'] . "\n";

        // Modify the column to TEXT type
        $alterQuery = "ALTER TABLE coupons MODIFY COLUMN discount_text TEXT NOT NULL";

        $pdo->exec($alterQuery);
        echo "✓ Successfully updated discount_text column to TEXT type.\n";
        echo "✓ The column can now store HTML content for rich text formatting.\n";

    } else {
        throw new Exception("discount_text column not found in coupons table");
    }

    // Verify the change
    $verifyQuery = "SHOW COLUMNS FROM coupons LIKE 'discount_text'";
    $verifyStmt = $pdo->query($verifyQuery);
    $newColumnInfo = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    if ($newColumnInfo) {
        echo "\nNew column type: " . $newColumnInfo['Type'] . "\n";
    }

    echo "\n✓ Migration completed successfully!\n";
    echo "You can now use rich text formatting in discount text fields.\n";

} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Close connection
$pdo = null;
?>
