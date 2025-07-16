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

// Get company settings
$company = getCompanySettings($pdo);

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offer #<?php echo $offer['offer_number']; ?> - Invoicing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .offer-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .offer-header {
            border-bottom: 2px solid #28a745;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info {
            text-align: right;
        }
        .client-info {
            margin-bottom: 30px;
        }
        .offer-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .items-table {
            margin-bottom: 30px;
        }
        .total-section {
            border-top: 2px solid #dee2e6;
            padding-top: 20px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .offer-container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="no-print">
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
                            <a class="nav-link active" href="offers.php">Понуди</a>
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
    </div>

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
                                <img src="<?php echo htmlspecialchars($offer['client_logo_path']); ?>" style="width:70px; height:auto; margin-bottom: 8px; border-radius: 6px; display:block;" />
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
<td style="border: 1px solid #b6d4ef; padding: 8px; text-align: left; font-size: 10pt; "><?php echo htmlspecialchars($item['description']); ?></td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 