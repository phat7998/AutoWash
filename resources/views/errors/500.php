<?php declare(strict_types=1); $backUrl = ($authUser['role'] ?? null) === 'admin' ? '/admin' : (($authUser['role'] ?? null) === 'customer' ? '/tai-khoan' : '/'); ?>
<section class="error-state" aria-labelledby="error-title">
    <p class="error-code">500</p>
    <h1 id="error-title"><?= $e($title) ?></h1>
    <p>Hệ thống chưa thể hoàn tất yêu cầu. Vui lòng thử lại sau ít phút.</p>
    <p class="request-id">Mã yêu cầu: <code><?= $e($requestId) ?></code></p>
    <?php if (is_string($debugMessage) && $debugMessage !== ''): ?>
        <details class="debug-details">
            <summary>Chi tiết dành cho môi trường phát triển</summary>
            <pre><?= $e($debugMessage) ?></pre>
        </details>
    <?php endif; ?>
    <a class="button button-primary" href="<?= $e($backUrl) ?>">Quay lại khu vực chính</a>
</section>
