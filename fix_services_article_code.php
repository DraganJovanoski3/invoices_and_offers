<?php
/**
 * Quick Fix Script for Services Table - Add article_code Column
 * This script adds the missing article_code column to the services table
 */

require_once 'config/database.php';

// Check if PDO MySQL extension is available
if (!extension_loaded('pdo_mysql')) {
    die("ERROR: PDO MySQL extension is not loaded. Please enable it in php.ini");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Services Table - Add article_code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #f5c6cb;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fix Services Table - Add article_code Column</h1>
        
<?php

$errors = [];
$successes = [];

try {
    // Check if services table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'services'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='error'>✗ Table 'services' does not exist. Please run the database setup first.</div>";
        die("</div></div></body></html>");
    }
    
    echo "<div class='info'>✓ Table 'services' exists</div>";
    
    // Get existing columns
    $stmt = $pdo->query("SHOW COLUMNS FROM services");
    $existing_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    
    // Check if article_code column exists
    if (in_array('article_code', $existing_columns)) {
        echo "<div class='info'>Column 'article_code' already exists - no changes needed</div>";
    } else {
        try {
            $pdo->exec("ALTER TABLE services ADD COLUMN article_code VARCHAR(100) DEFAULT NULL");
            $successes[] = "✓ Added column 'article_code' to services table";
            echo "<div class='success'>✓ Successfully added column 'article_code' to services table</div>";
        } catch(PDOException $e) {
            $errors[] = "✗ Failed to add column 'article_code': " . $e->getMessage();
            echo "<div class='error'>✗ Failed to add column 'article_code': " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    
    // Summary
    echo "<hr>";
    if (count($successes) > 0) {
        echo "<div class='success'>";
        echo "<strong>✓ Successfully added columns:</strong><br>";
        echo "<ul>";
        foreach ($successes as $success) {
            echo "<li>" . htmlspecialchars($success) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<strong>All columns added successfully!</strong><br>";
        echo "You can now view pre-invoices without errors. <a href='preinvoices.php'>Go to Pre-Invoices</a>";
        echo "</div>";
    } else if (count($errors) > 0) {
        echo "<div class='error'>";
        echo "<strong>Errors occurred:</strong><br>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='info'>";
        echo "<strong>No changes needed.</strong><br>";
        echo "The article_code column already exists in the services table.";
        echo "</div>";
    }
    
} catch(PDOException $e) {
    echo "<div class='error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

?>
    </div>
</body>
</html>


