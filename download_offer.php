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

// Get company settings
$company = getCompanySettings($pdo);

// Include TCPDF library
require_once('tcpdf/tcpdf.php');

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Invoicing System');
$pdf->SetAuthor($company['company_name']);
$pdf->SetTitle('Понуда #' . $offer['offer_number']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(20, 18, 20);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);

$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['total_price'];
}
$tax_amount = $offer['total_amount'] - $subtotal;

$currency = $company['currency'] ?? 'MKD';

$html = '<div style="font-family: dejavusans, helvetica, sans-serif; font-size: 10pt; color: #222;">';
// Header
$html .= '<div style="max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; font-size: 10pt;">';
$html .= '<div style="border-bottom: 2px solid #28a745; padding-bottom: 20px; margin-bottom: 30px;">'
    .'<table width="100%"><tr>'
    .'<td width="60%" style="vertical-align: top;">'
    .'<div style="font-size: 22pt; font-weight: bold; color: #28a745;">ПОНУДА</div>'
    .'<div style="font-size: 13pt; font-weight: bold; color: #333; margin-bottom: 8px;">#' . htmlspecialchars($offer['offer_number']) . '</div>'
    .'</td>'
    .'<td width="40%" style="text-align: right; vertical-align: top;">';
if ($company['logo_path']) {
    $company_logo_path = $_SERVER['DOCUMENT_ROOT'] . '/invoices_and_offers/' . ltrim($company['logo_path'], '/');
    $company_logo_path = str_replace(['\\', '//'], '/', $company_logo_path);
    if (file_exists($company_logo_path)) {
        $html .= '<img src="' . $company_logo_path . '" style="width:70px; height:auto; margin-bottom: 8px; border-radius: 6px;">';
    }
}
$html .= '<div style="font-size: 12pt; font-weight: bold; color: #333;">' . htmlspecialchars($company['company_name']) . '</div>';
if ($company['address']) {
    $html .= '<div style="font-size: 9pt; color: #666;">' . nl2br(htmlspecialchars($company['address'])) . '</div>';
}
if ($company['phone']) {
    $html .= '<div style="font-size: 9pt; color: #666;">Телефон: ' . htmlspecialchars($company['phone']) . '</div>';
}
if ($company['email']) {
    $html .= '<div style="font-size: 9pt; color: #666;">Е-пошта: ' . htmlspecialchars($company['email']) . '</div>';
}
$html .= '</td></tr></table>';
$html .= '</div>';
// Client and Offer Details
$html .= '<div style="margin-bottom: 30px;"><table width="100%"><tr>';
$html .= '<td width="50%" style="vertical-align: top;">';
$html .= '<div style="font-size: 11pt; font-weight: bold; color: #333; margin-bottom: 8px;">До:</div>';
if ($offer['client_logo_path']) {
    $client_logo_path = $_SERVER['DOCUMENT_ROOT'] . '/invoices_and_offers/' . ltrim($offer['client_logo_path'], '/');
    $client_logo_path = str_replace(['\\', '//'], '/', $client_logo_path);
    if (file_exists($client_logo_path)) {
        $html .= '<img src="' . $client_logo_path . '" style="width:70px; height:auto; margin-bottom: 8px; display: block; border-radius: 6px;">';
    }
}
$html .= '<div style="font-size: 10pt; color: #222;">';
if ($offer['client_company_name']) {
    $html .= '<strong>' . htmlspecialchars($offer['client_company_name']) . '</strong><br>';
}
if ($offer['client_name']) {
    $html .= 'Контакт: ' . htmlspecialchars($offer['client_name']) . '<br>';
}
if ($offer['client_address']) {
    $html .= nl2br(htmlspecialchars($offer['client_address'])) . '<br>';
}
if ($offer['client_email']) {
    $html .= 'Е-пошта: ' . htmlspecialchars($offer['client_email']) . '<br>';
}
if ($offer['client_phone']) {
    $html .= 'Телефон: ' . htmlspecialchars($offer['client_phone']) . '<br>';
}
if ($offer['client_tax_number']) {
    $html .= 'Даночен број: ' . htmlspecialchars($offer['client_tax_number']) . '<br>';
}
if ($offer['client_website']) {
    $html .= 'Веб-страна: ' . htmlspecialchars($offer['client_website']) . '<br>';
}
if (isset($offer['is_vat_obvrznik'])) {
    $html .= '<strong>ДДВ обврзник:</strong> ' . ($offer['is_vat_obvrznik'] ? 'Да' : 'Не') . '<br>';
}
if ($offer['client_notes']) {
    $html .= '<span style="color: #888;">Забелешки: ' . nl2br(htmlspecialchars($offer['client_notes'])) . '</span>';
}
$html .= '<div><strong>Стапка на ДДВ (%):</strong> ' . htmlspecialchars($offer['tax_rate']) . '%</div>';
$html .= '</div>';
$html .= '</td>';
$html .= '<td width="50%" style="vertical-align: top;">';
$html .= '<div style="background: #f8f9fa; padding: 20px; border-radius: 5px; font-size: 10pt; margin-bottom: 10px;">';
$html .= '<div><strong>Датум на издавање:</strong> ' . date('d.m.Y', strtotime($offer['issue_date'])) . '</div>';
$html .= '<div><strong>Важи до:</strong> ' . date('d.m.Y', strtotime($offer['valid_until'])) . '</div>';
$html .= '</div>';
$html .= '</td>';
$html .= '</tr></table></div>';
// Items Table
$html .= '<div class="items-table" style="margin-bottom: 30px;">';
$html .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; font-size: 10pt;">';
$html .= '<thead><tr style="background: #e9f3fb;">';
$html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: left; font-weight: bold;">Опис</th>';
$html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Количина</th>';
$html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Ед. цена</th>';
$has_item_discount = false;
foreach ($items as $item) {
    if (!empty($item['discount_rate']) && $item['discount_rate'] > 0) {
        $has_item_discount = true;
        break;
    }
}
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
    $html .= '<tr><td colspan="' . ($has_item_discount ? 5 : 4) . '" style="border: 1px solid #b6d4ef; padding: 15px; text-align: center; color: #999; font-style: italic;">Нема додадени ставки во оваа понуда</td></tr>';
}
$html .= '</tbody></table></div>';
// Totals
$html .= '<div style="border-top: 2px solid #dee2e6; padding-top: 20px; margin-top: 10px;">';
$html .= '<table width="100%"><tr><td width="60%"></td><td width="40%">';
$html .= '<table width="100%" style="font-size: 10pt;">';
$html .= '<tr><td style="color: #333;">Вкупен износ без данок:</td>';
$html .= '<td style="text-align: right; color: #333;">' . number_format($offer['subtotal'], 2) . ' ' . $currency . '</td></tr>';
if (!empty($offer['global_discount_rate']) && $offer['global_discount_rate'] > 0) {
    $html .= '<tr><td style="color: #333;">Глобална попуст (' . number_format($offer['global_discount_rate'], 2) . '%):</td>';
    $html .= '<td style="text-align: right; color: #333;">-' . number_format($offer['global_discount_amount'], 2) . ' ' . $currency . '</td></tr>';
}
if ($offer['tax_rate'] > 0) {
    $html .= '<tr><td style="color: #333;">ДДВ (' . htmlspecialchars($offer['tax_rate']) . '%):</td>';
    $html .= '<td style="text-align: right; color: #333;">' . number_format($offer['tax_amount'], 2) . ' ' . $currency . '</td></tr>';
}
$html .= '<tr><td style="font-size: 10pt; font-weight: normal; color: #333;">Вкупен износ со данок:</td>';
$html .= '<td style="text-align: right; font-size: 11pt; font-weight: bold; color: #333;">' . number_format($offer['total_amount'], 2) . ' ' . $currency . '</td></tr>';
$html .= '</table>';
$html .= '</td></tr></table></div>';
// Notes
if (!empty($offer['notes'])) {
    $html .= '<div style="margin-top: 25px; padding: 12px; background: #fff3cd; border-radius: 6px;">'
        .'<div style="font-size: 10pt; font-weight: bold; color: #856404; margin-bottom: 5px;">Забелешки:</div>'
        .'<div style="font-size: 10pt; color: #856404; line-height: 1.4;">' . nl2br(htmlspecialchars($offer['notes'])) . '</div>'
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
$pdf->Output('Ponuda_' . $offer['offer_number'] . '.pdf', 'D');
?> 