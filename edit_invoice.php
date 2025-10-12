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

// Get invoice details
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header('Location: invoices.php');
    exit;
}

// Get invoice items
$stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$stmt->execute([$invoice_id]);
$invoice_items = $stmt->fetchAll();

// Get all clients
$stmt = $pdo->prepare("SELECT * FROM clients ORDER BY name");
$stmt->execute();
$clients = $stmt->fetchAll();

// Get all services
$stmt = $pdo->prepare("SELECT * FROM services ORDER BY name");
$stmt->execute();
$services = $stmt->fetchAll();

// Get company settings
$company = getCompanySettings($pdo);
$page_title = 'Уреди фактура';
$currency = $company['currency'] ?? 'MKD';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Calculate totals from line items
        $subtotal = 0;
        $item_names = $_POST['item_name'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $unit_prices = $_POST['unit_price'] ?? [];
        $discount_rates = $_POST['discount_rate'] ?? [];
        $discount_amounts = $_POST['discount_amount'] ?? [];
        
        for ($i = 0; $i < count($item_names); $i++) {
            if (!empty($item_names[$i])) {
                $quantity = floatval($quantities[$i] ?? 0);
                $unit_price = floatval($unit_prices[$i] ?? 0);
                $discount_rate = floatval($discount_rates[$i] ?? 0);
                
                $line_total = $quantity * $unit_price;
                $discount_amount = $line_total * ($discount_rate / 100);
                $final_price = $line_total - $discount_amount;
                
                $subtotal += $final_price;
            }
        }
        
        // Calculate global discount
        $global_discount_rate = floatval($_POST['global_discount_rate'] ?? 0);
        $global_discount_amount = $subtotal * ($global_discount_rate / 100);
        $subtotal_after_global_discount = $subtotal - $global_discount_amount;
        
        // Calculate tax
        $tax_rate = floatval($_POST['tax_rate'] ?? 0);
        $tax_amount = $subtotal_after_global_discount * ($tax_rate / 100);
        $total_amount = $subtotal_after_global_discount + $tax_amount;
        
        // Update invoice
        $stmt = $pdo->prepare("UPDATE invoices SET 
            client_id = ?, 
            invoice_number = ?, 
            issue_date = ?, 
            due_date = ?, 
            subtotal = ?, 
            tax_rate = ?, 
            tax_amount = ?, 
            total_amount = ?, 
            notes = ?, 
            status = ?,
            is_vat_obvrznik = ?,
            global_discount_rate = ?,
            global_discount_amount = ?,
            is_global_discount = ?
            WHERE id = ?");
        
        $stmt->execute([
            $_POST['client_id'],
            $_POST['invoice_number'],
            $_POST['issue_date'],
            $_POST['due_date'],
            $subtotal,
            $tax_rate,
            $tax_amount,
            $total_amount,
            $_POST['notes'] ?? '',
            $_POST['status'] ?? 'draft',
            isset($_POST['is_vat_obvrznik']) ? 1 : 0,
            $global_discount_rate,
            $global_discount_amount,
            isset($_POST['is_global_discount']) ? 1 : 0,
            $invoice_id
        ]);
        
        // Delete existing items
        $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
        $stmt->execute([$invoice_id]);
        
        // Insert new items
        $item_names = $_POST['item_name'] ?? [];
        $descriptions = $_POST['description'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $unit_prices = $_POST['unit_price'] ?? [];
        $total_prices = $_POST['total_price'] ?? [];
        $discount_rates = $_POST['discount_rate'] ?? [];
        $discount_amounts = $_POST['discount_amount'] ?? [];
        $service_ids = $_POST['service_id'] ?? [];
        
        $stmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, service_id, item_name, description, quantity, unit_price, total_price, discount_rate, discount_amount, is_discount, final_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < count($item_names); $i++) {
            if (!empty($item_names[$i])) {
                $quantity = floatval($quantities[$i] ?? 0);
                $unit_price = floatval($unit_prices[$i] ?? 0);
                $discount_rate = floatval($discount_rates[$i] ?? 0);
                
                $line_total = $quantity * $unit_price;
                $discount_amount = $line_total * ($discount_rate / 100);
                $final_price = $line_total - $discount_amount;
                $is_discount = !empty($discount_rates[$i]) && $discount_rates[$i] > 0 ? 1 : 0;
                
                $stmt->execute([
                    $invoice_id,
                    $service_ids[$i] ?? null,
                    $item_names[$i],
                    $descriptions[$i],
                    $quantities[$i],
                    $unit_prices[$i],
                    $line_total,
                    $discount_rate,
                    $discount_amount,
                    $is_discount,
                    $final_price
                ]);
            }
        }
        
        $pdo->commit();
        $success = "Фактурата е успешно ажурирана!";
        
        // Reload invoice data
        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id]);
        $invoice = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
        $stmt->execute([$invoice_id]);
        $invoice_items = $stmt->fetchAll();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Грешка при ажурирање: " . $e->getMessage();
    }
}

