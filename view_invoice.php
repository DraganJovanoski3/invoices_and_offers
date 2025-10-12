<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/company_helper.php';

if (!isset($_GET['id'])) {
    header('Location: invoices.php');
    exit;
}

$invoice_id = $_GET['id'];

// Get invoice details with client information
$stmt = $pdo->prepare("
    SELECT i.*, c.name as client_name, c.company_name as client_company_name, c.logo_path as client_logo_path, c.email as client_email, c.phone as client_phone, c.address as client_address, c.tax_number as client_tax_number, c.website as client_website, c.notes as client_notes
    FROM invoices i 
    LEFT JOIN clients c ON i.client_id = c.id 
    WHERE i.id = ?
");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header('Location: invoices.php');
    exit;
}

// Get invoice items
$stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$stmt->execute([$invoice_id]);
$items = $stmt->fetchAll();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $invoice_id]);
    header("Location: view_invoice.php?id=$invoice_id");
    exit;
}

// Get company settings
$company = getCompanySettings($pdo);
$page_title = 'Фактура';

$logo_path = '/uploads/company_logo.png';
$upload_dir = __DIR__ . '/uploads/';
$logo_files = glob($upload_dir . 'company_logo*.png');
if ($logo_files && count($logo_files) > 0) {
    $logo_path = '/uploads/' . basename($logo_files[0]);
}

$currency = $company['currency'] ?? 'ден'; // Assuming currency is stored in company settings

$has_item_discount = false;
foreach ($items as $item) {
    if (!empty($item['discount_rate']) && $item['discount_rate'] > 0) {
        $has_item_discount = true;
        break;
    }
}

