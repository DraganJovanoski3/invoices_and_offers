<?php
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

// Get pre-invoice details
$stmt = $pdo->prepare("SELECT * FROM pre_invoices WHERE id = ?");
$stmt->execute([$preinvoice_id]);
$preinvoice = $stmt->fetch();

if (!$preinvoice) {
    header('Location: preinvoices.php');
    exit;
}

// Get pre-invoice items
$stmt = $pdo->prepare("SELECT * FROM pre_invoice_items WHERE preinvoice_id = ?");
$stmt->execute([$preinvoice_id]);
$preinvoice_items = $stmt->fetchAll();

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
$page_title = 'Уреди про фактура';
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
        
        // Update pre-invoice
        $stmt = $pdo->prepare("UPDATE pre_invoices SET 
            client_id = ?, 
            preinvoice_number = ?, 
            issue_date = ?, 
            due_date = ?, 
            subtotal = ?, 
            tax_rate = ?, 
            tax_amount = ?, 
            total_amount = ?, 
            notes = ?, 
            status = ?,
            payment_type = ?,
            advance_amount = ?,
            remaining_amount = ?,
            is_vat_obvrznik = ?,
            global_discount_rate = ?,
            global_discount_amount = ?,
            is_global_discount = ?
            WHERE id = ?");
        
        $stmt->execute([
            $_POST['client_id'],
            $_POST['preinvoice_number'],
            $_POST['issue_date'],
            $_POST['due_date'],
            $subtotal,
            $tax_rate,
            $tax_amount,
            $total_amount,
            $_POST['notes'] ?? '',
            $_POST['status'] ?? 'draft',
            $_POST['payment_type'] ?? 'full',
            $_POST['advance_amount'] ?? 0,
            $total_amount - ($_POST['advance_amount'] ?? 0), // remaining_amount
            isset($_POST['is_vat_obvrznik']) ? 1 : 0,
            $global_discount_rate,
            $global_discount_amount,
            isset($_POST['is_global_discount']) ? 1 : 0,
            $preinvoice_id
        ]);
        
        // Delete existing items
        $stmt = $pdo->prepare("DELETE FROM pre_invoice_items WHERE preinvoice_id = ?");
        $stmt->execute([$preinvoice_id]);
        
        // Insert new items
        $item_names = $_POST['item_name'] ?? [];
        $descriptions = $_POST['description'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $unit_prices = $_POST['unit_price'] ?? [];
        $total_prices = $_POST['total_price'] ?? [];
        $discount_rates = $_POST['discount_rate'] ?? [];
        $discount_amounts = $_POST['discount_amount'] ?? [];
        $service_ids = $_POST['service_id'] ?? [];
        
        $stmt = $pdo->prepare("INSERT INTO pre_invoice_items (preinvoice_id, service_id, item_name, description, quantity, unit_price, total_price, discount_rate, discount_amount, is_discount, final_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
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
                    $preinvoice_id,
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
        $success = "Про Фактурата е успешно ажурирана!";
        
        // Reload pre-invoice data
        $stmt = $pdo->prepare("SELECT * FROM pre_invoices WHERE id = ?");
        $stmt->execute([$preinvoice_id]);
        $preinvoice = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT * FROM pre_invoice_items WHERE preinvoice_id = ?");
        $stmt->execute([$preinvoice_id]);
        $preinvoice_items = $stmt->fetchAll();
        
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
            <h2>Уреди Про Фактура #<?php echo htmlspecialchars($preinvoice['preinvoice_number']); ?></h2>
            <a href="preinvoices.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Назад кон Про Фактури
            </a>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" id="preinvoiceForm">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Детали за Про Фактурата</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="client_id" class="form-label">Клиент *</label>
                                        <select class="form-select" id="client_id" name="client_id" required>
                                            <option value="">Избери клиент</option>
                                            <?php foreach ($clients as $client): ?>
                                                <option value="<?php echo $client['id']; ?>" <?php echo $client['id'] == $preinvoice['client_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($client['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="preinvoice_number" class="form-label">Број на Про Фактура *</label>
                                        <input type="text" class="form-control" id="preinvoice_number" name="preinvoice_number" value="<?php echo htmlspecialchars($preinvoice['preinvoice_number']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="issue_date" class="form-label">Датум на издавање *</label>
                                        <input type="date" class="form-control" id="issue_date" name="issue_date" value="<?php echo $preinvoice['issue_date']; ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="due_date" class="form-label">Датум за доспевање *</label>
                                        <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo $preinvoice['due_date']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Статус</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="draft" <?php echo $preinvoice['status'] === 'draft' ? 'selected' : ''; ?>>Нацрт</option>
                                            <option value="sent" <?php echo $preinvoice['status'] === 'sent' ? 'selected' : ''; ?>>Испратена</option>
                                            <option value="paid" <?php echo $preinvoice['status'] === 'paid' ? 'selected' : ''; ?>>Платена</option>
                                            <option value="cancelled" <?php echo $preinvoice['status'] === 'cancelled' ? 'selected' : ''; ?>>Откажана</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="tax_rate" class="form-label">Ставка на ДДВ (%)</label>
                                        <input type="number" step="0.01" min="0" max="100" class="form-control short-input" id="tax_rate" name="tax_rate" value="<?php echo $preinvoice['tax_rate']; ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="global_discount_rate" class="form-label">Глобална попуст (%)</label>
                                        <input type="number" step="0.01" min="0" max="100" class="form-control short-input" id="global_discount_rate" name="global_discount_rate" value="<?php echo $preinvoice['global_discount_rate'] ?? 0; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Тип на плаќање</label>
                                        <select class="form-select" id="payment_type" name="payment_type" onchange="toggleAdvanceInput()">
                                            <option value="full" <?php echo $preinvoice['payment_type'] === 'full' ? 'selected' : ''; ?>>Целосно</option>
                                            <option value="advance" <?php echo $preinvoice['payment_type'] === 'advance' ? 'selected' : ''; ?>>Аванс</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6" id="advance_amount_group" <?php echo $preinvoice['payment_type'] === 'advance' ? '' : 'style="display:none;"'; ?>>
                                    <div class="mb-3">
                                        <label for="advance_amount" class="form-label">Износ на аванс</label>
                                        <input type="number" step="0.01" class="form-control" id="advance_amount" name="advance_amount" value="<?php echo $preinvoice['advance_amount']; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Белешки на Про Фактурата</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Белешки за Про Фактурата"><?php echo htmlspecialchars($preinvoice['notes']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mt-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Ставки на Про Фактурата</h5>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addLineItem()">
                                <i class="bi bi-plus"></i> Додај ставка
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="lineItemsContainer">
                                <?php foreach ($preinvoice_items as $index => $item): ?>
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
                                <div class="h4" id="subtotal"><?php echo number_format($preinvoice['subtotal'], 2); ?> <?php echo $currency; ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Глобална попуст</label>
                                <div class="h5 text-success" id="globalDiscountAmount"><?php echo number_format($preinvoice['global_discount_amount'] ?? 0, 2); ?> <?php echo $currency; ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Износ на ДДВ</label>
                                <div class="h5" id="taxAmount"><?php echo number_format($preinvoice['tax_amount'], 2); ?> <?php echo $currency; ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Вкупен износ</label>
                                <div class="h3 text-primary" id="totalAmount"><?php echo number_format($preinvoice['total_amount'], 2); ?> <?php echo $currency; ?></div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check"></i> Зачувај промени
                                </button>
                                <a href="preinvoices.php" class="btn btn-secondary">Откажи</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    

    
    <script>
        const services = <?php echo json_encode($services); ?>;
        const currency = <?php echo json_encode($currency); ?>;
        let lineItemCounter = <?php echo count($preinvoice_items); ?>;

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

        function toggleAdvanceInput() {
            const paymentType = document.getElementById('payment_type').value;
            const advanceGroup = document.getElementById('advance_amount_group');
            if (paymentType === 'advance') {
                advanceGroup.style.display = 'block';
            } else {
                advanceGroup.style.display = 'none';
            }
        }

        function calculateTotals() {
            let subtotal = 0;
            const lineItems = document.querySelectorAll('.line-item-row');
            
            lineItems.forEach(item => {
                // Get the final price from the last readonly input (which is the final price field)
                const readonlyInputs = item.querySelectorAll('input[readonly]');
                const finalPrice = parseFloat(readonlyInputs[readonlyInputs.length - 1].value) || 0;
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
            
            // Add event listeners for tax rate and global discount rate changes
            document.getElementById('tax_rate').addEventListener('change', calculateTotals);
            document.getElementById('global_discount_rate').addEventListener('change', calculateTotals);
        });
    </script>
<?php include 'includes/footer.php'; ?>
</body>
</html> 