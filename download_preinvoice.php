<?php
// Prevent any output before PDF generation
ob_start();
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/company_helper.php';

if (!isset($_GET['id'])) {
    header('Location: preinvoices.php');
    exit;
}

$preinvoice_id = $_GET['id'];

// Get pre-invoice details with client information
$stmt = $pdo->prepare("
    SELECT pf.*, c.name as client_name, c.company_name as client_company_name, c.logo_path as client_logo_path, c.email as client_email, c.phone as client_phone, c.address as client_address, c.tax_number as client_tax_number, c.website as client_website, c.notes as client_notes
    FROM pre_invoices pf
    LEFT JOIN clients c ON pf.client_id = c.id
    WHERE pf.id = ?
");
$stmt->execute([$preinvoice_id]);
$preinvoice = $stmt->fetch();

if (!$preinvoice) {
    header('Location: preinvoices.php');
    exit;
}

// Get pre-invoice items
$stmt = $pdo->prepare("SELECT * FROM pre_invoice_items WHERE preinvoice_id = ?");
$stmt->execute([$preinvoice_id]);
$items = $stmt->fetchAll();

// Get company settings
$company = getCompanySettings($pdo);
$currency = $company['currency'] ?? 'ден';

require_once('tcpdf/tcpdf.php');

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Invoicing System');
$pdf->SetAuthor($company['company_name']);
$pdf->SetTitle('Про Фактура #' . $preinvoice['preinvoice_number']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);

$html = '<div style="font-family: dejavusans, helvetica, sans-serif; font-size: 10pt; color: #222;">';
// Header title and number
$html .= '<div style="margin-bottom: 8px;">';
$html .= '<span style="font-size: 22pt; font-weight: bold; color: #007bff;">ПРО ФАКТУРА</span><br>';
$html .= '<span style="font-size: 13pt; font-weight: bold; color: #333;">#' . htmlspecialchars($preinvoice['preinvoice_number']) . '</span>';
$html .= '</div>';
// Header
$html .= '<div style="max-width: 800px; margin: 0 auto; background: #fff; padding: 0px 30px 30px 30px; font-size: 10pt;">';
$html .= '<table width="100%"><tr>';
// Left: Client logo and info
$html .= '<td width="50%" style="vertical-align: top; padding-right: 10px;">';
if ($preinvoice['client_logo_path']) {
    $client_logo_path = $_SERVER['DOCUMENT_ROOT'] . '/invoices_and_offers/' . ltrim($preinvoice['client_logo_path'], '/');
    $client_logo_path = str_replace(['\\', '//'], '/', $client_logo_path);
    if (file_exists($client_logo_path)) {
        $html .= '<img src="' . $client_logo_path . '" style="width:70px; height:auto; margin-bottom: 8px; border-radius: 6px; display:block;">';
    }
}
if ($preinvoice['client_company_name']) {
    $html .= '<div style="font-size: 12pt; font-weight: bold; color: #333; margin-bottom: 2px;">' . htmlspecialchars($preinvoice['client_company_name']) . '</div>';
}
if ($preinvoice['client_address']) {
    $html .= '<div style="font-size: 9pt; color: #666; margin: 0px;">' . nl2br(htmlspecialchars($preinvoice['client_address'])) . '</div>';
}
if ($preinvoice['client_name']) {
    $html .= '<div style="font-size: 9pt; color: #666; margin: 0px;">Контакт: ' . htmlspecialchars($preinvoice['client_name']) . '</div>';
}
if ($preinvoice['client_email']) {
    $html .= '<div style="font-size: 9pt; color: #666; margin: 0px;">Е-пошта: ' . htmlspecialchars($preinvoice['client_email']) . '</div>';
}
if ($preinvoice['client_phone']) {
    $html .= '<div style="font-size: 9pt; color: #666; margin: 0px;">Телефон: ' . htmlspecialchars($preinvoice['client_phone']) . '</div>';
}
if ($preinvoice['client_tax_number']) {
    $html .= '<div style="font-size: 9pt; color: #666; margin: 0px;"><strong>Даночен број:</strong> ' . htmlspecialchars($preinvoice['client_tax_number']) . '</div>';
}
if ($preinvoice['client_website']) {
    $html .= '<div style="font-size: 9pt; color: #666; margin: 0px;">Веб-страна: ' . htmlspecialchars($preinvoice['client_website']) . '</div>';
}
if ($preinvoice['client_notes']) {
    $html .= '<div style="color: #888; font-size: 9pt; margin-bottom: 1px;">Забелешки: ' . nl2br(htmlspecialchars($preinvoice['client_notes'])) . '</div>';
}
$html .= '</td>';
// Right: Company logo and info
$html .= '<td width="50%" style="text-align: right; vertical-align: top; padding-left: 10px;">';
$logo_path = null;
$upload_dir = __DIR__ . '/uploads/';
$logo_files = glob($upload_dir . 'company_logo*.png');
if ($logo_files && count($logo_files) > 0) {
    $logo_path = $logo_files[0];
}
if ($logo_path && file_exists($logo_path)) {
    $html .= '<img src="' . $logo_path . '" style="width:70px; height:auto; margin-bottom: 8px; border-radius: 6px; display:block;">';
}
$html .= '<div style="font-size: 12pt; font-weight: bold; color: #333; margin-bottom: 2px;">' . htmlspecialchars($company['company_name']) . '</div>';
if ($company['address']) {
    $html .= '<div style="font-size: 9pt; color: #666; margin: 0px;">' . nl2br(htmlspecialchars($company['address'])) . '</div>';
}
if ($company['phone']) {
    $html .= '<div style="font-size: 9pt; color: #666; margin: 0px;">Телефон: ' . htmlspecialchars($company['phone']) . '</div>';
}
if ($company['email']) {
    $html .= '<div style="font-size: 9pt; color: #666; margin: 0px;">Е-пошта: ' . htmlspecialchars($company['email']) . '</div>';
}
if (!empty($company['tax_number'])) {
    $html .= '<div style="font-size: 9pt; color: #666; margin: 0px;"><strong>Даночен број:</strong> ' . htmlspecialchars($company['tax_number']) . '</div>';
}
if (!empty($company['bank_account'])) {
    $html .= '<div style="font-size: 9pt; color: #666; margin: 0px;"><strong>Жиро сметка:</strong> ' . htmlspecialchars($company['bank_account']) . '</div>';
}
$html .= '</td>';
$html .= '</tr></table>';
$html .= '</div>';
// BLUE LINE (matching view)
$html .= '<div style="max-width: 900px; margin: 0 auto; border-bottom: 2px solid #007bff; margin-bottom: 18px; margin-top: 12px;"></div>';
// DATES ROW (matching view)
$html .= '<div style="max-width: 900px; margin: 0 auto; margin-bottom: 0; padding-bottom: 0;">';
$html .= '<table width="100%" style="margin-bottom: 0; padding-bottom: 0;"><tr>';
$html .= '<td width="50%" style="font-size: 10pt;"><strong>Датум на издавање:</strong> ' . date('d.m.Y', strtotime($preinvoice['issue_date'])) . '</td>';
$html .= '<td width="50%" style="font-size: 10pt;"><strong>Датум на доспевање:</strong> ' . date('d.m.Y', strtotime($preinvoice['due_date'])) . '</td>';
$html .= '</tr></table>';
$html .= '</div>';

// LEGAL INFORMATION ROW (matching view)
$html .= '<div style="max-width: 900px; margin: 0 auto; margin-bottom: 15px; padding-bottom: 10px;">';
$html .= '<table width="100%" style="margin-bottom: 0; padding-bottom: 0;"><tr>';
$html .= '<td width="50%" style="font-size: 10pt;"><strong>Датум на испорака:</strong> ' . (!empty($preinvoice['supply_date']) ? date('d.m.Y', strtotime($preinvoice['supply_date'])) : date('d.m.Y', strtotime($preinvoice['issue_date']))) . '</td>';
$html .= '<td width="50%" style="font-size: 10pt;"><strong>Начин на плаќање:</strong> ';
$payment_methods = [
    'bank_transfer' => 'Банкарски трансфер',
    'cash' => 'Готовина',
    'card' => 'Картичка',
    'check' => 'Чек',
    'other' => 'Друго'
];
$html .= $payment_methods[$preinvoice['payment_method'] ?? 'bank_transfer'] ?? 'Банкарски трансфер';
$html .= '</td>';
$html .= '</tr></table>';
$html .= '</div>';

// AUTHORIZED PERSON ROW (matching view)
if (!empty($preinvoice['authorized_person'])) {
    $html .= '<div style="max-width: 900px; margin: 0 auto; margin-bottom: 15px; padding-bottom: 10px;">';
    $html .= '<table width="100%" style="margin-bottom: 0; padding-bottom: 0;"><tr>';
    $html .= '<td width="50%" style="font-size: 10pt;"><strong>Овластено лице:</strong> ' . htmlspecialchars($preinvoice['authorized_person']) . '</td>';
    $html .= '<td width="50%" style="font-size: 10pt;">';
    if (!empty($preinvoice['fiscal_receipt_number'])) {
        $html .= '<strong>Фискален број:</strong> ' . htmlspecialchars($preinvoice['fiscal_receipt_number']);
    }
    $html .= '</td>';
    $html .= '</tr></table>';
    $html .= '</div>';
}
// Items table
$html .= '<div class="items-table">';
$html .= '<table border="1" cellpadding="4" style="border-collapse: collapse; font-size: 10pt; width:100%;">';
$html .= '<thead><tr style="background: #e9f3fb;">';
$html .= '<th style="border: 1px solid #b6d4ef; text-align: left; font-weight: bold;">Опис</th>';
$html .= '<th style="border: 1px solid #b6d4ef; text-align: center; font-weight: bold;">Количина</th>';
$html .= '<th style="border: 1px solid #b6d4ef; text-align: center; font-weight: bold;">Ед. цена</th>';
$has_item_discount = false;
foreach ($items as $item) { if (!empty($item['discount_rate']) && $item['discount_rate'] > 0) { $has_item_discount = true; break; } }
if ($has_item_discount) {
    $html .= '<th style="border: 1px solid #b6d4ef; text-align: center; font-weight: bold;">Попуст</th>';
}
$html .= '<th style="border: 1px solid #b6d4ef; text-align: center; font-weight: bold;">Вкупно</th>';
$html .= '</tr></thead>';
$html .= '<tbody>';
if (!empty($items)) {
    foreach ($items as $item) {
        $html .= '<tr>';
        $html .= '<td style="border: 1px solid #b6d4ef; text-align: left; font-size: 10pt;">' . htmlspecialchars($item['description']) . '</td>';
        $html .= '<td style="border: 1px solid #b6d4ef; text-align: center; font-size: 10pt;">' . $item['quantity'] . '</td>';
        $html .= '<td style="border: 1px solid #b6d4ef; text-align: center; font-size: 10pt;">' . number_format($item['unit_price'], 2) . ' ' . $currency . '</td>';
        if ($has_item_discount) {
            if (!empty($item['discount_rate']) && $item['discount_rate'] > 0) {
                $html .= '<td style="border: 1px solid #b6d4ef; text-align: center; font-size: 10pt;">' . number_format($item['discount_rate'], 2) . '% (-' . number_format($item['discount_amount'], 2) . ' ' . $currency . ')</td>';
            } else {
                $html .= '<td style="border: 1px solid #b6d4ef; text-align: center; font-size: 10pt;">-</td>';
            }
        }
        $html .= '<td style="border: 1px solid #b6d4ef; text-align: center; font-size: 10pt;">' . number_format($item['total_price'], 2) . ' ' . $currency . '</td>';
        $html .= '</tr>';
    }
} else {
    $colspan = $has_item_discount ? 5 : 4;
    $html .= '<tr><td colspan="' . $colspan . '" style="border: 1px solid #b6d4ef; padding: 15px; text-align: center; color: #999; font-style: italic;">Нема додадени ставки во оваа Про Фактура</td></tr>';
}
$html .= '</tbody></table>';
$html .= '</div>';
// VAT/DISCOUNT ROW
$html .= '<table width="100%" style="margin-bottom: 8px; padding-bottom: 10px;"><tr>';
$html .= '<td style="font-size: 10pt; text-align: left;"><strong>ДДВ обврзник:</strong> ' . (isset($preinvoice['is_vat_obvrznik']) ? ($preinvoice['is_vat_obvrznik'] ? 'Да' : 'Не') : '') . '</td>';
$html .= '<td style="font-size: 10pt; text-align: right;"><strong>Стапка на ДДВ (%):</strong> ' . htmlspecialchars($preinvoice['tax_rate']) . '%</td>';
$html .= '</tr></table>';
// TOTALS SECTION
$html .= '<table width="100%"><tr><td width="40%"></td><td width="60%">';
$html .= '<table width="100%" style="font-size: 10pt; margin-top: 0;">';
$html .= '<tr><td colspan="2" style="height: 10px; border: none;"></td></tr>';
$html .= '<tr><td style="color: #333;">Вкупен износ без данок:</td>';
$html .= '<td style="text-align: right; color: #333;">' . number_format($preinvoice['subtotal'], 2) . ' ' . $currency . '</td></tr>';
if (!empty($preinvoice['global_discount_rate']) && $preinvoice['global_discount_rate'] > 0) {
    $html .= '<tr><td style="color: #333;">Глобална попуст (' . number_format($preinvoice['global_discount_rate'], 2) . '%):</td>';
    $html .= '<td style="text-align: right; color: #333;">-' . number_format($preinvoice['global_discount_amount'], 2) . ' ' . $currency . '</td></tr>';
}
if ($preinvoice['tax_rate'] > 0) {
    $html .= '<tr><td style="color: #333;">ДДВ (' . htmlspecialchars($preinvoice['tax_rate']) . '%):</td>';
    $html .= '<td style="text-align: right; color: #333;">' . number_format($preinvoice['tax_amount'], 2) . ' ' . $currency . '</td></tr>';
}
$html .= '<tr><td style="font-size: 10pt; font-weight: normal; color: #333;">Вкупен износ со данок:</td>';
$html .= '<td style="text-align: right; font-size: 11pt; font-weight: bold; color: #333;">' . number_format($preinvoice['total_amount'], 2) . ' ' . $currency . '</td></tr>';
if ($preinvoice['payment_type'] === 'advance') {
    $html .= '<tr><td style="color: #333; font-weight: bold;">Износ на Аванс:</td>';
    $html .= '<td style="text-align: right; color: #333; font-weight: bold;">-' . number_format($preinvoice['advance_amount'], 2) . ' ' . $currency . '</td></tr>';
    $html .= '<tr><td style="color: #333; font-weight: bold;">Останува за финална фактура:</td>';
    $html .= '<td style="text-align: right; color: #333; font-weight: bold;">' . number_format($preinvoice['total_amount'] - $preinvoice['advance_amount'], 2) . ' ' . $currency . '</td></tr>';
}
$html .= '</table>';
$html .= '</td></tr></table>';
// Notes
if (!empty($preinvoice['notes'])) {
    $html .= '<div style="margin-top: 25px; padding: 12px; background: #fff3cd; border-radius: 6px;">';
    $html .= '<div style="font-size: 10pt; font-weight: bold; color: #856404; margin-bottom: 5px;">Забелешки:</div>';
    $html .= '<div style="font-size: 10pt; color: #856404; line-height: 1.4;">' . nl2br(htmlspecialchars($preinvoice['notes'])) . '</div>';
    $html .= '</div>';
}
// Signatures
$html .= '<div style="margin-top: 60px;">';
$html .= '<table width="100%" style="font-size: 11pt;"><tr>';
$html .= '<td width="45%" style="text-align: left; vertical-align: bottom;">_________________________<br><span style="font-size:10pt;">Потпис на испраќач</span></td>';
$html .= '<td width="10%"></td>';
$html .= '<td width="45%" style="text-align: right; vertical-align: bottom;">_________________________<br><span style="font-size:10pt;">Потпис на примач</span></td>';
$html .= '</tr></table>';
$html .= '<div style="margin-top: 30px; text-align: center; font-size: 11pt;">_________________________<br><span style="font-size:10pt;">Датум</span></div>';
$html .= '</div>';
$html .= '</div>';

$pdf->writeHTML($html, true, false, true, false, '');
if (ob_get_length()) ob_clean();
$pdf->Output('pro-faktura-' . $preinvoice['preinvoice_number'] . '.pdf', 'D');
exit; 