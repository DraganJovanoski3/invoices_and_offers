<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/company_helper.php';

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

// Get company settings
$company = getCompanySettings($pdo);
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
    

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
                                        <a href="edit_offer.php?id=<?php echo $offer['id']; ?>" class="btn btn-sm btn-outline-warning">Уреди</a>
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

    

    
    <script>
        function deleteOffer(offerId, offerNumber) {
            document.getElementById('deleteOfferId').value = offerId;
            document.getElementById('deleteOfferNumber').textContent = offerNumber;
            new bootstrap.Modal(document.getElementById('deleteOfferModal')).show();
        }
    </script>
<?php include 'includes/footer.php'; ?>
</body>
</html> 