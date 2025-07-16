<?php
// Simple Services Setup Script
// This script will safely add the services table and sample data

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Services Setup Script</h1>";

try {
    // Include database configuration
    require_once 'config/database.php';
    echo "<p>✅ Database connection successful</p>";
    
    // Step 1: Create services table
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Services table created/verified</p>";
    
    // Step 2: Check if services table has data
    $stmt = $pdo->query("SELECT COUNT(*) FROM services");
    $service_count = $stmt->fetchColumn();
    
    if ($service_count == 0) {
        // Add sample services
        $services = [
            ['Web Development', 75.00, 'Custom website development'],
            ['Logo Design', 150.00, 'Professional logo design'],
            ['SEO Optimization', 50.00, 'Search engine optimization'],
            ['Content Writing', 25.00, 'Professional content writing'],
            ['Maintenance', 30.00, 'Website maintenance and updates']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO services (name, price, description) VALUES (?, ?, ?)");
        foreach ($services as $service) {
            $stmt->execute($service);
        }
        echo "<p>✅ Sample services added successfully</p>";
    } else {
        echo "<p>ℹ️ Services table already has {$service_count} services</p>";
    }
    
    // Step 3: Update invoice_items table (safely)
    $columns_to_add = [
        'service_id' => 'INT',
        'item_name' => 'VARCHAR(100) NOT NULL DEFAULT ""',
        'description' => 'TEXT',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
    ];
    
    foreach ($columns_to_add as $column => $definition) {
        try {
            $pdo->exec("ALTER TABLE invoice_items ADD COLUMN {$column} {$definition}");
            echo "<p>✅ Added {$column} to invoice_items</p>";
        } catch (Exception $e) {
            echo "<p>ℹ️ Column {$column} already exists in invoice_items</p>";
        }
    }
    
    // Step 4: Update offer_items table (safely)
    foreach ($columns_to_add as $column => $definition) {
        try {
            $pdo->exec("ALTER TABLE offer_items ADD COLUMN {$column} {$definition}");
            echo "<p>✅ Added {$column} to offer_items</p>";
        } catch (Exception $e) {
            echo "<p>ℹ️ Column {$column} already exists in offer_items</p>";
        }
    }
    
    echo "<h2>🎉 Setup Complete!</h2>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li><a href='index.php'>Go to Login Page</a></li>";
    echo "<li>Login with: admin / password</li>";
    echo "<li>Click 'Services' in the navigation</li>";
    echo "<li>Start creating invoices with services!</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
    echo "<p><strong>Debug Info:</strong></p>";
    echo "<p>Error Code: " . $e->getCode() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}
?> 