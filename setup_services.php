<?php
// Setup Services Table for Local Development
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
    
    // Add columns to invoice_items table
    $pdo->exec("ALTER TABLE invoice_items ADD COLUMN IF NOT EXISTS service_id INT");
    $pdo->exec("ALTER TABLE invoice_items ADD COLUMN IF NOT EXISTS item_name VARCHAR(100) NOT NULL DEFAULT ''");
    $pdo->exec("ALTER TABLE invoice_items ADD COLUMN IF NOT EXISTS description TEXT");
    $pdo->exec("ALTER TABLE invoice_items ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "<p>✅ Invoice items table updated!</p>";
    
    // Add columns to offer_items table
    $pdo->exec("ALTER TABLE offer_items ADD COLUMN IF NOT EXISTS service_id INT");
    $pdo->exec("ALTER TABLE offer_items ADD COLUMN IF NOT EXISTS item_name VARCHAR(100) NOT NULL DEFAULT ''");
    $pdo->exec("ALTER TABLE offer_items ADD COLUMN IF NOT EXISTS description TEXT");
    $pdo->exec("ALTER TABLE offer_items ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "<p>✅ Offer items table updated!</p>";
    
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