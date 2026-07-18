<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $revenue */
/** @var list<array<string, mixed>> $booking_status */
/** @var list<array<string, mixed>> $vehicle_types */
/** @var list<array<string, mixed>> $services */
/** @var list<array<string, mixed>> $tiers */
/** @var array<string, mixed> $points */
/** @var array<string, mixed> $usage */
$errors ??= [];
$revenue ??= [];
$booking_status ??= [];
$vehicle_types ??= [];
$services ??= [];
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
$bookingsInRange = array_sum(array_map(
    static fn (array $row): int => (int) $row['total'],
    $booking_status
));
?>
<section class="page-header">
    <div>
        <p class="eyebrow dark-eyebrow">Phân tích</p>
        <h1>Báo cáo vận hành</h1>
        <p class="lead">Theo dõi doanh thu, lịch phục vụ và hoạt động thành viên theo khoảng ngày.</p>
    </div>
</section>

<?php if ($errors !== []) : ?>
    <div class="notification notification-error" role="alert">
        <strong>Khoảng thời gian chưa hợp lệ</strong>
        <ul class="error-list">
            <?php foreach (array_unique($errors) as $message) : ?>
                <li><?= $e($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="filter-bar" method="get" action="/admin/bao-cao">
    <div class="form-field filter-field">
        <label for="from_date">Từ ngày</label>
        <input id="from_date" type="date" name="from_date" value="<?= $e($from_date ?? '') ?>" required>
    </div>
    <div class="form-field filter-field">
        <label for="to_date">Đến ngày</label>
        <input id="to_date" type="date" name="to_date" value="<?= $e($to_date ?? '') ?>" required>
    </div>
    <button class="button button-primary" type="submit">Xem báo cáo</button>
</form>

<section class="report-kpi-grid" aria-label="Chỉ số doanh thu và lịch đặt">
    <article class="summary-card">
        <span>Tổng doanh thu đã hoàn thành</span>
        <strong><?= $e(number_format((float) ($revenue['completed_revenue'] ?? 0), 0, ',', '.')) ?>&nbsp;₫</strong>
        <small>Lũy kế từ toàn bộ lịch đặt đã hoàn thành.</small>
    </article>
    <article class="summary-card">
        <span>Doanh thu hôm nay</span>
        <strong><?= $e(number_format((float) ($revenue['today_revenue'] ?? 0), 0, ',', '.')) ?>&nbsp;₫</strong>
        <small>Chỉ tính lịch đặt hoàn thành trong hôm nay.</small>
    </article>
    <article class="summary-card">
        <span>Doanh thu trong khoảng</span>
        <strong><?= $e(number_format((float) ($revenue['range_revenue'] ?? 0), 0, ',', '.')) ?>&nbsp;₫</strong>
        <small>Tính theo thời điểm hoàn thành lịch đặt.</small>
    </article>
    <article class="summary-card">
        <span>Lịch phục vụ trong khoảng</span>
        <strong><?= $e(number_format($bookingsInRange, 0, ',', '.')) ?></strong>
        <small>Tính theo ngày của khung giờ phục vụ.</small>
    </article>
</section>

<section class="report-grid" aria-label="Báo cáo lịch đặt">
    <article class="report-card">
        <h2>Lịch đặt theo trạng thái</h2>
        <?php if ($booking_status === []) : ?>
            <p class="muted-text">Không có lịch đặt trong khoảng đã chọn.</p>
        <?php else : ?>
            <div class="metric-chart">
                <?php foreach ($booking_status as $row) : ?>
                    <div class="metric-bar">
                        <div>
                            <span><?= $e($statusLabels[$row['status']] ?? $row['status']) ?></span>
                            <strong><?= $e($row['total']) ?></strong>
                        </div>
                        <progress max="100" value="<?= $e($row['percent']) ?>">
                            <?= $e($row['percent']) ?>%
                        </progress>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="report-card">
        <h2>Lịch đặt theo loại xe</h2>
        <?php if ($vehicle_types === []) : ?>
            <p class="muted-text">Không có dữ liệu loại xe trong khoảng đã chọn.</p>
        <?php else : ?>
            <div class="metric-chart">
                <?php foreach ($vehicle_types as $vehicleType) : ?>
                    <div class="metric-bar">
                        <div>
                            <span><?= $e($vehicleType['name']) ?></span>
                            <strong><?= $e($vehicleType['total']) ?></strong>
                        </div>
                        <progress max="100" value="<?= $e($vehicleType['percent']) ?>">
                            <?= $e($vehicleType['percent']) ?>%
                        </progress>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="report-card">
        <h2>Lịch đặt theo dịch vụ</h2>
        <?php if ($services === []) : ?>
            <p class="muted-text">Không có dữ liệu dịch vụ trong khoảng đã chọn.</p>
        <?php else : ?>
            <div class="metric-chart">
                <?php foreach ($services as $service) : ?>
                    <div class="metric-bar">
                        <div>
                            <span><?= $e($service['name']) ?></span>
                            <strong><?= $e($service['total']) ?></strong>
                        </div>
                        <progress max="100" value="<?= $e($service['percent']) ?>"><?= $e($service['percent']) ?>%</progress>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="report-card">
        <h2>Phân bố hạng hiện tại</h2>
        <?php if ($tiers === []) : ?>
            <p class="muted-text">Chưa có cấu hình hạng thành viên.</p>
        <?php else : ?>
            <div class="metric-chart">
                <?php foreach ($tiers as $tier) : ?>
                    <div class="metric-bar">
                        <div><span><?= $e($tier['name']) ?></span><strong><?= $e($tier['total']) ?></strong></div>
                        <progress max="100" value="<?= $e($tier['percent']) ?>"><?= $e($tier['percent']) ?>%</progress>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>

<section class="report-grid" aria-label="Báo cáo điểm và quyền lợi">
    <article class="report-card">
        <h2>Biến động điểm trong khoảng</h2>
        <dl class="report-definition-list">
            <div>
                <dt>Điểm đã cộng</dt>
                <dd><?= $e(number_format((int) ($points['points_added'] ?? 0), 0, ',', '.')) ?> điểm</dd>
            </div>
            <div>
                <dt>Điểm đã trừ</dt>
                <dd><?= $e(number_format((int) ($points['points_deducted'] ?? 0), 0, ',', '.')) ?> điểm</dd>
            </div>
        </dl>
    </article>

    <article class="report-card">
        <h2>Quyền lợi trong khoảng</h2>
        <dl class="report-definition-list">
            <div><dt>Quà tặng đã dùng</dt><dd><?= $e((int) ($usage['rewards_used'] ?? 0)) ?></dd></div>
            <div><dt>Khuyến mãi đã áp dụng</dt><dd><?= $e((int) ($usage['promotions_used'] ?? 0)) ?></dd></div>
        </dl>
    </article>
</section>
