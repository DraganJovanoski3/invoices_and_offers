<?php
/**
 * Fix Pre-Invoice Legal Fields - Simple Version
 * This script adds missing columns to pre_invoices table
 */

require_once 'config/database.php';

try {
    // Columns to add
    $columns_to_add = [
        'supply_date' => "DATE DEFAULT NULL",
        'authorized_person' => "VARCHAR(100) DEFAULT NULL",
        'payment_method' => "VARCHAR(50) DEFAULT 'bank_transfer'",
        'fiscal_receipt_number' => "VARCHAR(100) DEFAULT NULL"
    ];
    
    echo "<h2>Adding Missing Columns to pre_invoices Table</h2>";
    echo "<hr>";
    
    foreach ($columns_to_add as $column => $definition) {
        try {
            // Check if column exists using a simpler query
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns 
                WHERE table_schema = DATABASE() 
                AND table_name = 'pre_invoices' 
                AND column_name = ?");
            $stmt->execute([$column]);
            $exists = $stmt->fetchColumn();
            $stmt = null; // Clear the statement
            
            if (!$exists) {
                $pdo->exec("ALTER TABLE pre_invoices ADD COLUMN $column $definition");
                echo "✓ <strong>Added column:</strong> $column<br>";
            } else {
                echo "✓ <strong>Column already exists:</strong> $column<br>";
            }
        } catch (PDOException $e) {
            // Check if it's a duplicate column error
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "✓ <strong>Column already exists:</strong> $column<br>";
            } else {
                echo "✗ <strong>Error adding column $column:</strong> " . $e->getMessage() . "<br>";
            }
        }
    }
    
    echo "<hr>";
    echo "<br><strong style='color: green;'>Migration completed!</strong><br><br>";
    echo "<a href='preinvoices.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Pre-Invoices</a>";
    
} catch (Exception $e) {
    die("<strong style='color: red;'>Error:</strong> " . $e->getMessage());
}
?>


