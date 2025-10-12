<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/company_helper.php';
$company = getCompanySettings($pdo);
$page_title = 'Нова понуда';
$currency = $company['currency'] ?? 'MKD';

// Get all clients for the dropdown
$stmt = $pdo->query("SELECT id, name FROM clients ORDER BY name");
$clients = $stmt->fetchAll();

// Get all services for the dropdown
$stmt = $pdo->query("SELECT id, name, price, description FROM services ORDER BY name");
$services = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = !empty($_POST['client_id']) ? $_POST['client_id'] : null;
    $issue_date = $_POST['issue_date'] ?? date('Y-m-d');
    $valid_until = $_POST['valid_until'] ?? date('Y-m-d', strtotime('+30 days'));
    $tax_rate = floatval($_POST['tax_rate'] ?? 0);
    $global_discount_rate = floatval($_POST['global_discount_rate'] ?? 0);
    $is_global_discount = isset($_POST['is_global_discount']) ? 1 : 0;
    $notes = trim($_POST['notes'] ?? '');
    $is_vat_obvrznik = isset($_POST['is_vat_obvrznik']) ? 1 : 0;
    
    // Get line items
    $line_items = [];
    $item_names = $_POST['item_name'] ?? [];
    $service_ids = $_POST['service_id'] ?? [];
    $descriptions = $_POST['description'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $unit_prices = $_POST['unit_price'] ?? [];
    $discount_rates = $_POST['discount_rate'] ?? [];
    $is_discounts = $_POST['is_discount'] ?? [];
    
    $subtotal = 0;
    
    // Process line items
    for ($i = 0; $i < count($item_names); $i++) {
        if (!empty($item_names[$i]) && !empty($quantities[$i]) && !empty($unit_prices[$i])) {
            $quantity = floatval($quantities[$i]);
            $unit_price = floatval($unit_prices[$i]);
            $discount_rate = floatval($discount_rates[$i] ?? 0);
            $is_discount = isset($is_discounts[$i]) ? 1 : 0;
            
            $item_subtotal = $quantity * $unit_price;
            $item_discount_amount = $item_subtotal * ($discount_rate / 100);
            $final_price = $item_subtotal - $item_discount_amount;
            $subtotal += $final_price;
            
            $line_items[] = [
                'service_id' => !empty($service_ids[$i]) ? $service_ids[$i] : null,
                'item_name' => $item_names[$i],
                'description' => $descriptions[$i],
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'total_price' => $final_price,
                'discount_rate' => $discount_rate,
                'discount_amount' => $item_discount_amount,
                'is_discount' => $is_discount,
                'final_price' => $final_price
            ];
        }
    }
    
    if (empty($line_items)) {
        $error = "Задолжително е еден ред!";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Generate automatic offer number
            $current_year = date('Y');
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM offers WHERE YEAR(created_at) = ?");
            $stmt->execute([$current_year]);
            $offer_count = $stmt->fetchColumn();
            $offer_number = $current_year . '-' . str_pad($offer_count + 1, 3, '0', STR_PAD_LEFT);
            
            // Calculate global discount
            $global_discount_amount = $subtotal * ($global_discount_rate / 100);
            $subtotal_after_global_discount = $subtotal - $global_discount_amount;
            
            // Calculate totals
            $tax_amount = $subtotal_after_global_discount * ($tax_rate / 100);
            $total_amount = $subtotal_after_global_discount + $tax_amount;
            
            // Get payment type and advance amount
            $payment_type = $_POST['payment_type'] ?? 'full';
            $advance_amount = floatval($_POST['advance_amount'] ?? 0);
            $remaining_amount = $total_amount - $advance_amount;
            
            // Create offer
            $stmt = $pdo->prepare("INSERT INTO offers (client_id, offer_number, issue_date, valid_until, subtotal, tax_rate, tax_amount, total_amount, notes, is_vat_obvrznik, global_discount_rate, global_discount_amount, is_global_discount, payment_type, advance_amount, remaining_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$client_id, $offer_number, $issue_date, $valid_until, $subtotal, $tax_rate, $tax_amount, $total_amount, $notes, $is_vat_obvrznik, $global_discount_rate, $global_discount_amount, $is_global_discount, $payment_type, $advance_amount, $remaining_amount]);
            $offer_id = $pdo->lastInsertId();
            
            // Create offer items
            $stmt = $pdo->prepare("INSERT INTO offer_items (offer_id, service_id, item_name, description, quantity, unit_price, total_price, discount_rate, discount_amount, is_discount, final_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($line_items as $item) {
                $stmt->execute([$offer_id, $item['service_id'], $item['item_name'], $item['description'], $item['quantity'], $item['unit_price'], $item['total_price'], $item['discount_rate'], $item['discount_amount'], $item['is_discount'], $item['final_price']]);
            }
            
            $pdo->commit();
            $success = "Понудата е креирана успешно! Број на понуда: " . $offer_number;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Грешка при креирање на понуда: " . $e->getMessage();
        }
    }
}

