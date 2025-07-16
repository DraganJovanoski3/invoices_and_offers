<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Item Tables</h1>";

try {
    require_once 'config/database.php';
    echo "<p>✅ Database connection successful</p>";
    
    // Check and fix invoice_items table
    echo "<h2>Checking invoice_items table...</h2>";
    
    $invoice_items_columns = [
        'service_id' => 'INT',
        'item_name' => 'VARCHAR(100) NOT NULL',
        'description' => 'TEXT',
        'quantity' => 'DECIMAL(10,2) NOT NULL DEFAULT 1',
        'unit_price' => 'DECIMAL(10,2) NOT NULL',
        'total_price' => 'DECIMAL(10,2) NOT NULL'
    ];
    
    foreach ($invoice_items_columns as $column => $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM invoice_items LIKE '$column'");
        if ($stmt->rowCount() == 0) {
            echo "<p>❌ '$column' column missing in invoice_items table</p>";
            $pdo->exec("ALTER TABLE invoice_items ADD COLUMN $column $definition");
            echo "<p>✅ Added '$column' column to invoice_items table</p>";
        } else {
            echo "<p>✅ '$column' column exists in invoice_items table</p>";
        }
    }
    
    // Check and fix offer_items table
    echo "<h2>Checking offer_items table...</h2>";
    
    $offer_items_columns = [
        'service_id' => 'INT',
        'item_name' => 'VARCHAR(100) NOT NULL',
        'description' => 'TEXT',
        'quantity' => 'DECIMAL(10,2) NOT NULL DEFAULT 1',
        'unit_price' => 'DECIMAL(10,2) NOT NULL',
        'total_price' => 'DECIMAL(10,2) NOT NULL'
    ];
    
    foreach ($offer_items_columns as $column => $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM offer_items LIKE '$column'");
        if ($stmt->rowCount() == 0) {
            echo "<p>❌ '$column' column missing in offer_items table</p>";
            $pdo->exec("ALTER TABLE offer_items ADD COLUMN $column $definition");
            echo "<p>✅ Added '$column' column to offer_items table</p>";
        } else {
            echo "<p>✅ '$column' column exists in offer_items table</p>";
        }
    }
    
    echo "<h2>✅ All item table columns have been added!</h2>";
    echo "<p><a href='index.php'>Go to login page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?> 