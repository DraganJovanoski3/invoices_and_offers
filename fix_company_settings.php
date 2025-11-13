<?php
/**
 * Quick Fix Script for Company Settings Missing Columns
 * This script adds the missing columns to the company_settings table
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
    <title>Fix Company Settings</title>
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
        <h1>Fix Company Settings - Add Missing Columns</h1>
        
<?php

$errors = [];
$successes = [];

// Columns to add
$columns = [
    'currency' => "VARCHAR(3) DEFAULT 'MKD'",
    'vat_registration_number' => "VARCHAR(100) DEFAULT NULL",
    'authorized_person' => "VARCHAR(100) DEFAULT NULL",
    'legal_footer' => "TEXT DEFAULT NULL"
];

try {
    // Check if company_settings table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'company_settings'");
    if ($stmt->rowCount() == 0) {
        echo "<div class='error'>✗ Table 'company_settings' does not exist. Please run the database setup first.</div>";
        die("</div></div></body></html>");
    }
    
    echo "<div class='info'>✓ Table 'company_settings' exists</div>";
    
    // Get existing columns
    $stmt = $pdo->query("SHOW COLUMNS FROM company_settings");
    $existing_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    
    // Add missing columns
    foreach ($columns as $column_name => $column_definition) {
        if (in_array($column_name, $existing_columns)) {
            echo "<div class='info'>Column '$column_name' already exists - skipping</div>";
        } else {
            try {
                $pdo->exec("ALTER TABLE company_settings ADD COLUMN $column_name $column_definition");
                $successes[] = "✓ Added column '$column_name'";
                echo "<div class='success'>✓ Successfully added column '$column_name'</div>";
            } catch(PDOException $e) {
                $errors[] = "✗ Failed to add column '$column_name': " . $e->getMessage();
                echo "<div class='error'>✗ Failed to add column '$column_name': " . htmlspecialchars($e->getMessage()) . "</div>";
            }
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
    }
    
    if (count($errors) == 0 && count($successes) > 0) {
        echo "<div class='info'>";
        echo "<strong>All columns added successfully!</strong><br>";
        echo "You can now use the company settings page. <a href='company_settings.php'>Go to Company Settings</a>";
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
    }
    
} catch(PDOException $e) {
    echo "<div class='error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

?>
    </div>
</body>
</html>


