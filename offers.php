<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';

// Handle delete form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $offer_id = $_POST['offer_id'];
    
    // Delete offer items first (due to foreign key constraint)
    $stmt = $pdo->prepare("DELETE FROM offer_items WHERE offer_id = ?");
    $stmt->execute([$offer_id]);
    
    // Delete the offer
    $stmt = $pdo->prepare("DELETE FROM offers WHERE id = ?");
    $stmt->execute([$offer_id]);
    $success = "Понудата е избришана успешно!";
}

// Get all offers with client information
$stmt = $pdo->query("
    SELECT o.*, c.name as client_name 
    FROM offers o 
    LEFT JOIN clients c ON o.client_id = c.id 
    ORDER BY o.created_at DESC
");
$offers = $stmt->fetchAll();

// Get next offer number
$current_year = date('Y');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM offers WHERE YEAR(created_at) = ?");
$stmt->execute([$current_year]);
$offer_count = $stmt->fetchColumn();
$next_offer_number = $current_year . '-' . str_pad($offer_count + 1, 3, '0', STR_PAD_LEFT);

// Get company settings for currency
$stmt = $pdo->prepare("SELECT currency FROM company_settings WHERE id = ?");
$stmt->execute([1]); // Assuming company_settings table has an ID of 1 for the main company
$company = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offers - Invoicing System</title>
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
                <?php
                $logo_path = '/uploads/company_logo.png';
                $upload_dir = __DIR__ . '/uploads/';
                $logo_files = glob($upload_dir . 'company_logo*.png');
                if ($logo_files && count($logo_files) > 0) {
                    $logo_path = '/uploads/' . basename($logo_files[0]);
                }
                ?>
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
                        <a class="nav-link active" href="offers.php">Понуди</a>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Понуди</h2>
            <a href="create_offer.php" class="btn btn-primary">
                <i class="bi bi-plus"></i> Нова понуда
            </a>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Број на понуда</th>
                                <th>Клиент</th>
                                <th>Датум на издавање</th>
                                <th>Важи до</th>
                                <th>Износ</th>
                                <th>Статус</th>
                                <th>Дејства</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($offers as $offer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($offer['offer_number']); ?></td>
                                    <td><?php echo htmlspecialchars($offer['client_name']); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($offer['issue_date'])); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($offer['valid_until'])); ?></td>
                                    <td><?php echo number_format($offer['total_amount'], 2); ?> <?php echo $company['currency'] ?? 'MKD'; ?></td>
                                    <td>
                                        <?php
                                        $status_map = [
                                            'draft' => 'Нацрт',
                                            'sent' => 'Испратена',
                                            'accepted' => 'Прифатена',
                                            'rejected' => 'Одбиена',
                                            'expired' => 'Истечена'
                                        ];
                                        echo isset($status_map[$offer['status']]) ? $status_map[$offer['status']] : htmlspecialchars($offer['status']);
                                        ?>
                                    </td>
                                    <td>
                                        <a href="view_offer.php?id=<?php echo $offer['id']; ?>" class="btn btn-sm btn-outline-primary">Види</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Дали сте сигурни дека сакате да ја избришете понудата?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Избриши</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($offers)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Нема додадени понуди.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteOfferModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Потврди бришење</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Дали сте сигурни дека сакате да ја избришете понудата <strong id="deleteOfferNumber"></strong>?</p>
                        <p class="text-danger">Оваа акција не може да се откаже.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Откажи</button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="offer_id" id="deleteOfferId">
                            <button type="submit" class="btn btn-danger">Избриши понуда</button>
                        </form>
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
    <script>
        function deleteOffer(offerId, offerNumber) {
            document.getElementById('deleteOfferId').value = offerId;
            document.getElementById('deleteOfferNumber').textContent = offerNumber;
            new bootstrap.Modal(document.getElementById('deleteOfferModal')).show();
        }
    </script>
</body>
</html> 