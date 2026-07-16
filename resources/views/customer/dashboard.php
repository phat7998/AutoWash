<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var array<string, mixed> $authUser */
/** @var string|null $flashSuccess */
?>
<section class="dashboard-heading">
    <p class="eyebrow dark-eyebrow">Khu vực khách hàng</p>
    <h1>Xin chào, <?= $e($authUser['full_name'] ?? '') ?></h1>
    <p class="lead">Tài khoản của bạn đã sẵn sàng để quản lý hành trình chăm sóc phương tiện.</p>
</section>
<?php if (isset($flashSuccess) && is_string($flashSuccess)): ?>
    <div class="notification notification-success" role="status">
        <strong>Thành công</strong>
        <span><?= $e($flashSuccess) ?></span>
    </div>
<?php endif; ?>
<section class="empty-state" aria-labelledby="customer-next-title">
    <h2 id="customer-next-title">Chưa có phương tiện trong tài khoản</h2>
    <p>
        Tài khoản mới chưa có dữ liệu phương tiện. Bạn vẫn có thể kiểm tra thông tin phiên và đăng xuất an toàn
        từ thanh điều hướng.
    </p>
</section>
