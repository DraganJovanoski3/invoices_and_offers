<?php
// Professional Navbar Component
if (!isset($company)) {
    $company = getCompanySettings($pdo);
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary no-print">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <?php if (!empty($company['logo_path']) && file_exists($company['logo_path'])): ?>
                <img src="<?php echo htmlspecialchars($company['logo_path']); ?>" alt="<?php echo htmlspecialchars($company['company_name']); ?> Logo" style="height:32px; width:auto; margin-right:10px;">
            <?php endif; ?>
            <?php echo htmlspecialchars($company['company_name'] ?? 'Фактури и Понуди'); ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="bi bi-house-door"></i> Почетна
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'invoices.php') ? 'active' : ''; ?>" href="invoices.php">
                        <i class="bi bi-receipt"></i> Фактури
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'offers.php') ? 'active' : ''; ?>" href="offers.php">
                        <i class="bi bi-file-earmark-text"></i> Понуди
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'preinvoices.php') ? 'active' : ''; ?>" href="preinvoices.php">
                        <i class="bi bi-file-earmark-check"></i> Про Фактури
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'clients.php') ? 'active' : ''; ?>" href="clients.php">
                        <i class="bi bi-people"></i> Клиенти
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'services.php') ? 'active' : ''; ?>" href="services.php">
                        <i class="bi bi-gear"></i> Услуги
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'company_settings.php') ? 'active' : ''; ?>" href="company_settings.php">
                        <i class="bi bi-sliders"></i> Поставки
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'analytics.php') ? 'active' : ''; ?>" href="analytics.php">
                        <i class="bi bi-graph-up"></i> Аналитика
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="company_settings.php"><i class="bi bi-gear"></i> Поставки</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Одјава</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
