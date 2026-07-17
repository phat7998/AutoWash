<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var array<string, mixed> $authUser */
/** @var string|null $flashSuccess */
/** @var list<array<string, mixed>> $booking_status */
/** @var array<string, mixed> $revenue */
/** @var array<string, mixed> $slots */
/** @var list<array<string, mixed>> $tiers */
/** @var list<array<string, mixed>> $points */
/** @var array<string, mixed> $usage */
$booking_status ??= [];
$revenue ??= [];
$slots ??= [];
$tiers ??= [];
$points ??= [];
$usage ??= [];
$statusLabels = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy',
    'no_show' => 'Không đến',
];
$pointLabels = ['earn' => 'Điểm cộng', 'redeem' => 'Điểm đổi', 'expire' => 'Điểm hết hạn'];
$bookingsToday = array_sum(array_map(static fn (array $row): int => (int) $row['total'], $booking_status));
?>
<section class="dashboard-heading">
    <p class="eyebrow dark-eyebrow">Khu vực quản trị</p>
    <h1>Tổng quan vận hành</h1>
    <p class="lead">Theo dõi lịch phục vụ, doanh thu, sức chứa và hoạt động thành viên trong một màn hình.</p>
</section>
<?php if (isset($flashSuccess) && is_string($flashSuccess)) : ?>
    <div class="notification notification-success" role="status">
        <strong>Thành công</strong>
        <span><?= $e($flashSuccess) ?></span>
    </div>
<?php endif; ?>
<section class="report-kpi-grid" aria-label="Chỉ số vận hành chính">
    <article class="summary-card">
        <span>Lịch phục vụ hôm nay</span>
        <strong><?= $e(number_format($bookingsToday, 0, ',', '.')) ?></strong>
        <small>Tổng số lịch theo ngày phục vụ.</small>
    </article>
    <article class="summary-card">
        <span>Doanh thu hoàn thành hôm nay</span>
        <strong><?= $e(number_format((float) ($revenue['today_revenue'] ?? 0), 0, ',', '.')) ?> ₫</strong>
        <small>Chỉ tính các lịch đã hoàn thành.</small>
    </article>
    <article class="summary-card">
        <span>Doanh thu hoàn thành lũy kế</span>
        <strong><?= $e(number_format((float) ($revenue['completed_revenue'] ?? 0), 0, ',', '.')) ?> ₫</strong>
        <small>Tổng thành tiền của các lịch đã hoàn thành.</small>
    </article>
    <article class="summary-card">
        <span>Mức sử dụng sức chứa hôm nay</span>
        <strong><?= $e((int) ($slots['utilization_percent'] ?? 0)) ?>%</strong>
        <small>
            <?= $e((int) ($slots['reserved_capacity'] ?? 0)) ?> /
            <?= $e((int) ($slots['total_capacity'] ?? 0)) ?> đơn vị sức chứa.
        </small>
    </article>
</section>

<section id="bao-cao" class="report-grid" aria-label="Báo cáo vận hành">
    <article class="report-card">
        <h2>Lịch hôm nay theo trạng thái</h2>
        <?php if ($booking_status === []) : ?>
            <p class="muted-text">Chưa có lịch phục vụ hôm nay.</p>
        <?php else : ?>
            <div class="metric-chart">
                <?php foreach ($booking_status as $row) : ?>
                    <div class="metric-bar">
                        <div>
                            <span><?= $e($statusLabels[$row['status']] ?? $row['status']) ?></span>
                            <strong><?= $e($row['total']) ?></strong>
                        </div>
                        <progress max="100" value="<?= $e($row['percent']) ?>"><?= $e($row['percent']) ?>%</progress>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
    <article class="report-card">
        <h2>Phân bố hạng thành viên</h2>
        <div class="metric-chart">
            <?php foreach ($tiers as $tier) : ?>
                <div class="metric-bar">
                    <div><span><?= $e($tier['name']) ?></span><strong><?= $e($tier['total']) ?></strong></div>
                    <progress max="100" value="<?= $e($tier['percent']) ?>"><?= $e($tier['percent']) ?>%</progress>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
    <article class="report-card">
        <h2>Biến động điểm trong tháng</h2>
        <?php if ($points === []) : ?>
            <p class="muted-text">Chưa có thay đổi điểm trong tháng.</p>
        <?php else : ?>
            <dl class="report-definition-list">
                <?php foreach ($points as $point) : ?>
                    <div>
                        <dt><?= $e($pointLabels[$point['type']] ?? $point['type']) ?></dt>
                        <dd><?= $e(number_format((int) $point['points'], 0, ',', '.')) ?> điểm</dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
    </article>
    <article class="report-card">
        <h2>Quyền lợi dùng trong tháng</h2>
        <dl class="report-definition-list">
            <div><dt>Quà tặng đã dùng</dt><dd><?= $e((int) ($usage['rewards_used'] ?? 0)) ?></dd></div>
            <div><dt>Khuyến mãi đã áp dụng</dt><dd><?= $e((int) ($usage['promotions_used'] ?? 0)) ?></dd></div>
        </dl>
        <p class="muted-text">Số liệu tổng hợp từ hoạt động đã được ghi nhận trong tháng.</p>
    </article>
</section>
