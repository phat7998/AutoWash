<?php declare(strict_types=1); $backUrl = ($authUser['role'] ?? null) === 'admin' ? '/admin' : (($authUser['role'] ?? null) === 'customer' ? '/tai-khoan' : '/'); ?>
<section class="error-state" aria-labelledby="error-title">
    <p class="error-code">404</p>
    <h1 id="error-title"><?= $e($title) ?></h1>
    <p><?= $e($message) ?></p>
    <p class="muted-text">Đường dẫn có thể đã thay đổi hoặc nội dung không còn tồn tại.</p>
    <a class="button button-primary" href="<?= $e($backUrl) ?>">Quay lại khu vực chính</a>
</section>
