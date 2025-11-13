<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: invoices.php');
    exit();
}

// Get form data
$invoice_id = $_POST['invoice_id'] ?? null;
$new_status = $_POST['status'] ?? null;

// Validate input
if (!$invoice_id || !$new_status) {
    $_SESSION['error'] = 'Недостасуваат потребни податоци.';
    header("Location: view_invoice.php?id=" . $invoice_id);
    exit();
}

// Validate status value
$valid_statuses = ['draft', 'sent', 'paid', 'overdue'];
if (!in_array($new_status, $valid_statuses)) {
    $_SESSION['error'] = 'Неважечки статус.';
    header("Location: view_invoice.php?id=" . $invoice_id);
    exit();
}

try {
    // Update the invoice status
    $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $invoice_id]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = 'Статусот на фактурата е успешно ажуриран.';
    } else {
        $_SESSION['error'] = 'Фактурата не е пронајдена или не е ажурирана.';
    }
    
} catch (PDOException $e) {
    error_log("Error updating invoice status: " . $e->getMessage());
    $_SESSION['error'] = 'Грешка при ажурирање на статусот.';
}

// Redirect back to the invoice view
header("Location: view_invoice.php?id=" . $invoice_id);
exit();
?>
