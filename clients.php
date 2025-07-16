<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'];
                $company_name = $_POST['company_name'] ?? null;
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $address = $_POST['address'];
                $tax_number = $_POST['tax_number'] ?? null;
                $bank_account = $_POST['bank_account'] ?? null;
                $website = $_POST['website'] ?? null;
                $notes = $_POST['notes'] ?? null;
                $logo_path = null;
                // Handle logo upload
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                    $logo_filename = 'client_logo_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                    $upload_path = 'uploads/' . $logo_filename;
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                        $logo_path = $upload_path;
                    }
                }
                // Add bank_account column if not exists
                $pdo->exec("ALTER TABLE clients ADD COLUMN IF NOT EXISTS bank_account VARCHAR(100)");
                $stmt = $pdo->prepare("INSERT INTO clients (name, company_name, logo_path, email, phone, address, tax_number, bank_account, website, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $company_name, $logo_path, $email, $phone, $address, $tax_number, $bank_account, $website, $notes]);
                $success = "Клиентот е успешно додаден!";
                break;
            case 'edit':
                $client_id = $_POST['client_id'];
                $name = $_POST['name'];
                $company_name = $_POST['company_name'] ?? null;
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $address = $_POST['address'];
                $tax_number = $_POST['tax_number'] ?? null;
                $bank_account = $_POST['bank_account'] ?? null;
                $website = $_POST['website'] ?? null;
                $notes = $_POST['notes'] ?? null;
                $logo_path = $_POST['existing_logo_path'] ?? null;
                // Handle logo upload
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                    $logo_filename = 'client_logo_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                    $upload_path = 'uploads/' . $logo_filename;
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                        $logo_path = $upload_path;
                    }
                }
                $stmt = $pdo->prepare("UPDATE clients SET name=?, company_name=?, logo_path=?, email=?, phone=?, address=?, tax_number=?, bank_account=?, website=?, notes=? WHERE id=?");
                $stmt->execute([$name, $company_name, $logo_path, $email, $phone, $address, $tax_number, $bank_account, $website, $notes, $client_id]);
                $success = "Клиентот е успешно ажуриран!";
                break;
            case 'delete':
                $client_id = $_POST['client_id'];
                
                // Check if client has invoices or offers
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE client_id = ?");
                $stmt->execute([$client_id]);
                $invoice_count = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM offers WHERE client_id = ?");
                $stmt->execute([$client_id]);
                $offer_count = $stmt->fetchColumn();
                
                if ($invoice_count > 0 || $offer_count > 0) {
                    $error = "Не можете да го избришете клиентот. Овој клиент има " . ($invoice_count + $offer_count) . " асоцирани фактури или понуди. Ве молам, избришете ги прво.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
                    $stmt->execute([$client_id]);
                    $success = "Клиентот е успешно избришан!";
                }
                break;
        }
    }
}

