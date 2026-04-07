<?php
/**
 * Migration script to add 'paytabs' to payments provider ENUM
 * Run this file directly: php add-paytabs-provider.php
 */

require_once __DIR__ . '/src/config/db.php';
require_once __DIR__ . '/src/config/env.php';

try {
    Env::load();
    $db = getDB();
    
    // Check current provider enum values
    $stmt = $db->query("SHOW COLUMNS FROM payments WHERE Field = 'provider'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        $type = $column['Type'];
        // Check if paytabs is already in the enum
        if (strpos($type, 'paytabs') !== false) {
            echo "✓ 'paytabs' is already in the provider enum. No changes needed.\n";
            exit(0);
        }
    }
    
    // Modify the column to add 'paytabs'
    $db->exec("ALTER TABLE payments MODIFY COLUMN provider ENUM('razorpay', 'stripe', 'paytabs') NOT NULL");
    echo "✓ Added 'paytabs' to payments provider enum\n";
    
    echo "\nMigration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Note: If the error is about data type incompatibility, ensure all existing payment records have valid provider values.\n";
    exit(1);
}
