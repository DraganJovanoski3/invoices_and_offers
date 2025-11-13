<?php
/**
 * Database Connection Diagnostic Tool
 * This script helps identify database connection issues
 */

echo "<h2>Database Connection Diagnostic Tool</h2>";
echo "<hr>";

// Test 1: Check if MySQL extension is available
echo "<h3>1. Checking PHP MySQL Extension</h3>";
if (extension_loaded('pdo_mysql')) {
    echo "✓ PDO MySQL extension is loaded<br>";
} else {
    echo "✗ PDO MySQL extension is NOT loaded<br>";
    echo "<strong>Action required:</strong> Enable PDO MySQL extension in php.ini<br>";
}

if (extension_loaded('mysqli')) {
    echo "✓ MySQLi extension is loaded<br>";
} else {
    echo "⚠ MySQLi extension is not loaded (optional)<br>";
}
echo "<hr>";

// Test 2: Read current database configuration
echo "<h3>2. Current Database Configuration</h3>";
if (file_exists('config/database.php')) {
    require_once 'config/database.php';
    echo "✓ Configuration file found: config/database.php<br>";
    echo "Host: " . htmlspecialchars($host ?? 'NOT SET') . "<br>";
    echo "Database: " . htmlspecialchars($dbname ?? 'NOT SET') . "<br>";
    echo "Username: " . htmlspecialchars($username ?? 'NOT SET') . "<br>";
    echo "Password: " . (isset($password) && $password !== '' ? '[SET]' : '[EMPTY]') . "<br>";
} else {
    echo "✗ Configuration file NOT found: config/database.php<br>";
    echo "<strong>Action required:</strong> Create config/database.php file<br>";
    die();
}
echo "<hr>";

// Test 3: Try to connect to MySQL server (without database)
echo "<h3>3. Testing MySQL Server Connection</h3>";
try {
    $test_pdo = new PDO("mysql:host=$host", $username, $password);
    $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Successfully connected to MySQL server<br>";
    
    // Get MySQL version
    $version = $test_pdo->query('SELECT VERSION()')->fetchColumn();
    echo "MySQL Version: " . htmlspecialchars($version) . "<br>";
    
} catch(PDOException $e) {
    echo "✗ Failed to connect to MySQL server<br>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Possible causes:</strong><br>";
    echo "- MySQL server is not running<br>";
    echo "- Wrong host name<br>";
    echo "- Wrong username or password<br>";
    echo "- MySQL port is not accessible (check if it's 3306)<br>";
    echo "<hr>";
    die();
}
echo "<hr>";

// Test 4: Check if database exists
echo "<h3>4. Checking if Database Exists</h3>";
try {
    $stmt = $test_pdo->query("SHOW DATABASES LIKE '$dbname'");
    $db_exists = $stmt->rowCount() > 0;
    
    if ($db_exists) {
        echo "✓ Database '$dbname' exists<br>";
    } else {
        echo "✗ Database '$dbname' does NOT exist<br>";
        echo "<strong>Action required:</strong> Create the database or update database name in config/database.php<br>";
        echo "<br>Available databases:<br>";
        $stmt = $test_pdo->query("SHOW DATABASES");
        while ($row = $stmt->fetch()) {
            $db = $row['Database'];
            if (!in_array($db, ['information_schema', 'performance_schema', 'mysql', 'sys', 'phpmyadmin'])) {
                echo "- " . htmlspecialchars($db) . "<br>";
            }
        }
    }
} catch(PDOException $e) {
    echo "✗ Error checking database: " . htmlspecialchars($e->getMessage()) . "<br>";
}
echo "<hr>";

// Test 5: Try to connect to the specific database
echo "<h3>5. Testing Database Connection</h3>";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "✓ Successfully connected to database '$dbname'<br>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '$dbname'");
    $result = $stmt->fetch();
    echo "Number of tables in database: " . htmlspecialchars($result['table_count']) . "<br>";
    
    if ($result['table_count'] == 0) {
        echo "⚠ Database exists but has no tables<br>";
        echo "<strong>Action required:</strong> Import database.sql to create tables<br>";
    }
    
} catch(PDOException $e) {
    echo "✗ Failed to connect to database '$dbname'<br>";
    echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Possible causes:</strong><br>";
    echo "- Database name is incorrect<br>";
    echo "- Database does not exist<br>";
    echo "- User doesn't have permission to access this database<br>";
}
echo "<hr>";

// Test 6: Check required tables
if (isset($pdo)) {
    echo "<h3>6. Checking Required Tables</h3>";
    $required_tables = ['users', 'clients', 'services', 'invoices', 'invoice_items', 'offers', 'offer_items', 'company_settings'];
    
    foreach ($required_tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✓ Table '$table' exists<br>";
            } else {
                echo "✗ Table '$table' is missing<br>";
            }
        } catch(PDOException $e) {
            echo "✗ Error checking table '$table': " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    }
    echo "<hr>";
}

// Summary
echo "<h3>Summary</h3>";
if (isset($pdo)) {
    echo "<div style='background-color: #d4edda; padding: 10px; border-radius: 5px;'>";
    echo "✓ Database connection is working correctly!<br>";
    echo "</div>";
} else {
    echo "<div style='background-color: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "✗ Database connection failed. Please fix the issues above.<br>";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> Delete this file (test_db_connection.php) after diagnosis for security.</p>";
?>


