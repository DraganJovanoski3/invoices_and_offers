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

// Get company settings
$company = getCompanySettings($pdo);
$currency = $company['currency'] ?? 'MKD';

require_once('tcpdf/tcpdf.php');

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Invoicing System');
$pdf->SetAuthor($company['company_name']);
$pdf->SetTitle('Фактура #' . $invoice['invoice_number']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);

// Calculate subtotal and tax
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['total_price'];
}
$tax_amount = $invoice['total_amount'] - $subtotal;

$html = '<div style="font-family: dejavusans, helvetica, sans-serif; font-size: 10pt; color: #222;">';
// Header title and number
$html .= '<div style="margin-bottom: 8px;">';
$html .= '<span style="font-size: 22pt; font-weight: bold; color: #007bff;">ФАКТУРА</span><br>';
$html .= '<span style="font-size: 13pt; font-weight: bold; color: #333;">#' . htmlspecialchars($invoice['invoice_number']) . '</span>';
$html .= '</div>';
// Header
$html .= '<div style="max-width: 800px; margin: 0 auto; background: #fff; padding: 0px 30px 30px 30px; font-size: 10pt;">';
$html .= '<table width="100%"><tr>';
// Left: Client logo and info
$html .= '<td width="50%" style="vertical-align: top; padding-right: 10px;">';
if ($invoice['client_logo_path']) {
    $client_logo_path = $_SERVER['DOCUMENT_ROOT'] . '/invoices_and_offers/' . ltrim($invoice['client_logo_path'], '/');
    $client_logo_path = str_replace(['\\', '//'], '/', $client_logo_path);
    if (file_exists($client_logo_path)) {
        $html .= '<img src="' . $client_logo_path . '" style="width:70px; height:auto; margin-bottom: 8px; border-radius: 6px; display:block;">';
    }
}
if ($invoice['client_company_name']) {
    $html .= '<div style="font-size: 12pt; font-weight: bold; color: #333; margin-bottom: 2px;">' . htmlspecialchars($invoice['client_company_name']) . '</div>';
}
if ($invoice['client_address']) {
    $html .= '<div style="font-size: 9pt; color: #666; margin: 0px;">' . nl2br(htmlspecialchars($invoice['client_address'])) . '</div>';
}
if ($invoice['client_name']) {
    $html .= '<div style="font-size: 9pt; color: #666; margin: 0px;">Контакт: ' . htmlspecialchars($invoice['client_name']) . '</div>';
}
if ($invoice['client_email']) {
    $html .= '<div style="font-size: 9pt; color: #666; margin: 0px;">Е-пошта: ' . htmlspecialchars($invoice['client_email']) . '</div>';
}
if ($invoice['client_phone']) {
    $html .= '<div style="font-size: 9pt; color: #666; margin: 0px;">Телефон: ' . htmlspecialchars($invoice['client_phone']) . '</div>';
}
if ($invoice['client_tax_number']) {
    $html .= '<div style="font-size: 9pt; color: #666; margin: 0px;"><strong>Даночен број:</strong> ' . htmlspecialchars($invoice['client_tax_number']) . '</div>';
}
if ($invoice['client_website']) {
    $html .= '<div style="font-size: 9pt; color: #666; margin: 0px;">Веб-страна: ' . htmlspecialchars($invoice['client_website']) . '</div>';
}
if ($invoice['client_notes']) {
    $html .= '<div style="color: #888; font-size: 9pt; margin-bottom: 1px;">Забелешки: ' . nl2br(htmlspecialchars($invoice['client_notes'])) . '</div>';
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
// Blue line
$html .= '<div style="border-bottom: 2px solid #007bff; margin: 12px 0 18px 0;"></div>';
// Dates row below header (no spacing)
$html .= '<div style="margin-bottom: 0; padding-bottom: 0;">'
    .'<table width="100%" style="margin-bottom:0; padding-bottom:0;"><tr>'
    .'<td width="50%" style="font-size: 10pt;"><strong>Датум на издавање:</strong> ' . date('d.m.Y', strtotime($invoice['issue_date'])) . '</td>'
    .'<td width="50%" style="font-size: 10pt;"><strong>Датум на доспевање:</strong> ' . date('d.m.Y', strtotime($invoice['due_date'])) . '</td>'
    .'</tr></table>'
    .'</div>';
// Items Table (improved styling)
$has_item_discount = false;
foreach ($items as $item) {
    if (!empty($item['discount_rate']) && $item['discount_rate'] > 0) {
        $has_item_discount = true;
        break;
    }
}
$html .= '<div class="items-table" style="margin-bottom: 30px; margin-top: 0; padding-top: 0;">';
$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; font-size: 10pt;">';
$html .= '<thead><tr style="background: #e9f3fb;">';
$html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: left; font-weight: bold;">Опис</th>';
$html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Количина</th>';
$html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Ед. цена</th>';
if ($has_item_discount) {
    $html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Попуст</th>';
}
$html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Вкупно</th>';
$html .= '</tr></thead><tbody>';
if (!empty($items)) {
    $row_num = 0;
    foreach ($items as $item) {
        $row_bg = ($row_num % 2 == 0) ? '#fff' : '#f7faff';
        $html .= '<tr style="background: ' . $row_bg . ';">';
        $html .= '<td style="border: 1px solid #b6d4ef; padding: 8px; font-size: 10pt; text-align: left;">' . htmlspecialchars($item['description']) . '</td>';
        $html .= '<td style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-size: 10pt;">' . $item['quantity'] . '</td>';
        $html .= '<td style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-size: 10pt;">' . number_format($item['unit_price'], 2) . ' ' . $currency . '</td>';
        if ($has_item_discount) {
            $html .= '<td style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-size: 10pt;">';
            if (!empty($item['discount_rate']) && $item['discount_rate'] > 0) {
                $html .= number_format($item['discount_rate'], 2) . '% (-' . number_format($item['discount_amount'], 2) . ' ' . $currency . ')';
            } else {
                $html .= '-';
            }
            $html .= '</td>';
        }
        $html .= '<td style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-size: 10pt;">' . number_format($item['total_price'], 2) . ' ' . $currency . '</td>';
        $html .= '</tr>';
        $row_num++;
    }
} else {
    $html .= '<tr><td colspan="' . ($has_item_discount ? 5 : 4) . '" style="border: 1px solid #b6d4ef; padding: 15px; text-align: center; color: #999; font-style: italic;">Нема додадени ставки во оваа фактура</td></tr>';
}
$html .= '</tbody></table></div>';
// Inline row for ДДВ обврзник and Стапка на ДДВ (%) below items table
$html .= '<table width="100%" style="margin-bottom: 8px; padding-bottom: 10px;"><tr>';
$html .= '<td style="font-size: 10pt; text-align: left;">' . (isset($invoice['is_vat_obvrznik']) ? ('<strong>ДДВ обврзник:</strong> ' . ($invoice['is_vat_obvrznik'] ? 'Да' : 'Не')) : '') . '</td>';
$html .= '<td style="font-size: 10pt; text-align: right;"><strong>Стапка на ДДВ (%):</strong> ' . htmlspecialchars($invoice['tax_rate']) . '%</td>';
$html .= '</tr></table>';
// Totals
$html .= '<div style="border-top: 2px solid #dee2e6; padding-top: 0; margin-top: 0;">';
$html .= '<table width="100%"><tr><td width="40%"></td><td width="60%">';
$html .= '<table width="100%" style="font-size: 10pt; margin-top: 0;">';
$html .= '<tr><td colspan="2" style="height: 10px; border: none;"></td></tr>';
$html .= '<tr><td style="color: #333;">Вкупен износ без данок:</td>';
$html .= '<td style="text-align: right; color: #333;">' . number_format($invoice['subtotal'], 2) . ' ' . $currency . '</td></tr>';
if (!empty($invoice['global_discount_rate']) && $invoice['global_discount_rate'] > 0) {
    $html .= '<tr><td style="color: #333;">Глобална попуст (' . number_format($invoice['global_discount_rate'], 2) . '%):</td>';
    $html .= '<td style="text-align: right; color: #333;">-' . number_format($invoice['global_discount_amount'], 2) . ' ' . $currency . '</td></tr>';
}
if ($invoice['tax_rate'] > 0) {
    $html .= '<tr><td style="color: #333;">ДДВ (' . htmlspecialchars($invoice['tax_rate']) . '%):</td>';
    $html .= '<td style="text-align: right; color: #333;">' . number_format($invoice['tax_amount'], 2) . ' ' . $currency . '</td></tr>';
}
$html .= '<tr><td style="font-size: 10pt; font-weight: normal; color: #333;">Вкупен износ со данок:</td>';
$html .= '<td style="text-align: right; font-size: 11pt; font-weight: bold; color: #333;">' . number_format($invoice['total_amount'], 2) . ' ' . $currency . '</td></tr>';
$html .= '</table>';
$html .= '</td></tr></table></div>';
// Notes
if (!empty($invoice['notes'])) {
    $html .= '<div style="margin-top: 25px; padding: 12px; background: #fff3cd; border-radius: 6px;">'
        .'<div style="font-size: 10pt; font-weight: bold; color: #856404; margin-bottom: 5px;">Забелешки:</div>'
        .'<div style="font-size: 10pt; color: #856404; line-height: 1.4;">' . nl2br(htmlspecialchars($invoice['notes'])) . '</div>'
    .'</div>';
}
$html .= '<div style="margin-top: 60px;">'
    .'<table width="100%" style="font-size: 11pt;">'
    .'<tr>'
    .'<td width="45%" style="text-align: left; vertical-align: bottom;">_________________________<br><span style="font-size:10pt;">Потпис на испраќач</span></td>'
    .'<td width="10%"></td>'
    .'<td width="45%" style="text-align: right; vertical-align: bottom;">_________________________<br><span style="font-size:10pt;">Потпис на примач</span></td>'
    .'</tr>'
    .'</table>'
    .'<div style="margin-top: 30px; text-align: center; font-size: 11pt;">_________________________<br><span style="font-size:10pt;">Датум</span></div>'
    .'</div>';
$html .= '</div>';

$pdf->writeHTML($html, true, false, true, false, '');
ob_end_clean();
$pdf->Output('Faktura_' . $invoice['invoice_number'] . '.pdf', 'D');
