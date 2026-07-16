<?php declare(strict_types=1); ?>
<section class="error-state" aria-labelledby="error-title">
    <p class="error-code">500</p>
    <h1 id="error-title"><?= $e($title) ?></h1>
    <p><?= $e($message) ?></p>
    <p class="request-id">Mã yêu cầu: <code><?= $e($requestId) ?></code></p>
    <?php if (is_string($debugMessage) && $debugMessage !== ''): ?>
        <details class="debug-details">
            <summary>Chi tiết dành cho môi trường phát triển</summary>
            <pre><?= $e($debugMessage) ?></pre>
        </details>
    <?php endif; ?>
    <a class="button button-primary" href="/">Thử lại từ trang chủ</a>
</section>
