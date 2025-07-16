<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';

// Get current company settings first
$stmt = $pdo->query("SELECT * FROM company_settings LIMIT 1");
$company = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = $_POST['company_name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $website = $_POST['website'];
    $tax_number = $_POST['tax_number'];
    $bank_account = $_POST['bank_account'];
    $currency = $_POST['currency'] ?? 'MKD';
    
    $logo_path = null;
    
    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $logo_filename = 'company_logo_' . time() . '.' . $file_extension;
            $logo_path = $upload_dir . $logo_filename;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path)) {
                // Delete old logo if exists
                if ($company && $company['logo_path'] && file_exists($company['logo_path'])) {
                    unlink($company['logo_path']);
                }
            } else {
                $error = "Неуспешно отварање на лого. Обидете се повторно.";
            }
        } else {
            $error = "Неважечки тип на датотека. Ве молам, отварајте само JPG, PNG или GIF датотеки.";
        }
    }
    
    if (!isset($error)) {
        // Check if company settings exist
        $stmt = $pdo->query("SELECT COUNT(*) FROM company_settings");
        $exists = $stmt->fetchColumn();
        
        if ($exists > 0) {
            // Update existing settings
            if ($logo_path) {
                $stmt = $pdo->prepare("UPDATE company_settings SET company_name = ?, address = ?, phone = ?, email = ?, website = ?, tax_number = ?, bank_account = ?, logo_path = ?, currency = ? WHERE id = 1");
                $stmt->execute([$company_name, $address, $phone, $email, $website, $tax_number, $bank_account, $logo_path, $currency]);
            } else {
                $stmt = $pdo->prepare("UPDATE company_settings SET company_name = ?, address = ?, phone = ?, email = ?, website = ?, tax_number = ?, bank_account = ?, currency = ? WHERE id = 1");
                $stmt->execute([$company_name, $address, $phone, $email, $website, $tax_number, $bank_account, $currency]);
            }
        } else {
            // Insert new settings
            $stmt = $pdo->prepare("INSERT INTO company_settings (company_name, address, phone, email, website, tax_number, bank_account, logo_path, currency) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$company_name, $address, $phone, $email, $website, $tax_number, $bank_account, $logo_path, $currency]);
        }
        
        $success = "Поставките на компанијата успешно ажурирани!";
        
        // Refresh company data
        $stmt = $pdo->query("SELECT * FROM company_settings LIMIT 1");
        $company = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Settings - Invoicing System</title>
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
            <?php
            $logo_path = '/uploads/company_logo.png';
            $upload_dir = __DIR__ . '/uploads/';
            $logo_files = glob($upload_dir . 'company_logo*.png');
            if ($logo_files && count($logo_files) > 0) {
                $logo_path = '/uploads/' . basename($logo_files[0]);
            }
            ?>
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
                        <a class="nav-link" href="dashboard.php">Почетна</a>
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
                        <a class="nav-link active" href="company_settings.php">Поставки</a>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="bi bi-building"></i> Поставки за компанијата</h4>
                        <p class="text-muted mb-0">Ажурирајте информациите за вашата компанија кои ќе се појават на фактури и понуди</p>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="company_name" class="form-label">Име на компанија</label>
                                        <input type="text" class="form-control" id="company_name" name="company_name" 
                                               value="<?php echo htmlspecialchars($company['company_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Е-пошта</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($company['email'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="logo" class="form-label">Лого</label>
                                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                        <div class="form-text">Отварајте JPG, PNG или GIF датотеки. Максимална големина: 2MB</div>
                                        <?php if ($company && $company['logo_path']): ?>
                                            <div class="mt-2">
                                                <img src="<?php echo htmlspecialchars($company['logo_path']); ?>" alt="Лого" style="max-width: 150px; max-height: 100px;" class="img-thumbnail">
                                                <small class="text-muted d-block">Лого</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="website" class="form-label">Веб-страна</label>
                                        <input type="url" class="form-control" id="website" name="website" 
                                               value="<?php echo htmlspecialchars($company['website'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Телефон</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($company['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Адреса</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($company['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="tax_number" class="form-label">Даночен број</label>
                                        <input type="text" class="form-control" id="tax_number" name="tax_number" 
                                               value="<?php echo htmlspecialchars($company['tax_number'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="bank_account" class="form-label">Жиро сметка</label>
                                        <input type="text" class="form-control" id="bank_account" name="bank_account" 
                                               value="<?php echo htmlspecialchars($company['bank_account'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Add currency dropdown to the form -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="currency" class="form-label">Валута</label>
                                        <select class="form-select" id="currency" name="currency">
                                            <option value="MKD" <?php if (($company['currency'] ?? 'MKD') == 'MKD') echo 'selected'; ?>>MKD (денари)</option>
                                            <option value="EUR" <?php if (($company['currency'] ?? '') == 'EUR') echo 'selected'; ?>>EUR (евра)</option>
                                            <option value="USD" <?php if (($company['currency'] ?? '') == 'USD') echo 'selected'; ?>>USD (долари)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dashboard.php" class="btn btn-secondary me-md-2">Откажи</a>
                                <button type="submit" class="btn btn-primary">Зачувај</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Preview Section -->
                <?php if ($company): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="bi bi-eye"></i> Преглед</h5>
                        <p class="text-muted mb-0">Како ќе се појават информациите за вашата компанија на документи</p>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Информации за компанија</h6>
                                <?php if ($company['logo_path']): ?>
                                    <div class="mb-3">
                                        <img src="<?php echo htmlspecialchars($company['logo_path']); ?>" alt="Лого на компанија" style="max-width: 200px; max-height: 100px;" class="img-fluid">
                                    </div>
                                <?php endif; ?>
                                <p><strong><?php echo htmlspecialchars($company['company_name']); ?></strong></p>
                                <?php if ($company['address']): ?>
                                    <p><?php echo nl2br(htmlspecialchars($company['address'])); ?></p>
                                <?php endif; ?>
                                <?php if ($company['phone']): ?>
                                    <p>Телефон: <?php echo htmlspecialchars($company['phone']); ?></p>
                                <?php endif; ?>
                                <?php if ($company['email']): ?>
                                    <p>Е-пошта: <?php echo htmlspecialchars($company['email']); ?></p>
                                <?php endif; ?>
                                <?php if ($company['website']): ?>
                                    <p>Веб-страна: <?php echo htmlspecialchars($company['website']); ?></p>
                                <?php endif; ?>
                                <?php if ($company['currency']): ?>
                                    <p><strong>Валута:</strong> <?php echo htmlspecialchars($company['currency']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h6>Дополнителни детали</h6>
                                <?php if ($company['tax_number']): ?>
                                    <p>Даночен број: <?php echo htmlspecialchars($company['tax_number']); ?></p>
                                <?php endif; ?>
                                <?php if ($company['bank_account']): ?>
                                    <p>Жиро сметка: <?php echo htmlspecialchars($company['bank_account']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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