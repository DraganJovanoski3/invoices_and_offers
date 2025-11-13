<?php
// Database Configuration for local XAMPP development
$host = 'localhost';
$dbname = 'invoices_and_offers';
$username = 'root'; // Default local MySQL user
$password = ''; // Common local development password (empty)

// Production configuration (uncomment for production)
// $host = 'localhost';
// $dbname = 'mrrbbkvz_invoices_app';
// $username = 'mrrbbkvz_admin_saniko';
// $password = 'qjNq8rG?z%H7';

// Check if PDO MySQL extension is available
if (!extension_loaded('pdo_mysql')) {
    die("ERROR: PDO MySQL extension is not loaded. Please enable it in php.ini");
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    
    // Show detailed error in development, generic error in production
    $error_message = "Database connection failed. Please check your configuration.";
    
    // In development, show more details
    if (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1')) {
        $error_message .= "\n\nError details: " . $e->getMessage();
        $error_message .= "\n\nConfiguration:";
        $error_message .= "\n- Host: " . $host;
        $error_message .= "\n- Database: " . $dbname;
        $error_message .= "\n- Username: " . $username;
        $error_message .= "\n\nPossible solutions:";
        $error_message .= "\n1. Ensure MySQL/MariaDB is running in XAMPP";
        $error_message .= "\n2. Verify database name is correct";
        $error_message .= "\n3. Check if database exists (run: CREATE DATABASE $dbname;)";
        $error_message .= "\n4. Verify username and password";
        $error_message .= "\n\nRun test_db_connection.php for detailed diagnostics.";
    }
    
    die($error_message);
}
?> 