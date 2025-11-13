<?php
/**
 * Database Setup Script
 * This script creates the database and imports all required tables
 * Run this once to set up your database
 */

// Database Configuration
$host = 'localhost';
$dbname = 'invoices_and_offers';
$username = 'root';
$password = '';

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
    <title>Database Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
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
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #ffeaa7;
        }
        .step {
            margin: 15px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        .step-title {
            font-weight: bold;
            color: #007bff;
        }
        code {
            background-color: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Setup Script</h1>
        
<?php
$errors = [];
$warnings = [];
$successes = [];

// Step 1: Connect to MySQL server (without database)
echo "<div class='step'>";
echo "<div class='step-title'>Step 1: Connecting to MySQL Server</div>";
try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $successes[] = "✓ Successfully connected to MySQL server";
    echo "<div class='success'>✓ Successfully connected to MySQL server</div>";
} catch(PDOException $e) {
    $errors[] = "✗ Failed to connect to MySQL server: " . $e->getMessage();
    echo "<div class='error'>✗ Failed to connect to MySQL server: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p><strong>Possible solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Ensure MySQL/MariaDB is running in XAMPP Control Panel</li>";
    echo "<li>Check if username and password are correct</li>";
    echo "<li>Verify MySQL service is accessible</li>";
    echo "</ul>";
    die("</div></div></body></html>");
}
echo "</div>";

// Step 2: Check if database exists
echo "<div class='step'>";
echo "<div class='step-title'>Step 2: Checking Database</div>";
try {
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    $db_exists = $stmt->rowCount() > 0;
    
    if ($db_exists) {
        $warnings[] = "Database '$dbname' already exists";
        echo "<div class='warning'>⚠ Database '$dbname' already exists</div>";
        echo "<p>If you want to recreate it, please drop it first in phpMyAdmin or run: <code>DROP DATABASE $dbname;</code></p>";
    } else {
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $successes[] = "✓ Database '$dbname' created successfully";
        echo "<div class='success'>✓ Database '$dbname' created successfully</div>";
    }
} catch(PDOException $e) {
    $errors[] = "Error checking/creating database: " . $e->getMessage();
    echo "<div class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    die("</div></div></body></html>");
}
echo "</div>";

// Step 3: Connect to the specific database
echo "<div class='step'>";
echo "<div class='step-title'>Step 3: Connecting to Database</div>";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $successes[] = "✓ Connected to database '$dbname'";
    echo "<div class='success'>✓ Connected to database '$dbname'</div>";
} catch(PDOException $e) {
    $errors[] = "Failed to connect to database: " . $e->getMessage();
    echo "<div class='error'>✗ Failed to connect to database: " . htmlspecialchars($e->getMessage()) . "</div>";
    die("</div></div></body></html>");
}
echo "</div>";

// Step 4: Import base database structure
echo "<div class='step'>";
echo "<div class='step-title'>Step 4: Importing Base Database Structure</div>";
$sql_file = 'scripts/database.sql';
if (file_exists($sql_file)) {
    $sql = file_get_contents($sql_file);
    
    // Remove comments and split by semicolons
    $sql = preg_replace('/--.*$/m', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $executed = 0;
    $failed = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            $executed++;
        } catch(PDOException $e) {
            // Ignore "table already exists" errors if database existed
            if (strpos($e->getMessage(), 'already exists') === false && strpos($e->getMessage(), 'Duplicate entry') === false) {
                $failed++;
                echo "<div class='error'>✗ Error executing statement: " . htmlspecialchars(substr($e->getMessage(), 0, 100)) . "...</div>";
            }
        }
    }
    
    if ($executed > 0) {
        $successes[] = "✓ Executed $executed SQL statements from database.sql";
        echo "<div class='success'>✓ Executed $executed SQL statements from database.sql</div>";
    }
    if ($failed > 0) {
        $warnings[] = "⚠ $failed statements failed (may be expected if tables already exist)";
    }
} else {
    $errors[] = "SQL file not found: $sql_file";
    echo "<div class='error'>✗ SQL file not found: $sql_file</div>";
}
echo "</div>";