// Set default dates
$default_issue_date = date('Y-m-d');
$default_valid_until = date('Y-m-d', strtotime('+30 days'));
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
    

    <div class="container mt-4 main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Креирај нова понуда</h2>
            <a href="offers.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Назад до понуди
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" id="offerForm">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Детали за понудата</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="client_id" class="form-label">Клиент</label>
                                        <select class="form-select" id="client_id" name="client_id">
                                            <option value="">Избери клиент (незадолжително)</option>
                                            <?php foreach ($clients as $client): ?>
                                                <option value="<?php echo $client['id']; ?>">
                                                    <?php echo htmlspecialchars($client['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="issue_date" class="form-label">Датум на издавање</label>
                                        <input type="date" class="form-control" id="issue_date" name="issue_date" value="<?php echo $default_issue_date; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="valid_until" class="form-label">Важи до</label>
                                        <input type="date" class="form-control" id="valid_until" name="valid_until" value="<?php echo $default_valid_until; ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="tax_rate" class="form-label">Стапка на ДДВ (%)</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="number" step="0.01" min="0" max="100" class="form-control short-input" id="tax_rate" name="tax_rate" value="0" disabled>
                                            <div class="discount-checkbox d-flex align-items-center mb-0" style="height: 38px;">
                                                <input class="form-check-input me-1" type="checkbox" id="is_vat_obvrznik" name="is_vat_obvrznik" value="1" onchange="toggleVatRate()">
                                                <label class="form-check-label ms-1 big-checkbox-label" for="is_vat_obvrznik">ДДВ обврзник</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="global_discount_rate" class="form-label">Глобална попуст (%)</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="number" step="0.01" min="0" max="100" class="form-control short-input" id="global_discount_rate" name="global_discount_rate" value="0" disabled>
                                            <div class="discount-checkbox d-flex align-items-center mb-0" style="height: 38px;">
                                                <input class="form-check-input me-1" type="checkbox" id="is_global_discount" name="is_global_discount" value="1" onchange="toggleGlobalDiscount()">
                                                <label class="form-check-label ms-1 big-checkbox-label" for="is_global_discount">Глобална попуст</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Белешки на понудата</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Белешки за понудата"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_type" class="form-label">Тип на плаќање</label>
                                        <select class="form-select" id="payment_type" name="payment_type" onchange="toggleAdvanceInput()">
                                            <option value="full">Целосно плаќање</option>
                                            <option value="advance">Авансно плаќање</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3" id="advance_amount_group" style="display: none;">
                                        <label for="advance_amount" class="form-label">Износ на аванс</label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="advance_amount" name="advance_amount" value="0" onchange="calculateTotals()">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Стапки на понудата</h5>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addLineItem()">
                                <i class="bi bi-plus"></i> Додади Стапка
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="lineItemsContainer">
                                <!-- Редови за понудата ќе се додаваат тука -->
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
                                <div class="h4" id="subtotal">0.00 <?php echo $currency; ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Глобална попуст</label>
                                <div class="h5 text-success" id="globalDiscountAmount">0.00 <?php echo $currency; ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Износ на ДДВ</label>
                                <div class="h5" id="taxAmount">0.00 <?php echo $currency; ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Вкупен износ</label>
                                <div class="h3 text-primary" id="totalAmount">0.00 <?php echo $currency; ?></div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check"></i> Креирај понуда
                                </button>
                                <a href="offers.php" class="btn btn-secondary">Откажи</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    </div>

    

    
    <script>
        // Services data for JavaScript
        const services = <?php echo json_encode($services); ?>;
        const currency = <?php echo json_encode($currency); ?>;
        let lineItemCounter = 0;

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
                        <label class="form-label">Избери услуга (незадолжително)</label>
                        <select class="form-select service-select" onchange="updateItemFromService(${lineItemCounter}, this.value)">
                            <option value="">Избери услуга или внесеј свој ред</option>
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
                                <label class="form-label">Единица цена *</label>
                                <input type="number" step="0.01" min="0.01" class="form-control short-input" name="unit_price[]" required onchange="calculateLineTotal(${lineItemCounter})">
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
                                <label class="form-label">Количина *</label>
                                <input type="number" step="0.01" min="0.01" class="form-control short-input" name="quantity[]" value="1" required onchange="calculateLineTotal(${lineItemCounter})">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Вкупен износ</label>
                                <input type="text" class="form-control" readonly id="lineTotal${lineItemCounter}" value="0.00 ${currency}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Опис</label>
                                <textarea class="form-control" name="description[]" rows="2" placeholder="Незадолжителен опис"></textarea>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="service_id[]" value="">
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
            if (!serviceId) return;
            
            const service = services.find(s => s.id == serviceId);
            if (service) {
                const lineItem = document.getElementById(`lineItem${lineItemIndex}`);
                lineItem.querySelector('input[name="item_name[]"]').value = service.name;
                lineItem.querySelector('input[name="unit_price[]"]').value = service.price;
                lineItem.querySelector('textarea[name="description[]"]').value = service.description || '';
                lineItem.querySelector('input[name="service_id[]"]').value = service.id;
                
                calculateLineTotal(lineItemIndex);
            }
        }

        function calculateLineTotal(lineItemIndex) {
            const lineItem = document.getElementById(`lineItem${lineItemIndex}`);
            if (!lineItem) return;
            
            const quantity = parseFloat(lineItem.querySelector('input[name="quantity[]"]').value) || 0;
            const unitPrice = parseFloat(lineItem.querySelector('input[name="unit_price[]"]').value) || 0;
            const discountRate = parseFloat(lineItem.querySelector('input[name="discount_rate[]"]').value) || 0;
            
            const subtotal = quantity * unitPrice;
            const discountAmount = subtotal * (discountRate / 100);
            const total = subtotal - discountAmount;
            
            lineItem.querySelector(`#lineTotal${lineItemIndex}`).value = `${total.toFixed(2)} ${currency}`;
            calculateTotals();
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
            
            lineItems.forEach((item, index) => {
                const quantity = parseFloat(item.querySelector('input[name="quantity[]"]').value) || 0;
                const unitPrice = parseFloat(item.querySelector('input[name="unit_price[]"]').value) || 0;
                const discountRate = parseFloat(item.querySelector('input[name="discount_rate[]"]').value) || 0;
                
                const itemSubtotal = quantity * unitPrice;
                const itemDiscount = itemSubtotal * (discountRate / 100);
                subtotal += itemSubtotal - itemDiscount;
            });
            
            const globalDiscountRate = parseFloat(document.getElementById('global_discount_rate').value) || 0;
            const globalDiscountAmount = subtotal * (globalDiscountRate / 100);
            const subtotalAfterGlobalDiscount = subtotal - globalDiscountAmount;
            
            const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
            const taxAmount = subtotalAfterGlobalDiscount * (taxRate / 100);
            const total = subtotalAfterGlobalDiscount + taxAmount;
            
            document.getElementById('subtotal').textContent = `${subtotal.toFixed(2)} ${currency}`;
            document.getElementById('globalDiscountAmount').textContent = `${globalDiscountAmount.toFixed(2)} ${currency}`;
            document.getElementById('taxAmount').textContent = `${taxAmount.toFixed(2)} ${currency}`;
            document.getElementById('totalAmount').textContent = `${total.toFixed(2)} ${currency}`;
        }

        function toggleVatRate() {
            const vatCheckbox = document.getElementById('is_vat_obvrznik');
            const taxRateInput = document.getElementById('tax_rate');
            
            if (vatCheckbox.checked) {
                taxRateInput.disabled = false;
                taxRateInput.required = true;
            } else {
                taxRateInput.disabled = true;
                taxRateInput.required = false;
                taxRateInput.value = '0';
                calculateTotals();
            }
        }

        function toggleGlobalDiscount() {
            const discountCheckbox = document.getElementById('is_global_discount');
            const discountRateInput = document.getElementById('global_discount_rate');
            
            if (discountCheckbox.checked) {
                discountRateInput.disabled = false;
                discountRateInput.required = true;
            } else {
                discountRateInput.disabled = true;
                discountRateInput.required = false;
                discountRateInput.value = '0';
                calculateTotals();
            }
        }

        function toggleItemDiscount(lineItemIndex) {
            const discountCheckbox = document.getElementById(`isDiscount${lineItemIndex}`);
            const discountRateInput = document.getElementById(`discountRate${lineItemIndex}`);
            
            if (discountCheckbox.checked) {
                discountRateInput.disabled = false;
                discountRateInput.required = true;
            } else {
                discountRateInput.disabled = true;
                discountRateInput.required = false;
                discountRateInput.value = '0';
                calculateLineTotal(lineItemIndex);
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            addLineItem(); // Only add one row
            
            // Add event listener for tax rate changes
            document.getElementById('tax_rate').addEventListener('change', calculateTotals);
            
            // Add event listener for global discount rate changes
            document.getElementById('global_discount_rate').addEventListener('change', calculateTotals);
        });
    </script>
<?php include 'includes/footer.php'; ?>
</body>
</html> 