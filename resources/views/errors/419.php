<?php declare(strict_types=1); $backUrl = ($authUser['role'] ?? null) === 'admin' ? '/admin' : (($authUser['role'] ?? null) === 'customer' ? '/tai-khoan' : '/'); ?>
<section class="error-state" aria-labelledby="error-title">
    <p class="error-code">419</p>
    <h1 id="error-title"><?= $e($title) ?></h1>
    <p>Phiên thao tác đã hết hạn để bảo vệ tài khoản. Vui lòng tải lại trang và thực hiện lại.</p>
    <a class="button button-primary" href="<?= $e($backUrl) ?>">Tải lại từ khu vực chính</a>
</section>
