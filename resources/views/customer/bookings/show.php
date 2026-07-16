<?php

declare(strict_types=1);

use App\Support\VietnameseFormatter;

/** @var Closure(mixed): string $e */
/** @var array<string, mixed> $booking */
/** @var string $csrfToken */
/** @var string|null $flashSuccess */
/** @var string|null $flashError */
?>
<section class="page-heading page-heading-actions">
    <div>
        <p class="eyebrow dark-eyebrow">Chi tiết lịch đặt</p>
        <h1><?= $e($booking['booking_code']) ?></h1>
        <p class="lead"><?= $e($booking['display_plate']) ?> · <?= $e($booking['vehicle_type_name']) ?></p>
    </div>
    <span class="status-badge <?= $e($booking['status_class']) ?>"><?= $e($booking['status_label']) ?></span>
</section>

<?php if (isset($flashSuccess) && $flashSuccess !== ''): ?>
    <div class="notification notification-success" role="status">
        <strong>Thành công</strong><span><?= $e($flashSuccess) ?></span>
    </div>
<?php endif; ?>
<?php if (isset($flashError) && $flashError !== ''): ?>
    <div class="notification notification-error" role="alert">
        <strong>Chưa thể cập nhật lịch đặt</strong><span><?= $e($flashError) ?></span>
    </div>
<?php endif; ?>

<div class="booking-detail-grid">
    <section class="card" aria-labelledby="booking-time-title">
        <h2 id="booking-time-title">Thời gian và sức chứa</h2>
        <dl class="detail-list">
            <div><dt>Ngày phục vụ</dt><dd><?= $e(VietnameseFormatter::date((string) $booking['slot_date'])) ?></dd></div>
            <div><dt>Giờ bắt đầu</dt><dd><?= $e(VietnameseFormatter::time((string) $booking['start_time'])) ?></dd></div>
            <div><dt>Tổng thời lượng</dt><dd><?= $e($booking['booking_duration_minutes']) ?> phút</dd></div>
            <div><dt>Capacity giữ chỗ</dt><dd><?= $e($booking['booking_capacity_units']) ?> units</dd></div>
        </dl>
    </section>
    <section class="card" aria-labelledby="booking-price-title">
        <h2 id="booking-price-title">Chi tiết thanh toán</h2>
        <dl class="detail-list">
            <div><dt>Tạm tính</dt><dd><?= $e(VietnameseFormatter::vnd((string) $booking['subtotal'])) ?></dd></div>
            <div><dt>Giảm hạng</dt><dd><?= $e(VietnameseFormatter::vnd((string) $booking['perk_discount'])) ?></dd></div>
            <div><dt>Khuyến mãi</dt><dd><?= $e(VietnameseFormatter::vnd((string) $booking['promotion_discount'])) ?></dd></div>
            <div><dt>Đổi thưởng</dt><dd><?= $e(VietnameseFormatter::vnd((string) $booking['reward_discount'])) ?></dd></div>
            <div><dt>Thành tiền</dt><dd><strong><?= $e(VietnameseFormatter::vnd((string) $booking['final_price'])) ?></strong></dd></div>
        </dl>
    </section>
</div>

<section class="booking-section" aria-labelledby="booking-items-title">
    <div class="section-heading">
        <h2 id="booking-items-title">Dịch vụ đã đặt</h2>
        <p>Thông tin dưới đây là snapshot, không thay đổi khi cấu hình dịch vụ được cập nhật.</p>
    </div>
    <div class="table-shell">
        <table>
            <caption>Danh sách dịch vụ trong lịch đặt</caption>
            <thead><tr><th scope="col">Dịch vụ</th><th scope="col">Đơn giá</th><th scope="col">Thời lượng</th><th scope="col">Capacity</th><th scope="col">Thành tiền</th></tr></thead>
            <tbody>
            <?php foreach ($booking['items'] as $item): ?>
                <tr>
                    <td data-label="Dịch vụ"><?= $e($item['service_name_snapshot']) ?></td>
                    <td data-label="Đơn giá"><?= $e(VietnameseFormatter::vnd((string) $item['unit_price_snapshot'])) ?></td>
                    <td data-label="Thời lượng"><?= $e($item['duration_minutes_snapshot']) ?> phút</td>
                    <td data-label="Capacity"><?= $e($item['capacity_units_snapshot']) ?> units</td>
                    <td data-label="Thành tiền"><?= $e(VietnameseFormatter::vnd((string) $item['line_total'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($booking['status'] === 'cancelled' && $booking['cancellation_reason'] !== null): ?>
    <div class="notification notification-error" role="status">
        <strong>Lý do hủy</strong><span><?= $e($booking['cancellation_reason']) ?></span>
    </div>
<?php elseif (in_array($booking['status'], ['pending', 'confirmed'], true)): ?>
    <section class="booking-cancel-panel" aria-labelledby="booking-cancel-title">
        <div>
            <h2 id="booking-cancel-title">Hủy lịch đặt</h2>
            <p>
                <?= (bool) $booking['can_cancel_customer']
                    ? 'Bạn có thể hủy khi còn ít nhất 2 giờ trước giờ bắt đầu.'
                    : 'Đã qua thời hạn tự hủy 2 giờ. Vui lòng liên hệ quản trị viên nếu cần hỗ trợ.' ?>
            </p>
        </div>
        <?php if ((bool) $booking['can_cancel_customer']): ?>
            <form method="post" action="/lich-dat/<?= $e($booking['id']) ?>/huy">
                <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                <button class="button button-danger" type="submit">Hủy lịch đặt</button>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>

<div class="form-actions booking-back-actions">
    <a class="button button-outline" href="/lich-dat">Quay lại danh sách</a>
</div>