$logo_path = '/uploads/company_logo.png';
$upload_dir = __DIR__ . '/uploads/';
$logo_files = glob($upload_dir . 'company_logo*.png');
if ($logo_files && count($logo_files) > 0) {
    $logo_path = '/uploads/' . basename($logo_files[0]);
}
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
    

    <div class="container mt-4 main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Уреди фактура #<?php echo htmlspecialchars($invoice['invoice_number']); ?></h2>
            <a href="invoices.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Назад кон фактури
            </a>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" id="invoiceForm">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Детали за фактурата</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="client_id" class="form-label">Клиент *</label>
                                        <select class="form-select" id="client_id" name="client_id" required>
                                            <option value="">Избери клиент</option>
                                            <?php foreach ($clients as $client): ?>
                                                <option value="<?php echo $client['id']; ?>" <?php echo $client['id'] == $invoice['client_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($client['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="invoice_number" class="form-label">Број на фактура *</label>
                                        <input type="text" class="form-control" id="invoice_number" name="invoice_number" value="<?php echo htmlspecialchars($invoice['invoice_number']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="issue_date" class="form-label">Датум на издавање *</label>
                                        <input type="date" class="form-control" id="issue_date" name="issue_date" value="<?php echo $invoice['issue_date']; ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="due_date" class="form-label">Датум за доспевање *</label>
                                        <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo $invoice['due_date']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Статус</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="draft" <?php echo $invoice['status'] === 'draft' ? 'selected' : ''; ?>>Нацрт</option>
                                            <option value="sent" <?php echo $invoice['status'] === 'sent' ? 'selected' : ''; ?>>Испратена</option>
                                            <option value="paid" <?php echo $invoice['status'] === 'paid' ? 'selected' : ''; ?>>Платена</option>
                                            <option value="overdue" <?php echo $invoice['status'] === 'overdue' ? 'selected' : ''; ?>>Задоцнета</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="tax_rate" class="form-label">Ставка на ДДВ (%)</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="number" step="0.01" min="0" max="100" class="form-control short-input" id="tax_rate" name="tax_rate" value="<?php echo $invoice['tax_rate']; ?>" <?php echo !$invoice['is_vat_obvrznik'] ? 'disabled' : ''; ?>>
                                            <div class="discount-checkbox d-flex align-items-center mb-0" style="height: 38px;">
                                                <input class="form-check-input me-1" type="checkbox" id="is_vat_obvrznik" name="is_vat_obvrznik" value="1" <?php echo $invoice['is_vat_obvrznik'] ? 'checked' : ''; ?> onchange="toggleVatRate()">
                                                <label class="form-check-label ms-1 big-checkbox-label" for="is_vat_obvrznik">ДДВ обврзник</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="global_discount_rate" class="form-label">Глобална попуст (%)</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="number" step="0.01" min="0" max="100" class="form-control short-input" id="global_discount_rate" name="global_discount_rate" value="<?php echo $invoice['global_discount_rate'] ?? 0; ?>" <?php echo !$invoice['is_global_discount'] ? 'disabled' : ''; ?>>
                                            <div class="discount-checkbox d-flex align-items-center mb-0" style="height: 38px;">
                                                <input class="form-check-input me-1" type="checkbox" id="is_global_discount" name="is_global_discount" value="1" <?php echo $invoice['is_global_discount'] ? 'checked' : ''; ?> onchange="toggleGlobalDiscount()">
                                                <label class="form-check-label ms-1 big-checkbox-label" for="is_global_discount">Глобална попуст</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Белешки на фактурата</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Белешки за фактурата"><?php echo htmlspecialchars($invoice['notes']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Ставки на фактурата</h5>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addLineItem()">
                                <i class="bi bi-plus"></i> Додај ставка
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="lineItemsContainer">
                                <?php foreach ($invoice_items as $index => $item): ?>
                                    <div class="line-item-row" id="lineItem<?php echo $index; ?>">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h6 class="mb-0">Ред <?php echo $index + 1; ?></h6>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLineItem(<?php echo $index; ?>)">
                                                <i class="bi bi-trash"></i> Отстрани
                                            </button>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Избери услуга (Незадолжително)</label>
                                            <select class="form-select service-select" name="service_id[]" onchange="updateItemFromService(<?php echo $index; ?>, this.value)">
                                                <option value="">Избери услуга</option>
                                                <?php foreach ($services as $service): ?>
                                                    <option value="<?php echo $service['id']; ?>" <?php echo $service['id'] == $item['service_id'] ? 'selected' : ''; ?> data-name="<?php echo htmlspecialchars($service['name']); ?>" data-price="<?php echo $service['price']; ?>" data-description="<?php echo htmlspecialchars($service['description'] ?? ''); ?>">
                                                        <?php echo htmlspecialchars($service['name']); ?> - <?php echo $service['price']; ?> <?php echo $currency; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Име на ставка *</label>
                                                    <input type="text" class="form-control" name="item_name[]" value="<?php echo htmlspecialchars($item['item_name']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Количина *</label>
                                                    <input type="number" step="1" min="1" class="form-control short-input" name="quantity[]" value="<?php echo $item['quantity']; ?>" required onchange="calculateLineTotal(<?php echo $index; ?>)">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Единица цена *</label>
                                                    <input type="number" step="0.01" min="0.01" class="form-control short-input" name="unit_price[]" value="<?php echo $item['unit_price']; ?>" required onchange="calculateLineTotal(<?php echo $index; ?>)">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Попуст (%)</label>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <input type="number" step="0.01" min="0" max="100" class="form-control short-input" name="discount_rate[]" id="discountRate<?php echo $index; ?>" value="<?php echo $item['discount_rate']; ?>" <?php echo !$item['is_discount'] ? 'disabled' : ''; ?> onchange="calculateLineTotal(<?php echo $index; ?>)">
                                                        <div class="discount-checkbox d-flex align-items-center mb-0" style="height: 38px;">
                                                            <input class="form-check-input me-1" type="checkbox" name="is_discount[]" id="isDiscount<?php echo $index; ?>" value="1" <?php echo $item['is_discount'] ? 'checked' : ''; ?> onchange="toggleItemDiscount(<?php echo $index; ?>)">
                                                            <label class="form-check-label ms-1 big-checkbox-label" for="isDiscount<?php echo $index; ?>">Попуст на ставка</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Опис</label>
                                                    <textarea class="form-control" name="description[]" rows="3" placeholder="Опис на ставката"><?php echo htmlspecialchars($item['description']); ?></textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Вкупно за ставка</label>
                                                    <input type="number" step="0.01" class="form-control" name="total_price[]" value="<?php echo $item['total_price']; ?>" readonly>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Попуст (ден)</label>
                                                    <input type="number" step="0.01" class="form-control" name="discount_amount[]" value="<?php echo $item['discount_amount']; ?>" readonly>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Крајна цена</label>
                                                    <input type="number" step="0.01" class="form-control" value="<?php echo $item['final_price']; ?>" readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Вкупно</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Подвкупен износ</label>
                                <div class="h4" id="subtotal"><?php echo number_format($invoice['subtotal'], 2); ?> <?php echo $currency; ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Глобална попуст</label>
                                <div class="h5 text-success" id="globalDiscountAmount"><?php echo number_format($invoice['global_discount_amount'] ?? 0, 2); ?> <?php echo $currency; ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Износ на ДДВ</label>
                                <div class="h5" id="taxAmount"><?php echo number_format($invoice['tax_amount'], 2); ?> <?php echo $currency; ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Вкупен износ</label>
                                <div class="h3 text-primary" id="totalAmount"><?php echo number_format($invoice['total_amount'], 2); ?> <?php echo $currency; ?></div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check"></i> Зачувај промени
                                </button>
                                <a href="invoices.php" class="btn btn-secondary">Откажи</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    

    
    <script>
        // Services data for JavaScript
        const services = <?php echo json_encode($services); ?>;
        const currency = <?php echo json_encode($currency); ?>;
        let lineItemCounter = <?php echo count($invoice_items); ?>;

        function addLineItem() {
            const container = document.getElementById('lineItemsContainer');
            const lineItemHtml = `
                <div class="line-item-row" id="lineItem${lineItemCounter}">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h6 class="mb-0">Ред ${lineItemCounter + 1}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLineItem(${lineItemCounter})">
                            <i class="bi bi-trash"></i> Отстрани
                        </button>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Избери услуга (Незадолжително)</label>
                        <select class="form-select service-select" name="service_id[]" onchange="updateItemFromService(${lineItemCounter}, this.value)">
                            <option value="">Избери услуга</option>
                            ${services.map(service => `<option value="${service.id}" data-name="${service.name}" data-price="${service.price}" data-description="${service.description || ''}">${service.name} - ${service.price} ${currency}</option>`).join('')}
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Име на ставка *</label>
                                <input type="text" class="form-control" name="item_name[]" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Количина *</label>
                                <input type="number" step="1" min="1" class="form-control short-input" name="quantity[]" value="1" required onchange="calculateLineTotal(${lineItemCounter})">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Единица цена *</label>
                                <input type="number" step="0.01" min="0.01" class="form-control short-input" name="unit_price[]" value="0.00" required onchange="calculateLineTotal(${lineItemCounter})">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Попуст (%)</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="number" step="0.01" min="0" max="100" class="form-control short-input" name="discount_rate[]" id="discountRate${lineItemCounter}" value="0" disabled onchange="calculateLineTotal(${lineItemCounter})">
                                    <div class="discount-checkbox d-flex align-items-center mb-0" style="height: 38px;">
                                        <input class="form-check-input me-1" type="checkbox" name="is_discount[]" id="isDiscount${lineItemCounter}" value="1" onchange="toggleItemDiscount(${lineItemCounter})">
                                        <label class="form-check-label ms-1 big-checkbox-label" for="isDiscount${lineItemCounter}">Попуст на ставка</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Опис</label>
                                <textarea class="form-control" name="description[]" rows="3" placeholder="Опис на ставката"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Вкупно за ставка</label>
                                <input type="number" step="0.01" class="form-control" name="total_price[]" value="0.00" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Попуст (ден)</label>
                                <input type="number" step="0.01" class="form-control" name="discount_amount[]" value="0.00" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Крајна цена</label>
                                <input type="number" step="0.01" class="form-control" value="0.00" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', lineItemHtml);
            lineItemCounter++;
        }

        function removeLineItem(index) {
            const element = document.getElementById(`lineItem${index}`);
            if (element) {
                element.remove();
                calculateTotals();
            }
        }

        function updateItemFromService(lineItemIndex, serviceId) {
            const service = services.find(s => s.id == serviceId);
            if (service) {
                const lineItem = document.getElementById(`lineItem${lineItemIndex}`);
                lineItem.querySelector('input[name="item_name[]"]').value = service.name;
                lineItem.querySelector('input[name="unit_price[]"]').value = service.price;
                lineItem.querySelector('textarea[name="description[]"]').value = service.description || '';
                calculateLineTotal(lineItemIndex);
            }
        }

        function calculateLineTotal(lineItemIndex) {
            const lineItem = document.getElementById(`lineItem${lineItemIndex}`);
            const quantity = parseFloat(lineItem.querySelector('input[name="quantity[]"]').value) || 0;
            const unitPrice = parseFloat(lineItem.querySelector('input[name="unit_price[]"]').value) || 0;
            const discountRate = parseFloat(lineItem.querySelector('input[name="discount_rate[]"]').value) || 0;
            
            const subtotal = quantity * unitPrice;
            const discountAmount = subtotal * (discountRate / 100);
            const finalPrice = subtotal - discountAmount;
            
            lineItem.querySelector('input[name="total_price[]"]').value = subtotal.toFixed(2);
            lineItem.querySelector('input[name="discount_amount[]"]').value = discountAmount.toFixed(2);
            lineItem.querySelector('input[readonly]:last-child').value = finalPrice.toFixed(2);
            
            calculateTotals();
        }

        function toggleItemDiscount(lineItemIndex) {
            const checkbox = document.getElementById(`isDiscount${lineItemIndex}`);
            const discountInput = document.getElementById(`discountRate${lineItemIndex}`);
            discountInput.disabled = !checkbox.checked;
            if (!checkbox.checked) {
                discountInput.value = 0;
            }
            calculateLineTotal(lineItemIndex);
        }

        function toggleVatRate() {
            const checkbox = document.getElementById('is_vat_obvrznik');
            const taxInput = document.getElementById('tax_rate');
            taxInput.disabled = !checkbox.checked;
            if (!checkbox.checked) {
                taxInput.value = 0;
            }
            calculateTotals();
        }

        function toggleGlobalDiscount() {
            const checkbox = document.getElementById('is_global_discount');
            const discountInput = document.getElementById('global_discount_rate');
            discountInput.disabled = !checkbox.checked;
            if (!checkbox.checked) {
                discountInput.value = 0;
            }
            calculateTotals();
        }

        function calculateTotals() {
            let subtotal = 0;
            const lineItems = document.querySelectorAll('.line-item-row');
            
            lineItems.forEach(item => {
                const finalPrice = parseFloat(item.querySelector('input[readonly]:last-child').value) || 0;
                subtotal += finalPrice;
            });
            
            const globalDiscountRate = parseFloat(document.getElementById('global_discount_rate').value) || 0;
            const globalDiscountAmount = subtotal * (globalDiscountRate / 100);
            const subtotalAfterDiscount = subtotal - globalDiscountAmount;
            
            const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
            const taxAmount = subtotalAfterDiscount * (taxRate / 100);
            const totalAmount = subtotalAfterDiscount + taxAmount;
            
            document.getElementById('subtotal').textContent = subtotal.toFixed(2) + ' ' + currency;
            document.getElementById('globalDiscountAmount').textContent = globalDiscountAmount.toFixed(2) + ' ' + currency;
            document.getElementById('taxAmount').textContent = taxAmount.toFixed(2) + ' ' + currency;
            document.getElementById('totalAmount').textContent = totalAmount.toFixed(2) + ' ' + currency;
        }

        // Initialize calculations on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotals();
        });
    </script>
<?php include 'includes/footer.php'; ?>
</body>
</html> 