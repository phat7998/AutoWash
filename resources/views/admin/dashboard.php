<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var array<string, mixed> $authUser */
/** @var string|null $flashSuccess */
?>
<section class="dashboard-heading">
    <p class="eyebrow dark-eyebrow">Khu vực quản trị</p>
    <h1>Xin chào, <?= $e($authUser['full_name'] ?? '') ?></h1>
    <p class="lead">Phiên quản trị đã được xác thực và kiểm tra vai trò ở backend.</p>
</section>
<?php if (isset($flashSuccess) && is_string($flashSuccess)): ?>
    <div class="notification notification-success" role="status">
        <strong>Thành công</strong>
        <span><?= $e($flashSuccess) ?></span>
    </div>
<?php endif; ?>
<section class="empty-state" aria-labelledby="admin-next-title">
    <h2 id="admin-next-title">Chưa có dữ liệu vận hành để tổng hợp</h2>
    <p>
        Chưa có booking hoặc dữ liệu dịch vụ phát sinh. Hệ thống không hiển thị số liệu giả; khu vực này chỉ dành
        cho tài khoản quản trị đã được xác thực.
    </p>
</section>
