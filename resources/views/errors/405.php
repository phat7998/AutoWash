<?php declare(strict_types=1); $backUrl = ($authUser['role'] ?? null) === 'admin' ? '/admin' : (($authUser['role'] ?? null) === 'customer' ? '/tai-khoan' : '/'); ?>
<section class="error-state" aria-labelledby="error-title">
    <p class="error-code">405</p>
    <h1 id="error-title"><?= $e($title) ?></h1>
    <p>Thao tác này không thể thực hiện theo cách vừa gửi. Vui lòng quay lại và thử từ nút chức năng trên trang.</p>
    <a class="button button-primary" href="<?= $e($backUrl) ?>">Quay lại khu vực chính</a>
</section>
