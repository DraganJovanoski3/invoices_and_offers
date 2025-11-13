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

// Function to generate next auto-increment code
function generateNextServiceCode($pdo) {
    // Get the highest numeric code
    $stmt = $pdo->query("SELECT article_code FROM services WHERE article_code REGEXP '^[0-9]+$' ORDER BY CAST(article_code AS UNSIGNED) DESC LIMIT 1");
    $result = $stmt->fetch();
    
    if ($result && !empty($result['article_code'])) {
        $next_number = intval($result['article_code']) + 1;
    } else {
        $next_number = 1;
    }
    
    // Format as 001, 002, 003, etc.
    return str_pad($next_number, 3, '0', STR_PAD_LEFT);
}

// Handle service creation/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_service'])) {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $article_code = trim($_POST['article_code']);
    
    if (empty($name) || $price < 0.01) {
        $error = "Името и цената на услугата се задолжителни!";
    } else {
        if (isset($_POST['service_id']) && !empty($_POST['service_id'])) {
            // Update existing service
            // If article_code is empty, keep the existing one
            if (empty($article_code)) {
                $stmt = $pdo->prepare("SELECT article_code FROM services WHERE id = ?");
                $stmt->execute([$_POST['service_id']]);
                $existing = $stmt->fetch();
                $article_code = $existing['article_code'] ?? '';
            }
            $stmt = $pdo->prepare("UPDATE services SET name = ?, price = ?, description = ?, article_code = ? WHERE id = ?");
            $stmt->execute([$name, $price, $description, $article_code, $_POST['service_id']]);
            $success = "Услугата е ажурирана успешно!";
        } else {
            // Create new service
            // If article_code is empty, generate auto-increment code
            if (empty($article_code)) {
                $article_code = generateNextServiceCode($pdo);
            }
            $stmt = $pdo->prepare("INSERT INTO services (name, price, description, article_code) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $price, $description, $article_code]);
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
<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
    

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
                                <label for="article_code" class="form-label">Шифра (Артикул код)</label>
                                <input type="text" class="form-control" id="article_code" name="article_code" 
                                       value="<?php echo htmlspecialchars($edit_service['article_code'] ?? ''); ?>" 
                                       placeholder="<?php echo $edit_service ? 'Остави празно за да го задржиш постоечкиот код' : 'Остави празно за автоматски код (001, 002, ...)'; ?>">
                                <small class="form-text text-muted">
                                    <?php if ($edit_service): ?>
                                        Остави празно за да го задржиш постоечкиот код. Внеси прилагоден код за да го промениш.
                                    <?php else: ?>
                                        Остави празно за автоматски код (001, 002, 003, ...) или внеси прилагоден код.
                                    <?php endif; ?>
                                </small>
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
                                            <th>Шифра</th>
                                            <th>Име на услугата</th>
                                            <th>Цена</th>
                                            <th>Опис</th>
                                            <th>Дејства</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($services as $service): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($service['article_code'] ?? 'N/A'); ?></td>
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

    

    
<?php include 'includes/footer.php'; ?>
</body>
</html> 