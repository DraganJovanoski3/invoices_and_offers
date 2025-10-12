<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/company_helper.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $client_id = $_POST['client_id'];
                $issue_date = $_POST['issue_date'];
                $due_date = $_POST['due_date'];
                $total_amount = $_POST['total_amount'];
                
                // Generate automatic invoice number
                $current_year = date('Y');
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE YEAR(created_at) = ?");
                $stmt->execute([$current_year]);
                $invoice_count = $stmt->fetchColumn();
                $invoice_number = $current_year . '-' . str_pad($invoice_count + 1, 3, '0', STR_PAD_LEFT);
                
                $stmt = $pdo->prepare("INSERT INTO invoices (client_id, invoice_number, issue_date, due_date, total_amount) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$client_id, $invoice_number, $issue_date, $due_date, $total_amount]);
                $success = "Фактурата е успешно креирана!";
                break;
                
            case 'delete':
                $invoice_id = $_POST['invoice_id'];
                
                // Delete invoice items first (due to foreign key constraint)
                $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
                $stmt->execute([$invoice_id]);
                
                // Delete the invoice
                $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
                $stmt->execute([$invoice_id]);
                $success = "Фактурата е успешно избрисана!";
                break;
        }
    }
}

// Get all invoices with client information
$stmt = $pdo->query("
    SELECT i.*, c.name as client_name 
    FROM invoices i 
    LEFT JOIN clients c ON i.client_id = c.id 
    ORDER BY i.created_at DESC
");
$invoices = $stmt->fetchAll();

// Get all clients for the dropdown
$stmt = $pdo->query("SELECT id, name FROM clients ORDER BY name");
$clients = $stmt->fetchAll();

// Get next invoice number
$current_year = date('Y');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE YEAR(created_at) = ?");
$stmt->execute([$current_year]);
$invoice_count = $stmt->fetchColumn();
$next_invoice_number = $current_year . '-' . str_pad($invoice_count + 1, 3, '0', STR_PAD_LEFT);

// Get company settings
$company = getCompanySettings($pdo);
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
    

    <div class="container mt-4 main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Фактури</h2>
            <a href="create_invoice.php" class="btn btn-primary">
                <i class="bi bi-plus"></i> Нова фактура
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
                                <th>Број на фактура</th>
                                <th>Клиент</th>
                                <th>Датум на издавање</th>
                                <th>Датум на доспевање</th>
                                <th>Износ</th>
                                <th>Статус</th>
                                <th>Дејства</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['client_name']); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($invoice['issue_date'])); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($invoice['due_date'])); ?></td>
                                    <td><?php echo number_format($invoice['total_amount'], 2); ?> <?php echo $company['currency'] ?? 'MKD'; ?></td>
                                    <td>
                                        <?php
                                        $status_map = [
                                            'draft' => 'Нацрт',
                                            'sent' => 'Испратена',
                                            'paid' => 'Платена',
                                            'overdue' => 'Задоцнета'
                                        ];
                                        echo isset($status_map[$invoice['status']]) ? $status_map[$invoice['status']] : htmlspecialchars($invoice['status']);
                                        ?>
                                    </td>
                                    <td>
                                        <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-primary">Види</a>
                                        <a href="edit_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-warning">Уреди</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Дали сте сигурни дека сакате да ја избришете фактурата?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Избриши</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($invoices)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Нема додадени фактури.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Invoice Modal -->
    <div class="modal fade" id="addInvoiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="client_id" class="form-label">Client</label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value="">Select a client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo htmlspecialchars($client['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="invoice_number" class="form-label">Invoice Number</label>
                            <input type="text" class="form-control" id="invoice_number" name="invoice_number" value="<?php echo $next_invoice_number; ?>" readonly>
                            <div class="form-text">Invoice number is automatically generated</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="issue_date" class="form-label">Issue Date</label>
                                    <input type="date" class="form-control" id="issue_date" name="issue_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Due Date</label>
                                    <input type="date" class="form-control" id="due_date" name="due_date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="total_amount" class="form-label">Total Amount</label>
                            <input type="number" step="0.01" class="form-control" id="total_amount" name="total_amount" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteInvoiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete invoice <strong id="deleteInvoiceNumber"></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="invoice_id" id="deleteInvoiceId">
                        <button type="submit" class="btn btn-danger">Delete Invoice</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    </div>

    <script>
        function deleteInvoice(invoiceId, invoiceNumber) {
            document.getElementById('deleteInvoiceId').value = invoiceId;
            document.getElementById('deleteInvoiceNumber').textContent = invoiceNumber;
            new bootstrap.Modal(document.getElementById('deleteInvoiceModal')).show();
        }
    </script>
<?php include 'includes/footer.php'; ?>
</body>
</html> 