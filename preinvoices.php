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
    $preinvoice_id = $_POST['preinvoice_id'];
    // Delete pre-invoice items first (due to foreign key constraint)
    $stmt = $pdo->prepare("DELETE FROM pre_invoice_items WHERE preinvoice_id = ?");
    $stmt->execute([$preinvoice_id]);
    // Delete the pre-invoice
    $stmt = $pdo->prepare("DELETE FROM pre_invoices WHERE id = ?");
    $stmt->execute([$preinvoice_id]);
    $success = "Про Фактурата е избришана успешно!";
}

// Get all pre-invoices with client information
$stmt = $pdo->query("
    SELECT pf.*, c.name as client_name
    FROM pre_invoices pf
    LEFT JOIN clients c ON pf.client_id = c.id
    ORDER BY pf.created_at DESC
");
$preinvoices = $stmt->fetchAll();

// Get company settings for currency
$stmt = $pdo->prepare("SELECT currency FROM company_settings WHERE id = 1");
$stmt->execute();
$company = $stmt->fetch();
$currency = $company['currency'] ?? 'ден';
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4 main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Про Фактури</h2>
            <a href="create_preinvoice.php" class="btn btn-primary">
                <i class="bi bi-plus"></i> Нова Про Фактура
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
                                <th>Број</th>
                                <th>Клиент</th>
                                <th>Датум на издавање</th>
                                <th>Датум на доспевање</th>
                                <th>Износ</th>
                                <th>Тип на плаќање</th>
                                <th>Статус</th>
                                <th>Дејства</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preinvoices as $pf): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pf['preinvoice_number']); ?></td>
                                    <td><?php echo htmlspecialchars($pf['client_name']); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($pf['issue_date'])); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($pf['due_date'])); ?></td>
                                    <td><?php echo number_format($pf['total_amount'], 2); ?> <?php echo $currency; ?></td>
                                    <td><?php echo $pf['payment_type'] === 'advance' ? 'Аванс' : 'Целосно'; ?></td>
                                    <td>
                                        <?php
                                        $status_map = [
                                            'draft' => 'Нацрт',
                                            'sent' => 'Испратена',
                                            'paid' => 'Платена',
                                            'cancelled' => 'Откажана'
                                        ];
                                        echo isset($status_map[$pf['status']]) ? $status_map[$pf['status']] : htmlspecialchars($pf['status']);
                                        ?>
                                    </td>
                                    <td>
                                        <a href="view_preinvoice.php?id=<?php echo $pf['id']; ?>" class="btn btn-sm btn-outline-primary">Види</a>
                                        <a href="edit_preinvoice.php?id=<?php echo $pf['id']; ?>" class="btn btn-sm btn-outline-warning">Уреди</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Дали сте сигурни дека сакате да ја избришете Про Фактурата?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="preinvoice_id" value="<?php echo $pf['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Избриши</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($preinvoices)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Нема додадени Про Фактури.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    
<?php include 'includes/footer.php'; ?>
</body>
</html> 