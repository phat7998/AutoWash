<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var array<string, mixed> $authUser */
/** @var string|null $flashSuccess */
/** @var array<string, mixed>|null $summary */
/** @var list<array<string, mixed>> $recent_transactions */
/** @var array<string, mixed>|null $latest_booking */
/** @var list<array<string, mixed>> $wash_history */
/** @var int $available_rewards */
$bookingStatusLabels = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy',
    'no_show' => 'Không đến',
];
?>
<section class="dashboard-heading">
    <p class="eyebrow dark-eyebrow">Khu vực khách hàng</p>
    <h1>Xin chào, <?= $e($authUser['full_name'] ?? '') ?></h1>
    <p class="lead">Theo dõi hạng thành viên, điểm thưởng và hành trình chăm sóc phương tiện.</p>
</section>
<?php if (isset($flashSuccess) && is_string($flashSuccess)) : ?>
    <div class="notification notification-success" role="status">
        <strong>Thành công</strong>
        <span><?= $e($flashSuccess) ?></span>
    </div>
<?php endif; ?>
<?php if (is_array($summary ?? null)) : ?>
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
            <small>Ưu tiên sử dụng số điểm này trước thời hạn.</small>
        </article>
    </section>
<?php endif; ?>
<section class="report-grid customer-report-grid" aria-label="Hoạt động gần đây của tài khoản">
    <article class="report-card">
        <h2>Lịch đặt gần nhất</h2>
        <?php if (is_array($latest_booking ?? null)) : ?>
            <dl class="report-definition-list">
                <div><dt>Mã lịch</dt><dd><?= $e($latest_booking['code']) ?></dd></div>
                <div>
                    <dt>Ngày</dt>
                    <dd>
                        <?= $e($latest_booking['slot_date']) ?> ·
                        <?= $e(substr((string) $latest_booking['start_time'], 0, 5)) ?>
                    </dd>
                </div>
                <div>
                    <dt>Trạng thái</dt>
                    <dd><?= $e($bookingStatusLabels[$latest_booking['status']] ?? $latest_booking['status']) ?></dd>
                </div>
                <div>
                    <dt>Thành tiền</dt>
                    <dd><?= $e(number_format((float) $latest_booking['final_price'], 0, ',', '.')) ?> ₫</dd>
                </div>
            </dl>
        <?php else : ?>
            <div class="empty-state compact-empty"><h3>Chưa có lịch hẹn</h3><p>Đặt lịch đầu tiên để chủ động thời gian chăm sóc xe.</p><a href="/dat-lich">Đặt lịch ngay</a></div>
        <?php endif; ?>
    </article>
    <article class="report-card">
        <h2>Lịch sử chăm sóc gần đây</h2>
        <?php if (($wash_history ?? []) === []) : ?>
            <p class="muted-text">Lịch sử chăm sóc sẽ xuất hiện sau khi dịch vụ hoàn thành.</p>
        <?php else : ?>
            <ul class="compact-history-list">
                <?php foreach ($wash_history as $booking) : ?>
                    <li>
                        <strong><?= $e($booking['code']) ?></strong>
                        <span><?= $e($booking['services']) ?></span>
                        <small>
                            <?= $e($booking['completed_at']) ?> ·
                            <?= $e(number_format((float) $booking['final_price'], 0, ',', '.')) ?> ₫
                        </small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
    <article class="report-card">
        <h2>Quà tặng đang có</h2>
        <strong class="report-highlight"><?= $e((int) ($available_rewards ?? 0)) ?></strong>
        <p class="muted-text">Quà còn hạn và sẵn sàng dùng cho lịch đặt phù hợp.</p>
        <a class="button button-outline" href="/doi-thuong">Xem quà tặng</a>
    </article>
</section>
<section class="empty-state dashboard-next-action" aria-labelledby="customer-next-title">
    <h2 id="customer-next-title">Chăm sóc phương tiện tiếp theo</h2>
    <p>Chọn xe, dịch vụ và khung giờ phù hợp để tạo lịch đặt mới.</p>
    <div class="form-actions">
        <a class="button button-primary" href="/dat-lich">Đặt lịch rửa xe</a>
        <a class="button button-outline" href="/phuong-tien">Xem phương tiện</a>
        <a class="button button-outline" href="/diem-thuong">Xem lịch sử điểm</a>
    </div>
</section>
