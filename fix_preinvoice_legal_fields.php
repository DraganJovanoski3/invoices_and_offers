<?php
/**
 * Fix Pre-Invoice Legal Fields
 * This script adds missing columns to pre_invoices table
 */

require_once 'config/database.php';

try {
    // Read the migration SQL file
    $sql_file = __DIR__ . '/scripts/add_legal_fields_to_preinvoices.sql';
    
    if (!file_exists($sql_file)) {
        die("Migration file not found: $sql_file");
    }
    
    $sql = file_get_contents($sql_file);
    
    // Remove USE statement as we're already connected
    $sql = preg_replace('/USE\s+`[^`]+`;\s*/i', '', $sql);
    
    // Split by semicolons and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(SET|PREPARE|EXECUTE|DEALLOCATE)/i', $statement)) {
            // Skip SET, PREPARE, EXECUTE, DEALLOCATE as they're handled by the SQL file logic
            continue;
        }
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignore errors for "if not exists" checks
                if (strpos($e->getMessage(), 'Duplicate column') === false && 
                    strpos($e->getMessage(), 'already exists') === false) {
                    echo "Warning: " . $e->getMessage() . "<br>";
                }
            }
        }
    }
    
    // Manual column additions with checks
    $columns_to_add = [
        'supply_date' => "DATE DEFAULT NULL",
        'authorized_person' => "VARCHAR(100) DEFAULT NULL",
        'payment_method' => "VARCHAR(50) DEFAULT 'bank_transfer'",
        'fiscal_receipt_number' => "VARCHAR(100) DEFAULT NULL"
    ];
    
    foreach ($columns_to_add as $column => $definition) {
        try {
            // Check if column exists
            $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.columns 
                WHERE table_schema = DATABASE() 
                AND table_name = 'pre_invoices' 
                AND column_name = '$column'");
            $exists = $stmt->fetchColumn();
            $stmt->closeCursor(); // Close the cursor to allow next query
            
            if (!$exists) {
                $pdo->exec("ALTER TABLE pre_invoices ADD COLUMN $column $definition");
                echo "✓ Added column: $column<br>";
            } else {
                echo "✓ Column already exists: $column<br>";
            }
        } catch (PDOException $e) {
            echo "Error adding column $column: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br><strong>Migration completed!</strong><br>";
    echo "<a href='preinvoices.php'>Go to Pre-Invoices</a>";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