// Step 5: Run migration scripts (in correct order)
echo "<div class='step'>";
echo "<div class='step-title'>Step 5: Running Migration Scripts</div>";
$migration_files = [
    'scripts/add_preinvoices_tables.sql',      // Creates new tables first
    'scripts/add_offer_id_to_invoices.sql',    // Adds column to existing table
    'scripts/add_advance_payment_to_offers.sql', // Adds columns to existing table
    'scripts/add_vat_obvrznik_to_invoices.sql', // Adds column to existing table
    'scripts/add_vat_obvrznik_to_offers.sql',   // Adds column to existing table
    'scripts/add_discount_fields.sql',         // Adds columns to multiple tables
    'scripts/add_missing_columns_to_preinvoices.sql', // Adds columns to pre_invoices
];

$migrations_run = 0;
$migrations_failed = 0;

foreach ($migration_files as $migration_file) {
    if (file_exists($migration_file)) {
        $sql = file_get_contents($migration_file);
        $sql = preg_replace('/--.*$/m', '', $sql);
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            try {
                $pdo->exec($statement);
                $migrations_run++;
            } catch(PDOException $e) {
                // Ignore "table already exists" and "duplicate column" errors
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate column') === false &&
                    strpos($e->getMessage(), 'Duplicate entry') === false) {
                    $migrations_failed++;
                    echo "<div class='warning'>⚠ Migration $migration_file: " . htmlspecialchars(substr($e->getMessage(), 0, 100)) . "</div>";
                }
            }
        }
    }
}

if ($migrations_run > 0) {
    $successes[] = "✓ Ran $migrations_run migration statements";
    echo "<div class='success'>✓ Ran $migrations_run migration statements</div>";
}
if ($migrations_failed > 0) {
    echo "<div class='warning'>⚠ $migrations_failed migration statements had warnings (may be expected if already applied)</div>";
}
echo "</div>";

// Step 6: Verify tables
echo "<div class='step'>";
echo "<div class='step-title'>Step 6: Verifying Tables</div>";
$required_tables = ['users', 'clients', 'services', 'invoices', 'invoice_items', 'offers', 'offer_items', 'company_settings', 'pre_invoices', 'pre_invoice_items'];
$existing_tables = [];

try {
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $existing_tables[] = $row[0];
    }
    
    $missing_tables = array_diff($required_tables, $existing_tables);
    $found_tables = array_intersect($required_tables, $existing_tables);
    
    if (count($found_tables) > 0) {
        echo "<div class='success'>✓ Found " . count($found_tables) . " required tables: " . implode(', ', $found_tables) . "</div>";
    }
    
    if (count($missing_tables) > 0) {
        echo "<div class='error'>✗ Missing tables: " . implode(', ', $missing_tables) . "</div>";
    } else {
        $successes[] = "✓ All required tables exist";
        echo "<div class='success'>✓ All required tables exist</div>";
    }
} catch(PDOException $e) {
    echo "<div class='error'>✗ Error checking tables: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo "</div>";

// Final Summary
echo "<div class='step'>";
echo "<div class='step-title'>Setup Summary</div>";

if (count($errors) == 0 && count($successes) > 0) {
    echo "<div class='success'>";
    echo "<strong>✓ Database setup completed successfully!</strong><br>";
    echo "<ul>";
    foreach ($successes as $success) {
        echo "<li>" . htmlspecialchars($success) . "</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<strong>Next steps:</strong><br>";
    echo "<ul>";
    echo "<li>Test the application at <a href='index.php'>index.php</a></li>";
    echo "<li>Default login credentials: <strong>username:</strong> admin, <strong>password:</strong> password</li>";
    echo "<li><strong>Important:</strong> Change the default password after first login</li>";
    echo "<li>Delete this setup file (<code>setup_database.php</code>) for security</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<strong>Setup completed with errors:</strong><br>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if (count($warnings) > 0) {
    echo "<div class='warning'>";
    echo "<strong>Warnings:</strong><br>";
    echo "<ul>";
    foreach ($warnings as $warning) {
        echo "<li>" . htmlspecialchars($warning) . "</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "</div>";

echo "<div class='info'>";
echo "<strong>Note:</strong> This script is safe to run multiple times. It uses CREATE TABLE IF NOT EXISTS, so it won't overwrite existing data.";
echo "</div>";

?>
    </div>
</body>
</html>

