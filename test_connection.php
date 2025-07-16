<?php
// Simple Test Script
echo "<h1>Connection Test</h1>";

// Test 1: Basic PHP
echo "<p>✅ PHP is working</p>";

// Test 2: Check if config file exists
if (file_exists('config/database.php')) {
    echo "<p>✅ Database config file exists</p>";
} else {
    echo "<p>❌ Database config file missing</p>";
    exit;
}

// Test 3: Try to include config
try {
    require_once 'config/database.php';
    echo "<p>✅ Database config loaded</p>";
} catch (Exception $e) {
    echo "<p>❌ Error loading config: " . $e->getMessage() . "</p>";
    exit;
}

// Test 4: Test database connection
try {
    $test_query = $pdo->query("SELECT 1");
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test 5: Check if services table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'services'");
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "<p>✅ Services table exists</p>";
        
        // Check if it has data
        $stmt = $pdo->query("SELECT COUNT(*) FROM services");
        $count = $stmt->fetchColumn();
        echo "<p>ℹ️ Services table has {$count} records</p>";
    } else {
        echo "<p>ℹ️ Services table does not exist yet</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking services table: " . $e->getMessage() . "</p>";
}

echo "<h2>🎉 All tests passed!</h2>";
echo "<p><a href='index.php'>Go to Login Page</a></p>";
?> 