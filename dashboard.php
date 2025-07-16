<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Force reload only once after login
if (empty($_SESSION['dashboard_reloaded'])) {
    $_SESSION['dashboard_reloaded'] = true;
    echo "<script>window.location.reload(true);</script>";
    exit;
}

require_once 'config/database.php';

// Get current year statistics
$current_year = date('Y');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE YEAR(created_at) = ?");
$stmt->execute([$current_year]);
$invoices_this_year = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM offers WHERE YEAR(created_at) = ?");
$stmt->execute([$current_year]);
$offers_this_year = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM clients");
$stmt->execute();
$total_clients = $stmt->fetchColumn();

$logo_path = '/uploads/company_logo.png';
$upload_dir = __DIR__ . '/uploads/';
$logo_files = glob($upload_dir . 'company_logo*.png');
if ($logo_files && count($logo_files) > 0) {
    $logo_path = '/uploads/' . basename($logo_files[0]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Invoicing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1;
        }
        footer {
            margin-top: auto;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="<?php echo $logo_path; ?>" alt="DDS Logo" style="height:32px; width:auto; margin-right:10px;">
                Фактури и Понуди
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Почетна</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="invoices.php">Фактури</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="offers.php">Понуди</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="clients.php">Клиенти</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">Услуги</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="company_settings.php">Поставки</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Одјава</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4 main-content">
        <!-- Statistics Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-primary"><?php echo $invoices_this_year; ?></h3>
                        <p class="card-text">Фактури (<?php echo $current_year; ?>)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-success"><?php echo $offers_this_year; ?></h3>
                        <p class="card-text">Понуди (<?php echo $current_year; ?>)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-info"><?php echo $total_clients; ?></h3>
                        <p class="card-text">Вкупно клиенти</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-warning"><?php echo $current_year; ?></h3>
                        <p class="card-text">Тековна година</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Фактури</h5>
                        <p class="card-text">Управувајте со вашите фактури</p>
                        <a href="invoices.php" class="btn btn-primary">Види фактури</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Понуди</h5>
                        <p class="card-text">Управувајте со вашите понуди</p>
                        <a href="offers.php" class="btn btn-primary">Види понуди</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Клиенти</h5>
                        <p class="card-text">Управувајте со вашите клиенти</p>
                        <a href="clients.php" class="btn btn-primary">Види клиенти</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Поставки</h5>
                        <p class="card-text">Управувајте со податоците за компанијата</p>
                        <a href="company_settings.php" class="btn btn-primary">Поставки на компанијата</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark mt-5 py-3">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p class="mb-0" style="color: #38BDF8;">Custom Invoicing System made by <strong><a href="https://ddsolutions.com.mk/" target="_blank" style="color: #38BDF8; text-decoration: none;">DDSolutions</a></strong></p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 