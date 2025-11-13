<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/company_helper.php';

if (!isset($_GET['id'])) {
    header('Location: offers.php');
    exit;
}

$offer_id = $_GET['id'];

// Get offer details with client information
$stmt = $pdo->prepare("
    SELECT o.*, c.name as client_name, c.company_name as client_company_name, c.logo_path as client_logo_path, c.email as client_email, c.phone as client_phone, c.address as client_address, c.tax_number as client_tax_number, c.website as client_website, c.notes as client_notes
    FROM offers o 
    LEFT JOIN clients c ON o.client_id = c.id 
    WHERE o.id = ?
");
$stmt->execute([$offer_id]);
$offer = $stmt->fetch();

if (!$offer) {
    header('Location: offers.php');
    exit;
}

// Get offer items
$stmt = $pdo->prepare("SELECT * FROM offer_items WHERE offer_id = ?");
$stmt->execute([$offer_id]);
$items = $stmt->fetchAll();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE offers SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $offer_id]);
    header("Location: view_offer.php?id=$offer_id");
    exit;
}

// Check if an invoice already exists for this offer
$invoice_id_for_offer = null;
$stmt = $pdo->prepare("SELECT id FROM invoices WHERE offer_id = ?");
$stmt->execute([$offer_id]);
$invoice_for_offer = $stmt->fetch();
if ($invoice_for_offer) {
    $invoice_id_for_offer = $invoice_for_offer['id'];
}

// Handle generate invoice from offer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_invoice_from_offer'])) {
    // Only allow if no invoice exists for this offer
    if (!$invoice_id_for_offer && $offer['status'] === 'accepted') {
        try {
            $pdo->beginTransaction();
            // Generate automatic invoice number
            $current_year = date('Y');
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE YEAR(created_at) = ?");
            $stmt->execute([$current_year]);
            $invoice_count = $stmt->fetchColumn();
            $invoice_number = $current_year . '-' . str_pad($invoice_count + 1, 3, '0', STR_PAD_LEFT);

            // Set default due date (30 days from issue_date)
            $issue_date = $offer['issue_date'];
            $due_date = date('Y-m-d', strtotime($issue_date . ' +30 days'));

            // Insert invoice
            $stmt = $pdo->prepare("INSERT INTO invoices (client_id, offer_id, invoice_number, issue_date, due_date, subtotal, tax_rate, tax_amount, total_amount, notes, is_vat_obvrznik, global_discount_rate, global_discount_amount, is_global_discount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $offer['client_id'],
                $offer_id,
                $invoice_number,
                $issue_date,
                $due_date,
                $offer['subtotal'],
                $offer['tax_rate'],
                $offer['tax_amount'],
                $offer['total_amount'],
                $offer['notes'],
                $offer['is_vat_obvrznik'] ?? 0,
                $offer['global_discount_rate'] ?? 0,
                $offer['global_discount_amount'] ?? 0,
                $offer['is_global_discount'] ?? 0,
                'draft'
            ]);
            $new_invoice_id = $pdo->lastInsertId();

            // Copy offer items to invoice items
            $stmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, service_id, item_name, description, quantity, unit_price, total_price, discount_rate, discount_amount, is_discount, final_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($items as $item) {
                $stmt->execute([
                    $new_invoice_id,
                    $item['service_id'],
                    $item['item_name'],
                    $item['description'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['total_price'],
                    $item['discount_rate'] ?? 0,
                    $item['discount_amount'] ?? 0,
                    $item['is_discount'] ?? 0,
                    $item['final_price'] ?? $item['total_price']
                ]);
            }
            $pdo->commit();
            header("Location: view_invoice.php?id=$new_invoice_id");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Грешка при генерирање на фактура: " . $e->getMessage();
        }
    }
}

