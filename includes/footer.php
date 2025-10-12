<?php
// Professional Footer Component
if (!isset($company)) {
    $company = getCompanySettings($pdo);
}
?>
<footer class="bg-primary text-white py-4 mt-5 no-print">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-building"></i> <?php echo htmlspecialchars($company['company_name'] ?? 'Фактури и Понуди'); ?>
                </h6>
                <?php if (!empty($company['address'])): ?>
                    <p class="mb-2 small">
                        <i class="bi bi-geo-alt"></i> <?php echo nl2br(htmlspecialchars($company['address'])); ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-telephone"></i> Контакт
                </h6>
                <?php if (!empty($company['email'])): ?>
                    <p class="mb-2 small">
                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($company['email']); ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($company['phone'])): ?>
                    <p class="mb-2 small">
                        <i class="bi bi-phone"></i> <?php echo htmlspecialchars($company['phone']); ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-info-circle"></i> Информации
                </h6>
                <p class="mb-2 small">
                    <i class="bi bi-shield-check"></i> Сигурен систем за фактурирање
                </p>
                <p class="mb-2 small">
                    <i class="bi bi-code-slash"></i> Направено со PHP & MySQL
                </p>
            </div>
        </div>
        <hr class="my-3" style="border-color: rgba(255,255,255,0.2);">
        <div class="row">
            <div class="col-12 text-center">
                <p class="mb-0 small">
                    © <?php echo date('Y'); ?> <?php echo htmlspecialchars($company['company_name'] ?? 'Фактури и Понуди'); ?>. Сите права се задржани.
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
