<?php
// Common Header Component
if (!isset($company)) {
    $company = getCompanySettings($pdo);
}
?>
<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo htmlspecialchars($company['company_name'] ?? 'Фактури и Понуди'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <style>
        .navbar-brand {
            font-weight: bold;
        }
        .main-content {
            min-height: calc(100vh - 200px);
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transition: box-shadow 0.15s ease-in-out;
        }
        .btn {
            border-radius: 0.375rem;
        }
        .table {
            border-radius: 0.375rem;
            overflow: hidden;
        }
        .no-print {
            @media print {
                display: none !important;
            }
        }
        
        /* Print-specific styles */
        @media print {
            /* Hide navbar and footer */
            .navbar,
            .navbar *,
            footer,
            footer * {
                display: none !important;
            }
            
            /* Hide print buttons and navigation */
            .no-print,
            .btn,
            .btn * {
                display: none !important;
            }
            
            /* Ensure content takes full width */
            .container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* Remove margins and padding for clean print */
            body {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
            }
            
            /* Ensure text is black for printing */
            * {
                color: black !important;
                background: white !important;
            }
            
            /* Page breaks */
            .page-break {
                page-break-before: always;
            }
        }
        .invoice-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #007bff;
        }
        .client-info {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 0px 30px 30px 30px;
            font-size: 10pt;
        }
        .items-table {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 0px 30px 30px 30px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .revenue-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .success-card {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .warning-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .info-card {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
    </style>
</head>
<body>
