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

// Get additional parameters for enhanced PDF generation
$format = $_GET['format'] ?? 'standard';
$quality = $_GET['quality'] ?? '1';
$watermark = $_GET['watermark'] ?? 'false';

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

// Get invoice items with service article codes
$stmt = $pdo->prepare("
    SELECT ii.*, s.article_code 
    FROM invoice_items ii 
    LEFT JOIN services s ON ii.service_id = s.id 
    WHERE ii.invoice_id = ?
");
$stmt->execute([$invoice_id]);
$items = $stmt->fetchAll();

// Get company settings
$company = getCompanySettings($pdo);
$currency = $company['currency'] ?? 'MKD';

// Get company logo
$company_logo_path = '';
if (!empty($company['logo_path'])) {
    $company_logo_path = $company['logo_path'];
    // Make sure the path is absolute file system path for TCPDF
    if (!str_starts_with($company_logo_path, '/') && !str_starts_with($company_logo_path, 'C:')) {
        $company_logo_path = __DIR__ . '/' . $company_logo_path;
    }
}

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

// Read CSS file and embed in HTML (TCPDF strips style tags, but we'll use inline styles)
$css_file = __DIR__ . '/assets/css/download-css.css';
$css_content = file_exists($css_file) ? file_get_contents($css_file) : '';

$html = '<div style="font-family: dejavusans, helvetica, sans-serif; font-size: 10pt; color: #333;">';

// HEADER: Centered title and number at the top (matching view)
$html .= '<div style="max-width: 900px; margin: 0 auto; margin-bottom: 8px; text-align: center;">';
$html .= '<span style="font-size: 22pt; font-weight: bold; color: #007bff;">ФАКТУРА</span><br>';
$html .= '<span style="font-size: 13pt; font-weight: bold; color: #333;">#' . htmlspecialchars($invoice['invoice_number']) . '</span>';
$html .= '</div>';

// HEADER: Table with logo left/right (matching view)
$html .= '<div style="max-width: 900px; margin: 0 auto; background: #fff; padding: 0px 30px 0px 30px; font-size: 10pt;">';
$html .= '<table width="100%" style="border-collapse:collapse;"><tr>';
// Client logo left
$html .= '<td width="15%" style="vertical-align: top; text-align: left; padding-right: 10px;">';
if ($invoice['client_logo_path']) {
    $client_logo_path = $invoice['client_logo_path'];
    if (!str_starts_with($client_logo_path, '/') && !str_starts_with($client_logo_path, 'C:')) {
        $client_logo_path = __DIR__ . '/' . $client_logo_path;
    }
    if (file_exists($client_logo_path)) {
        $image_type = 'PNG';
        if (strtolower(pathinfo($client_logo_path, PATHINFO_EXTENSION)) == 'ico') {
            $image_type = 'ICO';
        }
        $html .= '<img src="' . $client_logo_path . '" style="max-width: 45px; max-height: 45px; display:block;" />';
    }
}
$html .= '</td>';
$html .= '<td width="70%"></td>';
// Company logo right
$html .= '<td width="15%" style="vertical-align: top; text-align: right; padding-left: 10px;">';
if ($company_logo_path && file_exists($company_logo_path)) {
    $html .= '<img src="' . $company_logo_path . '" style="max-width: 45px; max-height: 45px; margin-bottom: 8px; border-radius: 6px; display:block;" />';
}
$html .= '</td>';
$html .= '</tr></table>';
$html .= '</div>';

// HEADER: Company and client info side by side (matching view)
$html .= '<div style="max-width: 900px; margin: 0 auto; background: #fff; padding: 0px 30px 30px 30px; font-size: 10pt;">';
$html .= '<table width="100%"><tr>';
// Client info left
$html .= '<td width="50%" style="vertical-align: top; padding-right: 10px;">';
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
// Company info right
$html .= '<td width="50%" style="text-align: right; vertical-align: top; padding-left: 10px;">';
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
$html .= '<td width="50%" style="font-size: 10pt;"><strong>Датум на издавање:</strong> ' . date('d.m.Y', strtotime($invoice['issue_date'])) . '</td>';
$html .= '<td width="50%" style="font-size: 10pt;"><strong>Датум на доспевање:</strong> ' . date('d.m.Y', strtotime($invoice['due_date'])) . '</td>';
$html .= '</tr></table>';
$html .= '</div>';

// LEGAL INFORMATION ROW (matching view)
$html .= '<div style="max-width: 900px; margin: 0 auto; margin-bottom: 15px; padding-bottom: 10px;">';
$html .= '<table width="100%" style="margin-bottom: 0; padding-bottom: 0;"><tr>';
$html .= '<td width="50%" style="font-size: 10pt;"><strong>Датум на испорака:</strong> ' . (!empty($invoice['supply_date']) ? date('d.m.Y', strtotime($invoice['supply_date'])) : date('d.m.Y', strtotime($invoice['issue_date']))) . '</td>';
$html .= '<td width="50%" style="font-size: 10pt;"><strong>Начин на плаќање:</strong> ';
$payment_methods = [
    'bank_transfer' => 'Банкарски трансфер',
    'cash' => 'Готовина',
    'card' => 'Картичка',
    'check' => 'Чек',
    'other' => 'Друго'
];
$html .= $payment_methods[$invoice['payment_method'] ?? 'bank_transfer'] ?? 'Банкарски трансфер';
$html .= '</td>';
$html .= '</tr></table>';
$html .= '</div>';

// AUTHORIZED PERSON ROW (matching view)
$html .= '<div style="max-width: 900px; margin: 0 auto; margin-bottom: 15px; padding-bottom: 10px;">';
$html .= '<table width="100%" style="margin-bottom: 0; padding-bottom: 0;"><tr>';
$html .= '<td width="50%" style="font-size: 10pt;"><strong>Овластено лице:</strong> ' . (!empty($invoice['authorized_person']) ? htmlspecialchars($invoice['authorized_person']) : '-') . '</td>';
$html .= '<td width="50%" style="font-size: 10pt;">';
if (!empty($invoice['fiscal_receipt_number'])) {
    $html .= '<strong>Фискален број:</strong> ' . htmlspecialchars($invoice['fiscal_receipt_number']);
}
$html .= '</td>';
$html .= '</tr></table>';
$html .= '</div>';
// Check if any item has discount
$has_item_discount = false;
foreach ($items as $item) {
    if (!empty($item['discount_rate']) && $item['discount_rate'] > 0) {
        $has_item_discount = true;
        break;
    }
}

// ITEMS TABLE (matching view)
$html .= '<div style="max-width: 900px; margin: 0 auto; background: #fff; padding: 0px 30px 30px 30px;">';
$html .= '<table class="table" style="border-collapse: collapse; font-size: 10pt; width: 100%;">';
$html .= '<thead><tr style="background: #e9f3fb;">';
$html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Рб</th>';
$html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Шифра</th>';
$html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: left; font-weight: bold;">Артикал</th>';
$html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Ем</th>';
$html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Количина</th>';
$html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Цена без ДДВ</th>';
if ($has_item_discount) {
    $html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Попуст</th>';
}
$html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">ДДВ</th>';
$html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Цена со ДДВ</th>';
$html .= '<th style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-weight: bold;">Вредност со ДДВ</th>';
$html .= '</tr></thead><tbody>';
if (!empty($items)) {
    $row_num = 0;
    $total_without_vat = 0;
    $total_vat = 0;
    $total_with_vat = 0;
    $tax_rate_decimal = ($invoice['tax_rate'] ?? 0) / 100;
    $tax_multiplier = 1 + $tax_rate_decimal;
    foreach ($items as $item) {
        $row_bg = ($row_num % 2 == 0) ? '#fff' : '#f7faff';
        // Calculate unit price without VAT (unit_price includes VAT if tax_rate > 0)
        if ($tax_rate_decimal > 0) {
            $unit_price_without_vat = $item['unit_price'] / $tax_multiplier;
        } else {
            $unit_price_without_vat = $item['unit_price'];
        }
        // Apply item-level discount if exists
        $item_subtotal = $unit_price_without_vat * $item['quantity'];
        if (!empty($item['discount_rate']) && $item['discount_rate'] > 0) {
            $item_discount_amount = $item_subtotal * ($item['discount_rate'] / 100);
            $item_subtotal_after_discount = $item_subtotal - $item_discount_amount;
        } else {
            $item_discount_amount = 0;
            $item_subtotal_after_discount = $item_subtotal;
        }
        // Calculate VAT on discounted amount
        $vat_amount = $item_subtotal_after_discount * $tax_rate_decimal;
        $item_total_with_vat = $item_subtotal_after_discount + $vat_amount;
        
        $total_without_vat += $item_subtotal_after_discount;
        $total_vat += $vat_amount;
        $total_with_vat += $item_total_with_vat;
        
        $html .= '<tr style="background: ' . $row_bg . ';">';
        $html .= '<td style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-size: 10pt;">' . ($row_num + 1) . '</td>';
        $html .= '<td style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-size: 10pt;">' . htmlspecialchars($item['article_code'] ?? 'N/A') . '</td>';
        $html .= '<td style="border: 1px solid #b6d4ef; padding: 8px; text-align: left; font-size: 10pt;">';
        $html .= htmlspecialchars($item['item_name']);
        if (!empty($item['description'])) {
            $html .= '<br><span style="color: #666; font-size: 9pt;">' . nl2br(htmlspecialchars($item['description'])) . '</span>';
        }
        $html .= '</td>';
        $html .= '<td style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-size: 10pt;">бр.</td>';
        $html .= '<td style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-size: 10pt;">' . $item['quantity'] . '</td>';
        $html .= '<td style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-size: 10pt;">' . number_format($unit_price_without_vat, 2) . ' ' . $currency . '</td>';
        if ($has_item_discount) {
            $html .= '<td style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-size: 10pt;">';
            if (!empty($item['discount_rate']) && $item['discount_rate'] > 0) {
                $html .= number_format($item['discount_rate'], 2) . '%<br>';
                $html .= '<span style="color: #666; font-size: 9pt;">(-' . number_format($item_discount_amount, 2) . ' ' . $currency . ')</span>';
            } else {
                $html .= '-';
            }
            $html .= '</td>';
        }
        $display_tax_rate_item = (isset($invoice['is_vat_obvrznik']) && $invoice['is_vat_obvrznik']) ? ($invoice['tax_rate'] ?? 0) : 0;
        $html .= '<td style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-size: 10pt;">' . number_format($display_tax_rate_item, 0) . '%</td>';
        $html .= '<td style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-size: 10pt;">' . number_format($item['unit_price'], 2) . ' ' . $currency . '</td>';
        $html .= '<td style="border: 1px solid #b6d4ef; padding: 8px; text-align: center; font-size: 10pt;">' . number_format($item['total_price'], 2) . ' ' . $currency . '</td>';
        $html .= '</tr>';
        $row_num++;
    }
} else {
    $colspan = $has_item_discount ? 10 : 9;
    $html .= '<tr><td colspan="' . $colspan . '" style="border: 1px solid #b6d4ef; padding: 15px; text-align: center; color: #999; font-style: italic;">Нема додадени ставки во оваа фактура</td></tr>';
}
$html .= '</tbody></table>';
$html .= '</div>';

// VAT/DISCOUNT ROW (matching view)
$html .= '<div style="max-width: 900px; margin: 0 auto; padding: 0px 30px 30px 30px;">';
$html .= '<table width="100%" style="margin-bottom: 8px; padding-bottom: 10px;"><tr>';
$html .= '<td style="font-size: 10pt; text-align: left;"><strong>ДДВ обврзник:</strong> ' . (isset($invoice['is_vat_obvrznik']) ? ($invoice['is_vat_obvrznik'] ? 'Да' : 'Не') : '') . '</td>';
$display_tax_rate = (isset($invoice['is_vat_obvrznik']) && $invoice['is_vat_obvrznik']) ? $invoice['tax_rate'] : 0;
$html .= '<td style="font-size: 10pt; text-align: right;"><strong>Стапка на ДДВ (%):</strong> ' . htmlspecialchars($display_tax_rate) . '%</td>';
$html .= '</tr></table>';
// TOTALS SECTION (matching view)
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
$html .= '</td></tr></table>';
$html .= '</div>';

// VAT Breakdown Section (matching view)
$html .= '<div style="max-width: 900px; margin: 0 auto; margin-top: 20px; font-size: 10pt;">';
$html .= '<table style="width: 100%; border-collapse: collapse;">';
$html .= '<tr>';
$html .= '<td style="width: 50%; vertical-align: top;">';
$html .= '<div style="font-weight: bold; margin-bottom: 10px;">ддв основица ддв вкупно</div>';
$html .= '<table style="width: 100%; border-collapse: collapse; font-size: 9pt;">';
if ($invoice['tax_rate'] > 0) {
    $html .= '<tr>';
    $html .= '<td style="border: 1px solid #ccc; padding: 5px; text-align: center;">0%</td>';
    $html .= '<td style="border: 1px solid #ccc; padding: 5px; text-align: right;">0.00</td>';
    $html .= '<td style="border: 1px solid #ccc; padding: 5px; text-align: right;">0.00</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td style="border: 1px solid #ccc; padding: 5px; text-align: center;">' . number_format($invoice['tax_rate'], 0) . '%</td>';
    $html .= '<td style="border: 1px solid #ccc; padding: 5px; text-align: right;">' . number_format($total_without_vat, 2) . '</td>';
    $html .= '<td style="border: 1px solid #ccc; padding: 5px; text-align: right;">' . number_format($total_vat, 2) . '</td>';
    $html .= '</tr>';
} else {
    $html .= '<tr>';
    $html .= '<td style="border: 1px solid #ccc; padding: 5px; text-align: center;">0%</td>';
    $html .= '<td style="border: 1px solid #ccc; padding: 5px; text-align: right;">' . number_format($total_without_vat, 2) . '</td>';
    $html .= '<td style="border: 1px solid #ccc; padding: 5px; text-align: right;">0.00</td>';
    $html .= '</tr>';
}
$html .= '</table>';
$html .= '</td>';
$html .= '<td style="width: 50%; vertical-align: top; padding-left: 20px;">';
$html .= '<div style="font-weight: bold; margin-bottom: 10px;">законски застапник, овластено лице за потпишување на фактури</div>';
$html .= '<div style="margin-top: 30px; font-size: 9pt;">';
$html .= '<div style="margin-bottom: 20px;">';
$html .= '<div style="border-bottom: 1px solid #000; width: 200px; margin-bottom: 5px;"></div>';
$html .= '<div style="font-size: 8pt;">(потпис)</div>';
$html .= '</div>';
$html .= '<div style="margin-bottom: 20px;">';
$html .= '<div style="border-bottom: 1px solid #000; width: 200px; margin-bottom: 5px;"></div>';
$html .= '<div style="font-size: 8pt;">контролирал примил</div>';
$html .= '</div>';
$html .= '<div>';
$html .= '<div style="border-bottom: 1px solid #000; width: 200px; margin-bottom: 5px;"></div>';
$html .= '<div style="font-size: 8pt;">Фактурирал</div>';
$html .= '</div>';
$html .= '</div>';
$html .= '</td>';
$html .= '</tr>';
$html .= '</table>';
$html .= '</div>';

// Notes (matching view)
if (!empty($invoice['notes'])) {
    $html .= '<div style="max-width: 900px; margin: 0 auto; margin-top: 25px; padding: 12px; background: #fff3cd; border-radius: 6px;">';
    $html .= '<div style="font-size: 10pt; font-weight: bold; color: #856404; margin-bottom: 5px;">Забелешки:</div>';
    $html .= '<div style="font-size: 10pt; color: #856404; line-height: 1.4;">' . nl2br(htmlspecialchars($invoice['notes'])) . '</div>';
    $html .= '</div>';
}

// Legal Footer (matching view)
if (!empty($company['legal_footer'])) {
    $html .= '<div style="max-width: 900px; margin: 0 auto; margin-top: 40px; padding: 15px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">';
    $html .= '<div style="font-size: 9pt; color: #666; text-align: center; line-height: 1.4;">';
    $html .= htmlspecialchars($company['legal_footer']);
    $html .= '</div>';
    $html .= '</div>';
}

// Signatures (matching view)
$html .= '<div style="max-width: 900px; margin: 0 auto; margin-top: 40px;">';
$html .= '<table width="100%" style="font-size: 11pt;">';
$html .= '<tr>';
$html .= '<td width="45%" style="text-align: left; vertical-align: bottom;">_________________________<br><span style="font-size:10pt;">Потпис на испраќач</span></td>';
$html .= '<td width="10%"></td>';
$html .= '<td width="45%" style="text-align: right; vertical-align: bottom;">_________________________<br><span style="font-size:10pt;">Потпис на примач</span></td>';
$html .= '</tr>';
$html .= '</table>';
$html .= '<div style="margin-top: 30px; text-align: center; font-size: 11pt;">_________________________<br><span style="font-size:10pt;">Датум</span></div>';
$html .= '</div>';
$html .= '</div>';

$pdf->writeHTML($html, true, false, true, false, '');

// Add watermark if requested
if ($watermark === 'true') {
    $pdf->SetAlpha(0.1);
    $pdf->SetFont('dejavusans', 'B', 50);
    $pdf->SetTextColor(200, 200, 200);
    $pdf->Rotate(45, 150, 100);
    $pdf->Text(50, 100, 'КОПИЈА');
    $pdf->Rotate(0);
    $pdf->SetAlpha(1);
}

// QR code functionality removed as requested

ob_end_clean();
$pdf->Output('Faktura_' . $invoice['invoice_number'] . '.pdf', 'D');
