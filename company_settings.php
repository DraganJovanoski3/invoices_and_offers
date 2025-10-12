<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/company_helper.php';
$company = getCompanySettings($pdo);

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
    $vat_registration_number = $_POST['vat_registration_number'] ?? '';
    $authorized_person = $_POST['authorized_person'] ?? '';
    $legal_footer = $_POST['legal_footer'] ?? '';
    
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
                $stmt = $pdo->prepare("UPDATE company_settings SET company_name = ?, address = ?, phone = ?, email = ?, website = ?, tax_number = ?, bank_account = ?, logo_path = ?, currency = ?, vat_registration_number = ?, authorized_person = ?, legal_footer = ? WHERE id = 1");
                $stmt->execute([$company_name, $address, $phone, $email, $website, $tax_number, $bank_account, $logo_path, $currency, $vat_registration_number, $authorized_person, $legal_footer]);
            } else {
                $stmt = $pdo->prepare("UPDATE company_settings SET company_name = ?, address = ?, phone = ?, email = ?, website = ?, tax_number = ?, bank_account = ?, currency = ?, vat_registration_number = ?, authorized_person = ?, legal_footer = ? WHERE id = 1");
                $stmt->execute([$company_name, $address, $phone, $email, $website, $tax_number, $bank_account, $currency, $vat_registration_number, $authorized_person, $legal_footer]);
            }
        } else {
            // Insert new settings
            $stmt = $pdo->prepare("INSERT INTO company_settings (company_name, address, phone, email, website, tax_number, bank_account, logo_path, currency, vat_registration_number, authorized_person, legal_footer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$company_name, $address, $phone, $email, $website, $tax_number, $bank_account, $logo_path, $currency, $vat_registration_number, $authorized_person, $legal_footer]);
        }
        
        $success = "Поставките на компанијата успешно ажурирани!";
        
        // Refresh company data
        $stmt = $pdo->query("SELECT * FROM company_settings LIMIT 1");
        $company = $stmt->fetch();
    }
}
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
    

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

                            <!-- Legal Requirements Section -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="vat_registration_number" class="form-label">ДДВ регистрациски број</label>
                                        <input type="text" class="form-control" id="vat_registration_number" name="vat_registration_number" 
                                               value="<?php echo htmlspecialchars($company['vat_registration_number'] ?? ''); ?>">
                                        <small class="form-text text-muted">ДДВ регистрациски број (различен од даночниот број)</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="authorized_person" class="form-label">Овластено лице</label>
                                        <input type="text" class="form-control" id="authorized_person" name="authorized_person" 
                                               value="<?php echo htmlspecialchars($company['authorized_person'] ?? ''); ?>">
                                        <small class="form-text text-muted">Лице кое е овластено да издава фактури</small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="legal_footer" class="form-label">Правен текст за фактури</label>
                                <textarea class="form-control" id="legal_footer" name="legal_footer" rows="3" placeholder="Оваа фактура е издадена согласно со Законот за ДДВ на Република Македонија..."><?php echo htmlspecialchars($company['legal_footer'] ?? ''); ?></textarea>
                                <small class="form-text text-muted">Правен текст кој ќе се прикажува на сите фактури и про фактури</small>
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

<?php include 'includes/footer.php'; ?>
</body>
</html> 