<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/company_helper.php';

// Get current year statistics
$current_year = date('Y');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE YEAR(created_at) = ?");
$stmt->execute([$current_year]);
$invoices_this_year = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM offers WHERE YEAR(created_at) = ?");
$stmt->execute([$current_year]);
$offers_this_year = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM pre_invoices WHERE YEAR(created_at) = ?");
$stmt->execute([$current_year]);
$preinvoices_this_year = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM clients");
$stmt->execute();
$total_clients = $stmt->fetchColumn();

// Get company settings
$company = getCompanySettings($pdo);
$page_title = 'Почетна';
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-4 main-content">
    <!-- Statistics Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-primary"><?php echo $invoices_this_year; ?></h3>
                    <p class="card-text">Фактури оваа година</p>
                    <a href="invoices.php" class="btn btn-primary btn-sm">Погледни</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success"><?php echo $offers_this_year; ?></h3>
                    <p class="card-text">Понуди оваа година</p>
                    <a href="offers.php" class="btn btn-success btn-sm">Погледни</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-warning"><?php echo $preinvoices_this_year; ?></h3>
                    <p class="card-text">Про Фактури оваа година</p>
                    <a href="preinvoices.php" class="btn btn-warning btn-sm">Погледни</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info"><?php echo $total_clients; ?></h3>
                    <p class="card-text">Вкупно клиенти</p>
                    <a href="clients.php" class="btn btn-info btn-sm">Погледни</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Row -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Брзи акции</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="create_invoice.php" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle"></i> Нова фактура
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="create_offer.php" class="btn btn-success w-100">
                                <i class="bi bi-plus-circle"></i> Нова понуда
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="create_preinvoice.php" class="btn btn-warning w-100">
                                <i class="bi bi-plus-circle"></i> Нова про фактура
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="clients.php?action=add" class="btn btn-info w-100">
                                <i class="bi bi-plus-circle"></i> Нов клиент
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Row -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Последни фактури</h5>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->prepare("SELECT i.*, c.name as client_name FROM invoices i LEFT JOIN clients c ON i.client_id = c.id ORDER BY i.created_at DESC LIMIT 5");
                    $stmt->execute();
                    $recent_invoices = $stmt->fetchAll();
                    ?>
                    <?php if (!empty($recent_invoices)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_invoices as $invoice): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($invoice['client_name']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary"><?php echo number_format($invoice['total_amount'], 2); ?> <?php echo $company['currency'] ?? 'MKD'; ?></span>
                                        <br>
                                        <small class="text-muted"><?php echo date('d.m.Y', strtotime($invoice['created_at'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="invoices.php" class="btn btn-outline-primary btn-sm">Погледни сите</a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">Нема фактури</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Последни клиенти</h5>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM clients ORDER BY created_at DESC LIMIT 5");
                    $stmt->execute();
                    $recent_clients = $stmt->fetchAll();
                    ?>
                    <?php if (!empty($recent_clients)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_clients as $client): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($client['name']); ?></strong>
                                        <?php if ($client['company_name']): ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($client['company_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-end">
                                        <?php if ($client['email']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($client['email']); ?></small>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted"><?php echo date('d.m.Y', strtotime($client['created_at'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="clients.php" class="btn btn-outline-primary btn-sm">Погледни сите</a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">Нема клиенти</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
