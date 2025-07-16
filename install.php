<?php
// Invoicing System - Installation Script
// Run this once to set up your application

session_start();

// Check if already installed
if (file_exists('config/database.php') && !isset($_GET['force'])) {
    die('Application appears to be already installed. Add ?force=1 to URL to reinstall.');
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1:
            // Database configuration
            $host = $_POST['host'];
            $dbname = $_POST['dbname'];
            $username = $_POST['username'];
            $password = $_POST['password'];
            
            // Test database connection
            try {
                $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Create database configuration file
                $config_content = "<?php
\$host = '$host';
\$dbname = '$dbname';
\$username = '$username';
\$password = '$password';

try {
    \$pdo = new PDO(\"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\", \$username, \$password);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException \$e) {
    error_log(\"Database connection failed: \" . \$e->getMessage());
    die(\"Database connection failed. Please check your configuration.\");
}
?>";
                
                if (file_put_contents('config/database.php', $config_content)) {
                    $success = 'Database configuration saved successfully!';
                    $step = 2;
                } else {
                    $error = 'Could not write database configuration file. Check permissions.';
                }
            } catch (PDOException $e) {
                $error = 'Database connection failed: ' . $e->getMessage();
            }
            break;
            
        case 2:
            // Import database structure
            try {
                require_once 'config/database.php';
                
                $sql = file_get_contents('database.sql');
                $pdo->exec($sql);
                
                // Create admin user
                $admin_password = password_hash('password', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
                $stmt->execute(['admin', $admin_password, 'admin@yourdomain.com']);
                
                $success = 'Database structure imported and admin user created successfully!';
                $step = 3;
            } catch (Exception $e) {
                $error = 'Database import failed: ' . $e->getMessage();
            }
            break;
            
        case 3:
            // Create uploads directory
            if (!is_dir('uploads')) {
                if (mkdir('uploads', 0755)) {
                    $success = 'Uploads directory created successfully!';
                } else {
                    $error = 'Could not create uploads directory. Check permissions.';
                }
            } else {
                $success = 'Uploads directory already exists!';
            }
            $step = 4;
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoicing System - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Invoicing System - Installation</h3>
                        <p class="text-muted mb-0">Step <?php echo $step; ?> of 4</p>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($step == 1): ?>
                            <h5>Step 1: Database Configuration</h5>
                            <p>Enter your database connection details:</p>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="host" class="form-label">Database Host</label>
                                    <input type="text" class="form-control" id="host" name="host" value="localhost" required>
                                </div>
                                <div class="mb-3">
                                    <label for="dbname" class="form-label">Database Name</label>
                                    <input type="text" class="form-control" id="dbname" name="dbname" required>
                                </div>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Database Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Database Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Test Connection & Continue</button>
                            </form>
                            
                        <?php elseif ($step == 2): ?>
                            <h5>Step 2: Import Database Structure</h5>
                            <p>This will create all necessary tables and the admin user.</p>
                            <form method="POST">
                                <button type="submit" class="btn btn-primary">Import Database Structure</button>
                            </form>
                            
                        <?php elseif ($step == 3): ?>
                            <h5>Step 3: Create Uploads Directory</h5>
                            <p>This creates the directory for storing company logos.</p>
                            <form method="POST">
                                <button type="submit" class="btn btn-primary">Create Uploads Directory</button>
                            </form>
                            
                        <?php elseif ($step == 4): ?>
                            <h5>Step 4: Installation Complete!</h5>
                            <div class="alert alert-success">
                                <h6>Your invoicing system has been installed successfully!</h6>
                                <p><strong>Login Credentials:</strong></p>
                                <ul>
                                    <li>Username: <code>admin</code></li>
                                    <li>Password: <code>password</code></li>
                                </ul>
                                <p class="mb-0"><strong>Important:</strong> Change the default password after your first login!</p>
                            </div>
                            <a href="index.php" class="btn btn-success">Go to Login Page</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($step > 1): ?>
                    <div class="mt-3">
                        <a href="?step=<?php echo $step - 1; ?>" class="btn btn-secondary">Previous Step</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 