// Get all clients
$stmt = $pdo->query("SELECT * FROM clients ORDER BY name");
$clients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - Invoicing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php
    $logo_path = '/uploads/company_logo.png';
    $upload_dir = __DIR__ . '/uploads/';
    $logo_files = glob($upload_dir . 'company_logo*.png');
    if ($logo_files && count($logo_files) > 0) {
        $logo_path = '/uploads/' . basename($logo_files[0]);
    }
    ?>
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
                        <a class="nav-link" href="dashboard.php">Почетна</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="invoices.php">Фактури</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="offers.php">Понуди</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="clients.php">Клиенти</a>
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

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Клиенти</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                <i class="bi bi-plus"></i> Нов клиент
            </button>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($clients as $client): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <?php if (!empty($client['logo_path'])): ?>
                                <div class="mb-2 text-center">
                                    <img src="<?php echo htmlspecialchars($client['logo_path']); ?>" alt="Лого" style="max-width: 60px; max-height: 60px; object-fit: contain;">
                                </div>
                            <?php endif; ?>
                            <h5 class="card-title"><?php echo htmlspecialchars($client['name']); ?></h5>
                            <?php if (!empty($client['company_name'])): ?>
                                <div class="mb-1"><strong>Компанија:</strong> <?php echo htmlspecialchars($client['company_name']); ?></div>
                            <?php endif; ?>
                            <p class="card-text mb-1">
                                <strong>Е-пошта:</strong> <?php echo htmlspecialchars($client['email'] ?? 'Нема'); ?><br>
                                <strong>Телефон:</strong> <?php echo htmlspecialchars($client['phone'] ?? 'Нема'); ?><br>
                                <strong>Адреса:</strong> <?php echo htmlspecialchars($client['address'] ?? 'Нема'); ?>
                            </p>
                            <?php if (!empty($client['tax_number'])): ?>
                                <div class="mb-1"><strong>Даночен број:</strong> <?php echo htmlspecialchars($client['tax_number']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($client['bank_account'])): ?>
                                <div class="mb-1"><strong>Жиро сметка:</strong> <?php echo htmlspecialchars($client['bank_account']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($client['website'])): ?>
                                <div class="mb-1"><strong>Веб-страна:</strong> <a href="<?php echo htmlspecialchars($client['website']); ?>" target="_blank"><?php echo htmlspecialchars($client['website']); ?></a></div>
                            <?php endif; ?>
                            <?php if (!empty($client['notes'])): ?>
                                <div class="mb-1"><strong>Забелешки:</strong> <?php echo nl2br(htmlspecialchars($client['notes'])); ?></div>
                            <?php endif; ?>
                            <div class="btn-group mt-2" role="group">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editClientModal<?php echo $client['id']; ?>">Измени</button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteClient(<?php echo $client['id']; ?>, '<?php echo htmlspecialchars($client['name']); ?>')">Избриши</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Edit Client Modal -->
                <div class="modal fade" id="editClientModal<?php echo $client['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Измени клиент</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                                    <input type="hidden" name="existing_logo_path" value="<?php echo htmlspecialchars($client['logo_path']); ?>">
                                    <div class="mb-3">
                                        <label for="name<?php echo $client['id']; ?>" class="form-label">Име</label>
                                        <input type="text" class="form-control" id="name<?php echo $client['id']; ?>" name="name" value="<?php echo htmlspecialchars($client['name']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="company_name<?php echo $client['id']; ?>" class="form-label">Име на компанија</label>
                                        <input type="text" class="form-control" id="company_name<?php echo $client['id']; ?>" name="company_name" value="<?php echo htmlspecialchars($client['company_name']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="logo<?php echo $client['id']; ?>" class="form-label">Лого</label>
                                        <?php if (!empty($client['logo_path'])): ?>
                                            <div class="mb-2">
                                                <img src="<?php echo htmlspecialchars($client['logo_path']); ?>" alt="Лого" style="max-width: 60px; max-height: 60px; object-fit: contain;">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" id="logo<?php echo $client['id']; ?>" name="logo" accept="image/*">
                                    </div>
                                    <div class="mb-3">
                                        <label for="email<?php echo $client['id']; ?>" class="form-label">Е-пошта</label>
                                        <input type="email" class="form-control" id="email<?php echo $client['id']; ?>" name="email" value="<?php echo htmlspecialchars($client['email']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone<?php echo $client['id']; ?>" class="form-label">Телефон</label>
                                        <input type="tel" class="form-control" id="phone<?php echo $client['id']; ?>" name="phone" value="<?php echo htmlspecialchars($client['phone']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="address<?php echo $client['id']; ?>" class="form-label">Адреса</label>
                                        <textarea class="form-control" id="address<?php echo $client['id']; ?>" name="address" rows="3"><?php echo htmlspecialchars($client['address']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="tax_number<?php echo $client['id']; ?>" class="form-label">Даночен број</label>
                                        <input type="text" class="form-control" id="tax_number<?php echo $client['id']; ?>" name="tax_number" value="<?php echo htmlspecialchars($client['tax_number']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="bank_account<?php echo $client['id']; ?>" class="form-label">Жиро сметка</label>
                                        <input type="text" class="form-control" id="bank_account<?php echo $client['id']; ?>" name="bank_account" value="<?php echo htmlspecialchars($client['bank_account']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="website<?php echo $client['id']; ?>" class="form-label">Веб-страна</label>
                                        <input type="text" class="form-control" id="website<?php echo $client['id']; ?>" name="website" value="<?php echo htmlspecialchars($client['website']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="notes<?php echo $client['id']; ?>" class="form-label">Забелешки</label>
                                        <textarea class="form-control" id="notes<?php echo $client['id']; ?>" name="notes" rows="2"><?php echo htmlspecialchars($client['notes']); ?></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Откажи</button>
                                    <button type="submit" class="btn btn-primary">Зачувај</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($clients)): ?>
            <div class="text-center mt-4">
                <p class="text-muted">Нема додадени клиенти. Додајте го вашиот прв клиент за да започнете!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Client Modal -->
    <div class="modal fade" id="addClientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Нов клиент</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="name" class="form-label">Име</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="company_name" class="form-label">Име на компанија</label>
                            <input type="text" class="form-control" id="company_name" name="company_name">
                        </div>
                        <div class="mb-3">
                            <label for="logo" class="form-label">Лого</label>
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Е-пошта</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Телефон</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Адреса</label>
                            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="tax_number" class="form-label">Даночен број</label>
                            <input type="text" class="form-control" id="tax_number" name="tax_number">
                        </div>
                        <div class="mb-3">
                            <label for="bank_account" class="form-label">Жиро сметка</label>
                            <input type="text" class="form-control" id="bank_account" name="bank_account">
                        </div>
                        <div class="mb-3">
                            <label for="website" class="form-label">Веб-страна</label>
                            <input type="text" class="form-control" id="website" name="website">
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Забелешки</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Откажи</button>
                        <button type="submit" class="btn btn-primary">Додај клиент</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteClientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Потврди бришење</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Дали сте сигурни дека сакате да го избришете клиентот <strong id="deleteClientName"></strong>?</p>
                    <p class="text-danger">Оваа акција е неповратна.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Откажи</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="client_id" id="deleteClientId">
                        <button type="submit" class="btn btn-danger">Избриши клиент</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteClient(clientId, clientName) {
            document.getElementById('deleteClientId').value = clientId;
            document.getElementById('deleteClientName').textContent = clientName;
            new bootstrap.Modal(document.getElementById('deleteClientModal')).show();
        }
    </script>
</body>
</html> 