<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/company_helper.php';
$company_settings = getCompanySettings($pdo);
$currency = $company_settings['currency'] ?? 'MKD';

// Handle service deletion
if (isset($_POST['delete_service'])) {
    $service_id = $_POST['service_id'];
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    $stmt->execute([$service_id]);
    $success = "Услугата е избришана успешно!";
}

// Handle service creation/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_service'])) {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    
    if (empty($name) || $price < 0.01) {
        $error = "Името и цената на услугата се задолжителни!";
    } else {
        if (isset($_POST['service_id']) && !empty($_POST['service_id'])) {
            // Update existing service
            $stmt = $pdo->prepare("UPDATE services SET name = ?, price = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $price, $description, $_POST['service_id']]);
            $success = "Услугата е ажурирана успешно!";
        } else {
            // Create new service
            $stmt = $pdo->prepare("INSERT INTO services (name, price, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $price, $description]);
            $success = "Услугата е создадена успешно!";
        }
    }
}

// Get all services
$stmt = $pdo->query("SELECT * FROM services ORDER BY name");
$services = $stmt->fetchAll();

// Get service for editing
$edit_service = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_service = $stmt->fetch();
}

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
    <title>Services - Invoicing System</title>
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
                        <a class="nav-link active" href="services.php">Услуги</a>
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
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <?php echo $edit_service ? 'Измени услуга' : 'Додај нова услуга'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <?php if ($edit_service): ?>
                                <input type="hidden" name="service_id" value="<?php echo $edit_service['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Име на услугата *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($edit_service['name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="price" class="form-label">Цена *</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0.01" value="<?php echo $edit_service['price'] ?? ''; ?>" required>
                                    <span class="input-group-text"><?php echo $currency; ?></span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Опис</label>
                                <textarea class="form-control" id="description" name="description" rows="3"
                                          placeholder="Опционален опис на услугата"><?php echo htmlspecialchars($edit_service['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $edit_service ? 'Ажурирај услуга' : 'Додај услуга'; ?>
                                </button>
                                
                                <?php if ($edit_service): ?>
                                    <a href="services.php" class="btn btn-secondary">Откажи</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Листа на услуги</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($services)): ?>
                            <p class="text-muted">Нема додадени услуги. Додајте ја вашата прва услуга со користење на формата на лево.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Име на услугата</th>
                                            <th>Цена</th>
                                            <th>Опис</th>
                                            <th>Дејства</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($services as $service): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($service['name']); ?></td>
                                                <td><?php echo number_format($service['price'], 2); ?> <?php echo $currency; ?></td>
                                                <td>
                                                    <?php if ($service['description']): ?>
                                                        <span class="text-muted"><?php echo htmlspecialchars($service['description']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Нема опис</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?edit=<?php echo $service['id']; ?>" 
                                                           class="btn btn-outline-primary">
                                                            <i class="bi bi-pencil"></i> Измени
                                                        </a>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteModal<?php echo $service['id']; ?>">
                                                            <i class="bi bi-trash"></i> Избриши
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Delete Modal -->
                                                    <div class="modal fade" id="deleteModal<?php echo $service['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Потврди избриши</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Дали сте сигурни дека сакате да ја избришете услугата "<strong><?php echo htmlspecialchars($service['name']); ?></strong>"?</p>
                                                                    <p class="text-danger">Оваа акција не може да се откаже.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Откажи</button>
                                                                    <form method="POST" style="display: inline;">
                                                                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                                                        <button type="submit" name="delete_service" class="btn btn-danger">Избриши услуга</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
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