// Handle generate pre-invoice from offer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_preinvoice_from_offer'])) {
    // Only allow if offer is accepted and has advance payment
    if ($offer['status'] === 'accepted' && $offer['payment_type'] === 'advance') {
        try {
            $pdo->beginTransaction();
            // Generate automatic pre-invoice number
            $current_year = date('Y');
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pre_invoices WHERE YEAR(created_at) = ?");
            $stmt->execute([$current_year]);
            $preinvoice_count = $stmt->fetchColumn();
            $preinvoice_number = $current_year . '-PF-' . str_pad($preinvoice_count + 1, 3, '0', STR_PAD_LEFT);

            // Set default due date (30 days from issue_date)
            $issue_date = $offer['issue_date'];
            $due_date = date('Y-m-d', strtotime($issue_date . ' +30 days'));

            // Insert pre-invoice
            $stmt = $pdo->prepare("INSERT INTO pre_invoices (client_id, preinvoice_number, issue_date, due_date, subtotal, tax_rate, tax_amount, total_amount, payment_type, advance_amount, remaining_amount, notes, is_vat_obvrznik, global_discount_rate, global_discount_amount, is_global_discount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $offer['client_id'],
                $preinvoice_number,
                $issue_date,
                $due_date,
                $offer['subtotal'],
                $offer['tax_rate'],
                $offer['tax_amount'],
                $offer['total_amount'],
                'advance',
                $offer['advance_amount'],
                $offer['remaining_amount'],
                $offer['notes'],
                $offer['is_vat_obvrznik'] ?? 0,
                $offer['global_discount_rate'] ?? 0,
                $offer['global_discount_amount'] ?? 0,
                $offer['is_global_discount'] ?? 0,
                'draft'
            ]);
            $new_preinvoice_id = $pdo->lastInsertId();

            // Copy offer items to pre-invoice items
            $stmt = $pdo->prepare("INSERT INTO pre_invoice_items (preinvoice_id, service_id, item_name, description, quantity, unit_price, total_price, discount_rate, discount_amount, is_discount, final_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($items as $item) {
                $stmt->execute([
                    $new_preinvoice_id,
                    $item['service_id'],
                    $item['item_name'],
                    $item['description'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['total_price'],
                    $item['discount_rate'] ?? 0,
                    $item['discount_amount'] ?? 0,
                    $item['is_discount'] ?? 0,
                    $item['final_price'] ?? $item['total_price']
                ]);
            }
            $pdo->commit();
            header("Location: view_preinvoice.php?id=$new_preinvoice_id");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Грешка при генерирање на Про Фактура: " . $e->getMessage();
        }
    }
}

// Get company settings
$company = getCompanySettings($pdo);
$page_title = 'Понуда';

$logo_path = '/uploads/company_logo.png';
$upload_dir = __DIR__ . '/uploads/';
$logo_files = glob($upload_dir . 'company_logo*.png');
if ($logo_files && count($logo_files) > 0) {
    $logo_path = '/uploads/' . basename($logo_files[0]);
}

$currency = $company['currency'] ?? 'ден';
$has_item_discount = false;
foreach ($items as $item) {
    if (!empty($item['discount_rate']) && $item['discount_rate'] > 0) {
        $has_item_discount = true;
        break;
    }
}

