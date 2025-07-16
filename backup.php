<?php
// Invoicing System - Backup Script
// Run this to create backups of your database and files

session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';

$backup_dir = 'backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755);
}

$timestamp = date('Y-m-d_H-i-s');
$backup_file = $backup_dir . 'backup_' . $timestamp . '.sql';

// Create database backup
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $backup_content = "-- Invoicing System Database Backup\n";
    $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        // Get table structure
        $create_table = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        $backup_content .= "\n-- Table structure for table `$table`\n";
        $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
        $backup_content .= $create_table[1] . ";\n\n";
        
        // Get table data
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll();
        if (!empty($rows)) {
            $backup_content .= "-- Data for table `$table`\n";
            foreach ($rows as $row) {
                $values = array_map(function($value) use ($pdo) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return $pdo->quote($value);
                }, $row);
                $backup_content .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
            }
            $backup_content .= "\n";
        }
    }
    
    if (file_put_contents($backup_file, $backup_content)) {
        $success = "Database backup created successfully: " . basename($backup_file);
    } else {
        $error = "Could not create backup file. Check permissions.";
    }
    
} catch (Exception $e) {
    $error = "Backup failed: " . $e->getMessage();
}

// Get list of existing backups
$backups = [];
if (is_dir($backup_dir)) {
    $files = glob($backup_dir . '*.sql');
    foreach ($files as $file) {
        $backups[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'date' => filemtime($file)
        ];
    }
    // Sort by date (newest first)
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup - Invoicing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Invoicing System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="invoices.php">Invoices</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="offers.php">Offers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="clients.php">Clients</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="company_settings.php">Settings</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="bi bi-download"></i> Database Backup</h4>
                        <p class="text-muted mb-0">Create and manage database backups</p>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Create New Backup</h5>
                                <p>Create a complete backup of your database including all tables and data.</p>
                                <form method="POST">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-download"></i> Create Backup
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <h5>Backup Information</h5>
                                <ul class="list-unstyled">
                                    <li><strong>Backup Location:</strong> backups/ folder</li>
                                    <li><strong>Backup Format:</strong> SQL file</li>
                                    <li><strong>Includes:</strong> All tables and data</li>
                                    <li><strong>Timestamp:</strong> Automatic naming</li>
                                </ul>
                            </div>
                        </div>
                        
                        <?php if (!empty($backups)): ?>
                            <hr>
                            <h5>Existing Backups</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Backup File</th>
                                            <th>Size</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($backups as $backup): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($backup['name']); ?></td>
                                                <td><?php echo number_format($backup['size'] / 1024, 2); ?> KB</td>
                                                <td><?php echo date('Y-m-d H:i:s', $backup['date']); ?></td>
                                                <td>
                                                    <a href="<?php echo $backup_dir . $backup['name']; ?>" class="btn btn-sm btn-outline-primary" download>
                                                        <i class="bi bi-download"></i> Download
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <hr>
                            <p class="text-muted">No backups found. Create your first backup above.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 