// Get company settings
$company = getCompanySettings($pdo);
$page_title = 'Фактура';
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-4 main-content">
        <div class="no-print mb-3">
            <a href="invoices.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Назад кон фактури
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Печати
            </button>
            <a href="download_invoice.php?id=<?php echo $invoice_id; ?>" class="btn btn-success">
                <i class="bi bi-download"></i> Преземи PDF
            </a>
        </div>

        <div class="invoice-container">
            <!-- HEADER: Centered title and number at the top -->
            <div style="max-width:900px; margin:0 auto; margin-bottom: 8px; ">
                <span style="font-size: 22pt; font-weight: bold; color: #007bff;">ФАКТУРА</span><br>
                <span style="font-size: 13pt; font-weight: bold; color: #333;">#<?php echo htmlspecialchars($invoice['invoice_number']); ?></span>
            </div>
            <!-- HEADER: Table with logo left/right and info -->
            <div style="max-width: 900px; margin: 0 auto; background: #fff; padding: 0px 30px 0px 30px; font-size: 10pt;">
                <table width="100%" style="border-collapse:collapse;">
                    <tr>
                        <!-- Client logo left -->
                        <td width="15%" style="vertical-align: top; text-align: left; padding-right: 10px;">
                            <?php if ($invoice['client_logo_path']): ?>
                                <img src="<?php echo htmlspecialchars($invoice['client_logo_path']); ?>" style="width:70px; height:auto; margin-bottom: 8px; border-radius: 6px; display:block;" />
                            <?php endif; ?>
                        </td>
                        <!-- Empty space -->
                        <td width="70%"></td>
                        <!-- Company logo right -->
                        <td width="15%" style="vertical-align: top; text-align: right; padding-left: 10px;">
                            <?php if ($company['logo_path']): ?>
                                <img src="<?php echo htmlspecialchars($company['logo_path']); ?>" style="width:70px; height:auto; margin-bottom: 8px; border-radius: 6px; display:block;" />
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            <!-- HEADER: Company and client info side by side -->
            <div style="max-width: 900px; margin: 0 auto; background: #fff; padding: 0px 30px 30px 30px; font-size: 10pt;">
                <table width="100%"><tr>
                    <!-- Client info left -->
                    <td width="50%" style="vertical-align: top; padding-right: 10px;">
                        <?php if ($invoice['client_company_name']): ?>
                            <div style="font-size: 12pt; font-weight: bold; color: #333; margin-bottom: 2px;"> <?php echo htmlspecialchars($invoice['client_company_name']); ?> </div>
                        <?php endif; ?>
                        <?php if ($invoice['client_address']): ?>
                            <div style="font-size: 9pt; color: #666; margin: 0px;"> <?php echo nl2br(htmlspecialchars($invoice['client_address'])); ?> </div>
                        <?php endif; ?>
                        <?php if ($invoice['client_name']): ?>
                            <div style="font-size: 9pt; color: #666; margin: 0px;"> Контакт: <?php echo htmlspecialchars($invoice['client_name']); ?> </div>
                        <?php endif; ?>
                        <?php if ($invoice['client_email']): ?>
                            <div style="font-size: 9pt; color: #666; margin: 0px;"> Е-пошта: <?php echo htmlspecialchars($invoice['client_email']); ?> </div>
                        <?php endif; ?>
                        <?php if ($invoice['client_phone']): ?>
                            <div style="font-size: 9pt; color: #666; margin: 0px;"> Телефон: <?php echo htmlspecialchars($invoice['client_phone']); ?> </div>
                        <?php endif; ?>
                        <?php if ($invoice['client_tax_number']): ?>
                            <div style="font-size: 9pt; color: #666; margin: 0px;"><strong>Даночен број:</strong> <?php echo htmlspecialchars($invoice['client_tax_number']); ?> </div>
                        <?php endif; ?>
                        <?php if ($invoice['client_website']): ?>
                            <div style="font-size: 9pt; color: #666; margin: 0px;"> Веб-страна: <?php echo htmlspecialchars($invoice['client_website']); ?> </div>
                        <?php endif; ?>
                        <?php if ($invoice['client_notes']): ?>
                            <div style="color: #888; font-size: 9pt; margin-bottom: 1px;"> Забелешки: <?php echo nl2br(htmlspecialchars($invoice['client_notes'])); ?> </div>
                        <?php endif; ?>
                    </td>
                    <!-- Company info right -->
                    <td width="50%" style="text-align: right; vertical-align: top; padding-left: 10px;">
                        <div style="font-size: 12pt; font-weight: bold; color: #333; margin-bottom: 2px;"> <?php echo htmlspecialchars($company['company_name']); ?> </div>
                        <?php if ($company['address']): ?>
                            <div style="font-size: 9pt; color: #666; margin: 0px;"> <?php echo nl2br(htmlspecialchars($company['address'])); ?> </div>
                        <?php endif; ?>
                        <?php if ($company['phone']): ?>
                            <div style="font-size: 9pt; color: #666; margin: 0px;"> Телефон: <?php echo htmlspecialchars($company['phone']); ?> </div>
                        <?php endif; ?>
                        <?php if ($company['email']): ?>
                            <div style="font-size: 9pt; color: #666; margin: 0px;"> Е-пошта: <?php echo htmlspecialchars($company['email']); ?> </div>
                        <?php endif; ?>
                        <?php if (!empty($company['tax_number'])): ?>
                            <div style="font-size: 9pt; color: #666; margin: 0px;"><strong>Даночен број:</strong> <?php echo htmlspecialchars($company['tax_number']); ?> </div>
                        <?php endif; ?>
                        <?php if (!empty($company['bank_account'])): ?>
                            <div style="font-size: 9pt; color: #666; margin: 0px;"><strong>Жиро сметка:</strong> <?php echo htmlspecialchars($company['bank_account']); ?> </div>
                        <?php endif; ?>
                    </td>
                </tr></table>
            </div>
            <!-- BLUE LINE -->
            <div style="max-width:900px; margin:0 auto; border-bottom:2px solid #007bff; margin-bottom:18px; margin-top:12px;"></div>
            <!-- DATES ROW -->
            <div style="margin-bottom: 0; padding-bottom: 0;">
                <table width="100%" style="margin-bottom:0; padding-bottom:0;"><tr>
                    <td width="50%" style="font-size: 10pt;"><strong>Датум на издавање:</strong> <?php echo date('d.m.Y', strtotime($invoice['issue_date'])); ?></td>
                    <td width="50%" style="font-size: 10pt;"><strong>Датум на доспевање:</strong> <?php echo date('d.m.Y', strtotime($invoice['due_date'])); ?></td>
                </tr></table>
            </div>
            
            <!-- LEGAL INFORMATION ROW -->
            <div style="margin-bottom: 15px; padding-bottom: 10px;">
                <table width="100%" style="margin-bottom:0; padding-bottom:0;"><tr>
                    <td width="50%" style="font-size: 10pt;"><strong>Датум на испорака:</strong> <?php echo $invoice['supply_date'] ? date('d.m.Y', strtotime($invoice['supply_date'])) : date('d.m.Y', strtotime($invoice['issue_date'])); ?></td>
                    <td width="50%" style="font-size: 10pt;"><strong>Начин на плаќање:</strong> 
                        <?php 
                        $payment_methods = [
                            'bank_transfer' => 'Банкарски трансфер',
                            'cash' => 'Готовина',
                            'card' => 'Картичка',
                            'check' => 'Чек',
                            'other' => 'Друго'
                        ];
                        echo $payment_methods[$invoice['payment_method']] ?? 'Банкарски трансфер';
                        ?>
                    </td>
                </tr></table>
            </div>
            
            <!-- AUTHORIZED PERSON ROW -->
            <?php if ($invoice['authorized_person']): ?>
            <div style="margin-bottom: 15px; padding-bottom: 10px;">
                <table width="100%" style="margin-bottom:0; padding-bottom:0;"><tr>
                    <td width="50%" style="font-size: 10pt;"><strong>Овластено лице:</strong> <?php echo htmlspecialchars($invoice['authorized_person']); ?></td>
                    <td width="50%" style="font-size: 10pt;">
                        <?php if ($invoice['fiscal_receipt_number']): ?>
                            <strong>Фискален број:</strong> <?php echo htmlspecialchars($invoice['fiscal_receipt_number']); ?>
                        <?php endif; ?>
                    </td>
                </tr></table>
            </div>
            <?php endif; ?>
            <!-- ITEMS TABLE: use previous improved style here -->
            <div class="items-table">
                <table class="table" style="border-collapse: collapse; font-size: 10pt;">
                    <thead><tr style="background: #e9f3fb;">
                        <th style="border: 1px solid #b6d4ef; padding: 8px; text-align: left; font-weight: bold;">Опис</th>
                        <th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Количина</th>
                        <th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Ед. цена</th>
                        <?php if ($has_item_discount): ?>
                            <th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Попуст</th>
                        <?php endif; ?>
                        <th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Вкупно</th>
                    </tr></thead>
                    <tbody>
                        <?php if (!empty($items)): ?>
                            <?php $row_num = 0; foreach ($items as $item): $row_bg = ($row_num % 2 == 0) ? '#fff' : '#f7faff'; ?>
                                <tr style="background: <?php echo $row_bg; ?>;">
                                    <td style="border: 1px solid #b6d4ef; padding: 8px; text-align: left; font-size: 10pt; "><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-size: 10pt; "><?php echo $item['quantity']; ?></td>
                                    <td style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-size: 10pt; "><?php echo number_format($item['unit_price'], 2); ?> <?php echo $currency; ?></td>
                                    <?php if ($has_item_discount): ?>
                                        <td style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-size: 10pt; ">
                                            <?php if (!empty($item['discount_rate']) && $item['discount_rate'] > 0): ?>
                                                <?php echo number_format($item['discount_rate'], 2); ?>% (-<?php echo number_format($item['discount_amount'], 2); ?> <?php echo $currency; ?>)
                                            <?php else: ?>-
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    <td style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-size: 10pt; "><?php echo number_format($item['total_price'], 2); ?> <?php echo $currency; ?></td>
                                </tr>
                            <?php $row_num++; endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="<?php echo $has_item_discount ? 5 : 4; ?>" style="border: 1px solid #b6d4ef; padding: 15px; text-align: center; color: #999; font-style: italic;">Нема додадени ставки во оваа фактура</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- VAT/DISCOUNT ROW -->
            <table width="100%" style="margin-bottom: 8px; padding-bottom: 10px;"><tr>
                <td style="font-size: 10pt; text-align: left;"><strong>ДДВ обврзник:</strong> <?php echo isset($invoice['is_vat_obvrznik']) ? ($invoice['is_vat_obvrznik'] ? 'Да' : 'Не') : ''; ?></td>
                <td style="font-size: 10pt; text-align: right;"><strong>Стапка на ДДВ (%):</strong> <?php echo htmlspecialchars($invoice['tax_rate']); ?>%</td>
            </tr></table>
            <!-- TOTALS SECTION: match PDF layout and spacing -->
            <div style="border-top: 2px solid #dee2e6; padding-top: 0; margin-top: 0;">
                <table width="100%"><tr><td width="40%"></td><td width="60%">
                    <table width="100%" style="font-size: 10pt; margin-top: 0;">
                        <tr><td colspan="2" style="height: 10px; border: none;"></td></tr>
                        <tr><td style="color: #333;">Вкупен износ без данок:</td>
                            <td style="text-align: right; color: #333;"> <?php echo number_format($invoice['subtotal'], 2); ?> <?php echo $currency; ?> </td></tr>
                        <?php if (!empty($invoice['global_discount_rate']) && $invoice['global_discount_rate'] > 0): ?>
                            <tr><td style="color: #333;">Глобална попуст (<?php echo number_format($invoice['global_discount_rate'], 2); ?>%):</td>
                                <td style="text-align: right; color: #333;">-<?php echo number_format($invoice['global_discount_amount'], 2); ?> <?php echo $currency; ?></td></tr>
                        <?php endif; ?>
                        <?php if ($invoice['tax_rate'] > 0): ?>
                            <tr><td style="color: #333;">ДДВ (<?php echo htmlspecialchars($invoice['tax_rate']); ?>%):</td>
                                <td style="text-align: right; color: #333;"> <?php echo number_format($invoice['tax_amount'], 2); ?> <?php echo $currency; ?> </td></tr>
                        <?php endif; ?>
                        <tr><td style="font-size: 10pt; font-weight: normal; color: #333;">Вкупен износ со данок:</td>
                            <td style="text-align: right; font-size: 11pt; font-weight: bold; color: #333;"> <?php echo number_format($invoice['total_amount'], 2); ?> <?php echo $currency; ?> </td></tr>
                    </table>
                </td></tr></table>
            </div>
            <!-- NOTES AND SIGNATURES: match PDF -->
            <?php if (!empty($invoice['notes'])): ?>
                <div style="margin-top: 25px; padding: 12px; background: #fff3cd; border-radius: 6px;">
                    <div style="font-size: 10pt; font-weight: bold; color: #856404; margin-bottom: 5px;">Забелешки:</div>
                    <div style="font-size: 10pt; color: #856404; line-height: 1.4;"> <?php echo nl2br(htmlspecialchars($invoice['notes'])); ?> </div>
                </div>
            <?php endif; ?>
            
            <!-- LEGAL FOOTER -->
            <?php if (!empty($company['legal_footer'])): ?>
            <div style="margin-top: 40px; padding: 15px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">
                <div style="font-size: 9pt; color: #666; text-align: center; line-height: 1.4;">
                    <?php echo htmlspecialchars($company['legal_footer']); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 40px;">
                <table width="100%" style="font-size: 11pt;">
                    <tr>
                        <td width="45%" style="text-align: left; vertical-align: bottom;">_________________________<br><span style="font-size:10pt;">Потпис на испраќач</span></td>
                        <td width="10%"></td>
                        <td width="45%" style="text-align: right; vertical-align: bottom;">_________________________<br><span style="font-size:10pt;">Потпис на примач</span></td>
                    </tr>
                </table>
                <div style="margin-top: 30px; text-align: center; font-size: 11pt;">_________________________<br><span style="font-size:10pt;">Датум</span></div>
            </div>

            <div class="no-print mt-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Ажурирај статус на фактура</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row align-items-end">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Статус</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?php echo $invoice['status'] === 'draft' ? 'selected' : ''; ?>>Нацрт</option>
                                    <option value="sent" <?php echo $invoice['status'] === 'sent' ? 'selected' : ''; ?>>Испратена</option>
                                    <option value="paid" <?php echo $invoice['status'] === 'paid' ? 'selected' : ''; ?>>Платена</option>
                                    <option value="overdue" <?php echo $invoice['status'] === 'overdue' ? 'selected' : ''; ?>>Задоцнета</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" name="update_status" class="btn btn-primary">Ажурирај</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>

<?php include 'includes/footer.php'; ?> 