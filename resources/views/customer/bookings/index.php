<?php

declare(strict_types=1);

use App\Support\VietnameseFormatter;

/** @var Closure(mixed): string $e */
/** @var list<array<string, mixed>> $bookings */
/** @var list<array<string, mixed>> $history */
/** @var string|null $flashSuccess */
/** @var string|null $flashError */
?>
<section class="page-heading page-heading-actions">
    <div>
        <p class="eyebrow dark-eyebrow">Khu vực khách hàng</p>
        <h1>Lịch đặt của tôi</h1>
        <p class="lead">Theo dõi trạng thái phục vụ và xem lại dịch vụ đã hoàn thành.</p>
    </div>
    <a class="button button-primary" href="/dat-lich">Đặt lịch mới</a>
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

<section class="booking-section" aria-labelledby="booking-list-title">
    <div class="section-heading">
        <h2 id="booking-list-title">Lịch đặt và trạng thái phục vụ</h2>
        <p>Các lịch chờ xác nhận, đã xác nhận, đã hủy hoặc không đến.</p>
    </div>
    <?php if ($bookings === []): ?>
        <div class="empty-state compact-empty">
            <h2>Bạn chưa có lịch đặt đang theo dõi</h2>
            <p>Chọn phương tiện, dịch vụ và khung giờ để tạo lịch đầu tiên.</p>
            <a class="button button-primary" href="/dat-lich">Đặt lịch ngay</a>
        </div>
    <?php else: ?>
        <div class="booking-card-grid">
            <?php foreach ($bookings as $booking): ?>
                <article class="booking-card">
                    <div class="card-heading-row">
                        <div>
                            <p class="item-code"><?= $e($booking['booking_code']) ?></p>
                            <h2><?= $e($booking['display_plate']) ?> · <?= $e($booking['vehicle_type_name']) ?></h2>
                        </div>
                        <span class="status-badge <?= $e($booking['status_class']) ?>">
                            <?= $e($booking['status_label']) ?>
                        </span>
                    </div>
                    <p class="booking-services"><?= $e($booking['service_names']) ?></p>
                    <dl class="detail-list">
                        <div><dt>Ngày</dt><dd><?= $e(VietnameseFormatter::date((string) $booking['slot_date'])) ?></dd></div>
                        <div><dt>Bắt đầu</dt><dd><?= $e(VietnameseFormatter::time((string) $booking['start_time'])) ?></dd></div>
                        <div><dt>Thành tiền</dt><dd><?= $e(VietnameseFormatter::vnd((string) $booking['final_price'])) ?></dd></div>
                    </dl>
                    <div class="booking-card-actions">
                        <a class="button button-outline" href="/lich-dat/<?= $e($booking['id']) ?>">
                            Xem chi tiết
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="booking-section" aria-labelledby="wash-history-title">
    <div class="section-heading">
        <h2 id="wash-history-title">Lịch sử rửa xe</h2>
        <p>Các lần sử dụng đã hoàn thành được lưu theo thông tin tại thời điểm đặt lịch.</p>
    </div>
    <?php if ($history === []): ?>
        <div class="empty-state compact-empty">
            <h2>Chưa có lần rửa xe hoàn thành</h2>
            <p>Lịch sử sẽ xuất hiện sau khi quản trị viên ghi nhận hoàn thành dịch vụ.</p>
        </div>
    <?php else: ?>
        <div class="table-shell">
            <table>
                <caption>Các lần sử dụng dịch vụ đã hoàn thành</caption>
                <thead>
                    <tr><th scope="col">Mã lịch</th><th scope="col">Phương tiện</th><th scope="col">Dịch vụ</th><th scope="col">Ngày rửa</th><th scope="col">Thành tiền</th><th scope="col">Thao tác</th></tr>
                </thead>
                <tbody>
                <?php foreach ($history as $booking): ?>
                    <tr>
                        <td data-label="Mã lịch"><strong><?= $e($booking['booking_code']) ?></strong></td>
                        <td data-label="Phương tiện"><?= $e($booking['display_plate']) ?></td>
                        <td data-label="Dịch vụ"><?= $e($booking['service_names']) ?></td>
                        <td data-label="Ngày rửa"><?= $e(VietnameseFormatter::date((string) $booking['slot_date'])) ?></td>
                        <td data-label="Thành tiền"><?= $e(VietnameseFormatter::vnd((string) $booking['final_price'])) ?></td>
                        <td data-label="Thao tác"><a href="/lich-dat/<?= $e($booking['id']) ?>">Xem chi tiết</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
