<?php
// Production Database Configuration
// Update these values with your hosting provider's database details

$host = 'localhost';  // Usually 'localhost' for cPanel hosting
$dbname = 'ddsolutionscomm_invoices_app';  // Your database name from hosting provider
$username = 'admin';  // Your database username
$password = '123draganfakturi';  // Your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}
?> 