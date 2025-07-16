<?php
// Database Configuration for ddsolutionscomm_invoices_app
// $host = 'localhost';
// $dbname = 'ddsolutionscomm_invoices_app';
// $username = 'ddsolutionscomm_invoices-admin'; // Default local MySQL user
// $password = '123dragan1230405'; // Common local development password (empty)

$host = 'localhost';
$dbname = 'invoices_app';
$username = 'root'; // Default local MySQL user
$password = ''; // Common local development password (empty)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}
?> 