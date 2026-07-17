<?php declare(strict_types=1); $backUrl = ($authUser['role'] ?? null) === 'admin' ? '/admin' : (($authUser['role'] ?? null) === 'customer' ? '/tai-khoan' : '/'); ?>
<section class="error-state" aria-labelledby="error-title">
    <p class="error-code">403</p>
    <h1 id="error-title"><?= $e($title) ?></h1>
    <p>Bạn đang ở ngoài khu vực được cấp quyền. Hãy quay lại trang tổng quan phù hợp với tài khoản.</p>
    <a class="button button-primary" href="<?= $e($backUrl) ?>">Về trang tổng quan</a>
</section>