// Get company settings
$company = getCompanySettings($pdo);
$page_title = 'Понуда';
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-4">
        <div class="no-print mb-3">
            <a href="offers.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Назад кон понуди
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Печати
            </button>
            <a href="download_offer.php?id=<?php echo $offer_id; ?>" class="btn btn-success">
                <i class="bi bi-download"></i> Преземи PDF
            </a>
        </div>

        <div class="offer-container">
            <!-- HEADER: Centered title and number at the top -->
            <div style="max-width:900px; margin:0 auto; margin-bottom: 8px;">
                <span style="font-size: 22pt; font-weight: bold; color: #28a745;">ПОНУДА</span><br>
                <span style="font-size: 13pt; font-weight: bold; color: #333;">#<?php echo htmlspecialchars($offer['offer_number']); ?></span>
            </div>
            <!-- HEADER: Table with logo left/right and info -->
            <div style="max-width: 900px; margin: 0 auto; background: #fff; padding: 0px 30px 0px 30px; font-size: 10pt;">
                <table width="100%" style="border-collapse:collapse;">
                    <tr>
                        <!-- Client logo left -->
                        <td width="15%" style="vertical-align: top; text-align: left; padding-right: 10px;">
                            <?php if ($offer['client_logo_path']): ?>
                                <img src="<?php echo htmlspecialchars($offer['client_logo_path']); ?>" class="client-logo" style="margin-bottom: 8px; border-radius: 6px; display:block;" />
                            <?php endif; ?>
                        </td>
                        <!-- Empty space -->
                        <td width="70%"></td>
                        <!-- Company logo right -->
                        <td width="15%" style="vertical-align: top; text-align: right; padding-left: 10px;">
                            <?php if ($company['logo_path']): ?>
                                <img src="<?php echo htmlspecialchars($company['logo_path']); ?>" class="company-logo" style="margin-bottom: 8px; border-radius: 6px; display:block;" />
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
                        <?php if ($offer['client_company_name']): ?>
                            <div style="font-size: 12pt; font-weight: bold; color: #333; margin-bottom: 2px;"> <?php echo htmlspecialchars($offer['client_company_name']); ?> </div>
                        <?php endif; ?>
                        <?php if ($offer['client_address']): ?>
                            <div style="font-size: 9pt; color: #666; margin: 0px;"> <?php echo nl2br(htmlspecialchars($offer['client_address'])); ?> </div>
                        <?php endif; ?>
                        <?php if ($offer['client_name']): ?>
                            <div style="font-size: 9pt; color: #666; margin: 0px;"> Контакт: <?php echo htmlspecialchars($offer['client_name']); ?> </div>
                        <?php endif; ?>
                        <?php if ($offer['client_email']): ?>
                            <div style="font-size: 9pt; color: #666; margin: 0px;"> Е-пошта: <?php echo htmlspecialchars($offer['client_email']); ?> </div>
                        <?php endif; ?>
                        <?php if ($offer['client_phone']): ?>
                            <div style="font-size: 9pt; color: #666; margin: 0px;"> Телефон: <?php echo htmlspecialchars($offer['client_phone']); ?> </div>
                        <?php endif; ?>
                        <?php if ($offer['client_tax_number']): ?>
                            <div style="font-size: 9pt; color: #666; margin: 0px;"><strong>Даночен број:</strong> <?php echo htmlspecialchars($offer['client_tax_number']); ?> </div>
                        <?php endif; ?>
                        <?php if ($offer['client_website']): ?>
                            <div style="font-size: 9pt; color: #666; margin: 0px;"> Веб-страна: <?php echo htmlspecialchars($offer['client_website']); ?> </div>
                        <?php endif; ?>
                        <?php if ($offer['client_notes']): ?>
                            <div style="color: #888; font-size: 9pt; margin-bottom: 1px;"> Забелешки: <?php echo nl2br(htmlspecialchars($offer['client_notes'])); ?> </div>
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
            <div style="max-width:900px; margin:0 auto; border-bottom:2px solid #28a745; margin-bottom:18px; margin-top:12px;"></div>
            <!-- DATES ROW -->
            <div style="margin-bottom: 0; padding-bottom: 0;">
                <table width="100%" style="margin-bottom:0; padding-bottom:0;"><tr>
                    <td width="50%" style="font-size: 10pt;"><strong>Датум на издавање:</strong> <?php echo date('d.m.Y', strtotime($offer['issue_date'])); ?></td>
                    <td width="50%" style="font-size: 10pt;"><strong>Важи до:</strong> <?php echo date('d.m.Y', strtotime($offer['valid_until'])); ?></td>
                </tr></table>
            </div>
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
<tr><td colspan="<?php echo $has_item_discount ? 5 : 4; ?>" style="border: 1px solid #b6d4ef; padding: 15px; text-align: center; color: #999; font-style: italic;">Нема додадени ставки во оваа понуда</td></tr>
<?php endif; ?>
</tbody>
</table>
            </div>

            <!-- VAT/DISCOUNT ROW -->
            <table width="100%" style="margin-bottom: 8px; padding-bottom: 10px;"><tr>
                <td style="font-size: 10pt; text-align: left;"><strong>ДДВ обврзник:</strong> <?php echo isset($offer['is_vat_obvrznik']) ? ($offer['is_vat_obvrznik'] ? 'Да' : 'Не') : ''; ?></td>
                <td style="font-size: 10pt; text-align: right;"><strong>Стапка на ДДВ (%):</strong> <?php echo htmlspecialchars($offer['tax_rate']); ?>%</td>
            </tr></table>
            <!-- TOTALS SECTION: match PDF layout and spacing -->
            <div style="border-top: 2px solid #dee2e6; padding-top: 0; margin-top: 0;">
                <table width="100%"><tr><td width="40%"></td><td width="60%">
                    <table width="100%" style="font-size: 10pt; margin-top: 0;">
                        <tr><td colspan="2" style="height: 10px; border: none;"></td></tr>
                        <tr><td style="color: #333;">Вкупен износ без данок:</td>
                            <td style="text-align: right; color: #333;"> <?php echo number_format($offer['subtotal'], 2); ?> <?php echo $currency; ?> </td></tr>
                        <?php if (!empty($offer['global_discount_rate']) && $offer['global_discount_rate'] > 0): ?>
                            <tr><td style="color: #333;">Глобална попуст (<?php echo number_format($offer['global_discount_rate'], 2); ?>%):</td>
                                <td style="text-align: right; color: #333;">-<?php echo number_format($offer['global_discount_amount'], 2); ?> <?php echo $currency; ?></td></tr>
                        <?php endif; ?>
                        <?php if ($offer['tax_rate'] > 0): ?>
                            <tr><td style="color: #333;">ДДВ (<?php echo htmlspecialchars($offer['tax_rate']); ?>%):</td>
                                <td style="text-align: right; color: #333;"> <?php echo number_format($offer['tax_amount'], 2); ?> <?php echo $currency; ?> </td></tr>
                        <?php endif; ?>
                        <tr><td style="font-size: 10pt; font-weight: normal; color: #333;">Вкупен износ со данок:</td>
                            <td style="text-align: right; font-size: 11pt; font-weight: bold; color: #333;"> <?php echo number_format($offer['total_amount'], 2); ?> <?php echo $currency; ?> </td></tr>
                        <?php if ($offer['payment_type'] === 'advance'): ?>
                            <tr><td style="color: #333; font-weight: bold;">Износ на Аванс:</td>
                                <td style="text-align: right; color: #333; font-weight: bold;">-<?php echo number_format($offer['advance_amount'], 2); ?> <?php echo $currency; ?></td></tr>
                            <tr><td style="color: #333; font-weight: bold;">Останува за финална фактура:</td>
                                <td style="text-align: right; color: #333; font-weight: bold;"> <?php echo number_format($offer['remaining_amount'], 2); ?> <?php echo $currency; ?></td></tr>
                        <?php endif; ?>
                    </table>
                </td></tr></table>
            </div>
            <!-- NOTES AND SIGNATURES: match PDF -->
            <?php if (!empty($offer['notes'])): ?>
                <div style="margin-top: 25px; padding: 12px; background: #fff3cd; border-radius: 6px;">
                    <div style="font-size: 10pt; font-weight: bold; color: #856404; margin-bottom: 5px;">Забелешки:</div>
                    <div style="font-size: 10pt; color: #856404; line-height: 1.4;"> <?php echo nl2br(htmlspecialchars($offer['notes'])); ?> </div>
                </div>
            <?php endif; ?>
            <div style="margin-top: 60px;">
                <table width="100%" style="font-size: 11pt;">
                    <tr>
                        <td width="45%" style="text-align: left; vertical-align: bottom;">_________________________<br><span style="font-size:10pt;">Потпис на испраќач</span></td>
                        <td width="10%"></td>
                        <td width="45%" style="text-align: right; vertical-align: bottom;">_________________________<br><span style="font-size:10pt;">Потпис на примач</span></td>
                    </tr>
                </table>
                <div style="margin-top: 30px; text-align: center; font-size: 11pt;">_________________________<br><span style="font-size:10pt;">Датум</span></div>
            </div>
        </div>
    </div>

    <div class="no-print mt-4 d-flex justify-content-center">
        <div class="card" style="max-width: 700px; width: 100%;">
            <div class="card-header">
                <h5>Ажурирај статус на понуда</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row align-items-end">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Статус</label>
                        <select class="form-select" id="status" name="status">
                            <option value="draft" <?php echo $offer['status'] === 'draft' ? 'selected' : ''; ?>>Нацрт</option>
                            <option value="sent" <?php echo $offer['status'] === 'sent' ? 'selected' : ''; ?>>Испратена</option>
                            <option value="accepted" <?php echo $offer['status'] === 'accepted' ? 'selected' : ''; ?>>Прифатена</option>
                            <option value="rejected" <?php echo $offer['status'] === 'rejected' ? 'selected' : ''; ?>>Одбиена</option>
                            <option value="expired" <?php echo $offer['status'] === 'expired' ? 'selected' : ''; ?>>Истечена</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="update_status" class="btn btn-primary">Ажурирај</button>
                    </div>
                </form>
                <?php if ($offer['status'] === 'accepted' && !$invoice_id_for_offer): ?>
                    <form method="POST" class="mt-3">
                        <button type="submit" name="generate_invoice_from_offer" class="btn btn-success">
                            Генерирај фактура од оваа понуда
                        </button>
                    </form>
                <?php elseif ($invoice_id_for_offer): ?>
                    <div class="alert alert-info mt-3">Веќе е генерирана фактура од оваа понуда. <a href="view_invoice.php?id=<?php echo $invoice_id_for_offer; ?>" class="btn btn-link">Види фактура</a></div>
                <?php endif; ?>
                
                <?php if ($offer['status'] === 'accepted'): ?>
                    <form method="POST" class="mt-3">
                        <button type="submit" name="generate_preinvoice_from_offer" class="btn btn-warning">
                            Генерирај Про Фактура
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?> 