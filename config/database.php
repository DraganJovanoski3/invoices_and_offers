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

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}
?> 