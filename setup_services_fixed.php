<?php
// Setup Services Table for Local Development (MariaDB Compatible)
// Run this in your browser to add the services table

require_once 'config/database.php';

try {
    echo "<h2>Setting up Services Table...</h2>";
    
    // Create services table
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ Services table created successfully!</p>";
    
    // Add columns to invoice_items table (check if they exist first)
    try {
        $pdo->exec("ALTER TABLE invoice_items ADD COLUMN service_id INT");
        echo "<p>✅ Added service_id to invoice_items</p>";
    } catch (Exception $e) {
        echo "<p>ℹ️ service_id column already exists in invoice_items</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE invoice_items ADD COLUMN item_name VARCHAR(100) NOT NULL DEFAULT ''");
        echo "<p>✅ Added item_name to invoice_items</p>";
    } catch (Exception $e) {
        echo "<p>ℹ️ item_name column already exists in invoice_items</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE invoice_items ADD COLUMN description TEXT");
        echo "<p>✅ Added description to invoice_items</p>";
    } catch (Exception $e) {
        echo "<p>ℹ️ description column already exists in invoice_items</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE invoice_items ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "<p>✅ Added created_at to invoice_items</p>";
    } catch (Exception $e) {
        echo "<p>ℹ️ created_at column already exists in invoice_items</p>";
    }
    
    // Add columns to offer_items table
    try {
        $pdo->exec("ALTER TABLE offer_items ADD COLUMN service_id INT");
        echo "<p>✅ Added service_id to offer_items</p>";
    } catch (Exception $e) {
        echo "<p>ℹ️ service_id column already exists in offer_items</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE offer_items ADD COLUMN item_name VARCHAR(100) NOT NULL DEFAULT ''");
        echo "<p>✅ Added item_name to offer_items</p>";
    } catch (Exception $e) {
        echo "<p>ℹ️ item_name column already exists in offer_items</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE offer_items ADD COLUMN description TEXT");
        echo "<p>✅ Added description to offer_items</p>";
    } catch (Exception $e) {
        echo "<p>ℹ️ description column already exists in offer_items</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE offer_items ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "<p>✅ Added created_at to offer_items</p>";
    } catch (Exception $e) {
        echo "<p>ℹ️ created_at column already exists in offer_items</p>";
    }
    
    // Try to add foreign key constraints (ignore if they already exist)
    try {
        $pdo->exec("ALTER TABLE invoice_items ADD CONSTRAINT fk_invoice_items_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL");
        echo "<p>✅ Added foreign key constraint for invoice_items</p>";
    } catch (Exception $e) {
        echo "<p>ℹ️ Foreign key constraint already exists for invoice_items</p>";
    }
    
    try {
        $pdo->exec("ALTER TABLE offer_items ADD CONSTRAINT fk_offer_items_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL");
        echo "<p>✅ Added foreign key constraint for offer_items</p>";
    } catch (Exception $e) {
        echo "<p>ℹ️ Foreign key constraint already exists for offer_items</p>";
    }
    
    // Check if services already exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM services");
    $service_count = $stmt->fetchColumn();
    
    if ($service_count == 0) {
        // Insert sample services
        $sample_services = [
            ['Web Development', 75.00, 'Custom website development'],
            ['Logo Design', 150.00, 'Professional logo design'],
            ['SEO Optimization', 50.00, 'Search engine optimization'],
            ['Content Writing', 25.00, 'Professional content writing'],
            ['Maintenance', 30.00, 'Website maintenance and updates']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO services (name, price, description) VALUES (?, ?, ?)");
        foreach ($sample_services as $service) {
            $stmt->execute($service);
        }
        echo "<p>✅ Sample services added successfully!</p>";
    } else {
        echo "<p>ℹ️ Services already exist in database</p>";
    }
    
    echo "<h3>🎉 Services system setup complete!</h3>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Go to <a href='index.php'>Login Page</a></li>";
    echo "<li>Login with admin/password</li>";
    echo "<li>Click 'Services' in the navigation</li>";
    echo "<li>Start creating invoices with services!</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
    echo "<p>Please check your database connection in config/database.php</p>";
}
?> 