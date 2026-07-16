<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var array<string, mixed> $authUser */
/** @var string|null $flashSuccess */
/** @var array<string, mixed>|null $summary */
/** @var list<array<string, mixed>> $recent_transactions */
?>
<section class="dashboard-heading">
    <p class="eyebrow dark-eyebrow">Khu vực khách hàng</p>
    <h1>Xin chào, <?= $e($authUser['full_name'] ?? '') ?></h1>
    <p class="lead">Theo dõi hạng thành viên, điểm thưởng và hành trình chăm sóc phương tiện.</p>
</section>
<?php if (isset($flashSuccess) && is_string($flashSuccess)): ?>
    <div class="notification notification-success" role="status">
        <strong>Thành công</strong>
        <span><?= $e($flashSuccess) ?></span>
    </div>
<?php endif; ?>
<?php if (is_array($summary ?? null)): ?>
    <section class="loyalty-summary-grid" aria-label="Tổng quan khách hàng thân thiết">
        <article class="summary-card">
            <span>Hạng hiện tại</span>
            <strong><?= $e($summary['tier_name']) ?></strong>
            <small>Hệ số tích điểm <?= $e($summary['point_rate']) ?>.</small>
        </article>
        <article class="summary-card">
            <span>Điểm khả dụng</span>
            <strong><?= $e(number_format((int) $summary['point_balance'], 0, ',', '.')) ?> điểm</strong>
            <small><a href="/diem-thuong">Xem sổ giao dịch</a></small>
        </article>
        <article class="summary-card">
            <span>Sắp hết hạn trong 30 ngày</span>
            <strong><?= $e(number_format((int) $summary['expiring_points_30_days'], 0, ',', '.')) ?> điểm</strong>
            <small>Chỉ tính các lô earn còn khả dụng.</small>
        </article>
    </section>
<?php endif; ?>
<section class="empty-state dashboard-next-action" aria-labelledby="customer-next-title">
    <h2 id="customer-next-title">Chăm sóc phương tiện tiếp theo</h2>
    <p>Chọn xe, dịch vụ và khung giờ phù hợp để tạo lịch đặt mới.</p>
    <div class="form-actions">
        <a class="button button-primary" href="/dat-lich">Đặt lịch rửa xe</a>
        <a class="button button-outline" href="/phuong-tien">Xem phương tiện</a>
        <a class="button button-outline" href="/diem-thuong">Xem lịch sử điểm</a>
    </div>
</section>
