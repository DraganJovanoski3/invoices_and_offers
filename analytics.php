<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/company_helper.php';

// Get current year statistics
$current_year = date('Y');

// Basic statistics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE YEAR(created_at) = ?");
$stmt->execute([$current_year]);
$invoices_this_year = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM offers WHERE YEAR(created_at) = ?");
$stmt->execute([$current_year]);
$offers_this_year = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM pre_invoices WHERE YEAR(created_at) = ?");
$stmt->execute([$current_year]);
$preinvoices_this_year = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM clients");
$stmt->execute();
$total_clients = $stmt->fetchColumn();

// Analytics data
$stmt = $pdo->prepare("SELECT SUM(total_amount) FROM invoices WHERE YEAR(created_at) = ? AND status = 'paid'");
$stmt->execute([$current_year]);
$total_revenue = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE status = 'paid'");
$stmt->execute();
$paid_invoices = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE status = 'sent'");
$stmt->execute();
$sent_invoices = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE status = 'overdue'");
$stmt->execute();
$overdue_invoices = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE status = 'draft'");
$stmt->execute();
$draft_invoices = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM offers WHERE status = 'accepted'");
$stmt->execute();
$accepted_offers = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM offers WHERE status = 'sent'");
$stmt->execute();
$sent_offers = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM offers WHERE status = 'draft'");
$stmt->execute();
$draft_offers = $stmt->fetchColumn();

// Monthly data for charts
$monthly_data = [];
for ($month = 1; $month <= 12; $month++) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?");
    $stmt->execute([$current_year, $month]);
    $monthly_data['invoices'][$month] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM offers WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?");
    $stmt->execute([$current_year, $month]);
    $monthly_data['offers'][$month] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pre_invoices WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?");
    $stmt->execute([$current_year, $month]);
    $monthly_data['preinvoices'][$month] = $stmt->fetchColumn();
}

// Revenue by month
$monthly_revenue = [];
for ($month = 1; $month <= 12; $month++) {
    $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM invoices WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? AND status = 'paid'");
    $stmt->execute([$current_year, $month]);
    $monthly_revenue[$month] = $stmt->fetchColumn() ?? 0;
}

// Get company settings
$company = getCompanySettings($pdo);
$page_title = 'Аналитика';
$currency = $company['currency'] ?? 'MKD';
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-4 main-content">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="bi bi-graph-up"></i> Аналитика на бизнисот</h2>
                <p class="text-muted">Преглед на клучните метрики и трендови за <?php echo $current_year; ?></p>
                <?php if ($total_revenue == 0 && $invoices_this_year > 0): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Имате <?php echo $invoices_this_year; ?> фактури, но нема платени фактури за прикажување на приход.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Key Metrics Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card revenue-card text-center">
                    <div class="card-body">
                        <h3 class="text-white"><?php echo number_format($total_revenue, 2); ?> <?php echo $currency; ?></h3>
                        <p class="card-text text-white">Вкупен приход (<?php echo $current_year; ?>)</p>
                        <?php if ($total_revenue == 0): ?>
                            <small class="text-white-50">Нема платени фактури</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card success-card text-center">
                    <div class="card-body">
                        <h3 class="text-white"><?php echo $paid_invoices; ?></h3>
                        <p class="card-text text-white">Платени фактури</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card warning-card text-center">
                    <div class="card-body">
                        <h3 class="text-white"><?php echo $accepted_offers; ?></h3>
                        <p class="card-text text-white">Прифатени понуди</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card info-card text-center">
                    <div class="card-body">
                        <h3 class="text-white"><?php echo $overdue_invoices; ?></h3>
                        <p class="card-text text-white">Задоцнети фактури</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Document Counts Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="text-primary"><?php echo $invoices_this_year; ?></h4>
                        <p class="card-text">Фактури (<?php echo $current_year; ?>)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="text-success"><?php echo $offers_this_year; ?></h4>
                        <p class="card-text">Понуди (<?php echo $current_year; ?>)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="text-info"><?php echo $preinvoices_this_year; ?></h4>
                        <p class="card-text">Про Фактури (<?php echo $current_year; ?>)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h4 class="text-warning"><?php echo $total_clients; ?></h4>
                        <p class="card-text">Вкупно клиенти</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="bi bi-pie-chart"></i> Статус на фактури</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="invoiceStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="bi bi-pie-chart"></i> Статус на понуди</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="offerStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trends Charts Row -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="bi bi-graph-up"></i> Месечни трендови (<?php echo $current_year; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="monthlyTrendsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="bi bi-graph-up"></i> Месечен приход (<?php echo $current_year; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="monthlyRevenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    

    
    <script>
        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', function() {
            // Check if Chart.js is loaded
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded!');
                return;
            }
            
            console.log('Chart.js loaded successfully');
            
            // Invoice Status Chart
            const invoiceStatusCtx = document.getElementById('invoiceStatusChart').getContext('2d');
        new Chart(invoiceStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Платени', 'Испратени', 'Задоцнети', 'Нацрт'],
                datasets: [{
                    data: [<?php echo $paid_invoices ?: 0; ?>, <?php echo $sent_invoices ?: 0; ?>, <?php echo $overdue_invoices ?: 0; ?>, <?php echo $draft_invoices ?: 0; ?>],
                    backgroundColor: [
                        '#28a745',
                        '#007bff',
                        '#dc3545',
                        '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Offer Status Chart
        const offerStatusCtx = document.getElementById('offerStatusChart').getContext('2d');
        new Chart(offerStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Прифатени', 'Испратени', 'Нацрт'],
                datasets: [{
                    data: [<?php echo $accepted_offers; ?>, <?php echo $sent_offers; ?>, <?php echo $draft_offers; ?>],
                    backgroundColor: [
                        '#28a745',
                        '#007bff',
                        '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Monthly Trends Chart
        const monthlyTrendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
        new Chart(monthlyTrendsCtx, {
            type: 'line',
            data: {
                labels: ['Јан', 'Феб', 'Мар', 'Апр', 'Мај', 'Јун', 'Јул', 'Авг', 'Сеп', 'Окт', 'Ное', 'Дек'],
                datasets: [{
                    label: 'Фактури',
                    data: [<?php echo implode(',', $monthly_data['invoices']); ?>],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Понуди',
                    data: [<?php echo implode(',', $monthly_data['offers']); ?>],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Про Фактури',
                    data: [<?php echo implode(',', $monthly_data['preinvoices']); ?>],
                    borderColor: '#17a2b8',
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Monthly Revenue Chart
        const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
        new Chart(monthlyRevenueCtx, {
            type: 'bar',
            data: {
                labels: ['Јан', 'Феб', 'Мар', 'Апр', 'Мај', 'Јун', 'Јул', 'Авг', 'Сеп', 'Окт', 'Ное', 'Дек'],
                datasets: [{
                    label: 'Приход (<?php echo $currency; ?>)',
                    data: [<?php echo implode(',', $monthly_revenue); ?>],
                    backgroundColor: 'rgba(255, 99, 132, 0.8)',
                    borderColor: '#dc3545',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        }); // End of DOMContentLoaded
    </script>
<?php include 'includes/footer.php'; ?>
</body>
</html> 