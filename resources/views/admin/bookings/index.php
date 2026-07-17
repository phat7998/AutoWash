<?php

declare(strict_types=1);

use App\Support\VietnameseFormatter;

/** @var Closure(mixed): string $e */
/** @var list<array<string, mixed>> $bookings */
/** @var string $csrfToken */
/** @var string|null $flashSuccess */
/** @var string|null $flashError */
?>
<section class="page-heading">
    <p class="eyebrow dark-eyebrow">Vận hành dịch vụ</p>
    <h1>Quản lý lịch đặt</h1>
    <p class="lead">Xác nhận lịch, ghi nhận hoàn thành hoặc xử lý ngoại lệ theo trạng thái hiện tại.</p>
</section>

<?php if (isset($flashSuccess) && $flashSuccess !== ''): ?>
    <div class="notification notification-success" role="status">
        <strong>Thành công</strong><span><?= $e($flashSuccess) ?></span>
    </div>
<?php endif; ?>
<?php if (isset($flashError) && $flashError !== ''): ?>
    <div class="notification notification-error" role="alert">
        <strong>Không thể cập nhật lịch đặt</strong><span><?= $e($flashError) ?></span>
    </div>
<?php endif; ?>

<?php if ($bookings === []): ?>
    <section class="empty-state">
        <h2>Chưa có lịch đặt</h2>
        <p>Lịch của khách hàng sẽ xuất hiện tại đây sau khi được tạo.</p>
    </section>
<?php else: ?>
    <div class="admin-booking-list">
        <?php foreach ($bookings as $booking): ?>
            <article class="admin-booking-card">
                <div class="card-heading-row">
                    <div>
                        <p class="item-code"><?= $e($booking['booking_code']) ?></p>
                        <h2><?= $e($booking['customer_name']) ?> · <?= $e($booking['display_plate']) ?></h2>
                        <p><?= $e($booking['service_names']) ?></p>
                    </div>
                    <span class="status-badge <?= $e($booking['status_class']) ?>">
                        <?= $e($booking['status_label']) ?>
                    </span>
                </div>
                <dl class="admin-booking-summary">
                    <div><dt>Thời gian</dt><dd><?= $e(VietnameseFormatter::date((string) $booking['slot_date'])) ?> · <?= $e(VietnameseFormatter::time((string) $booking['start_time'])) ?></dd></div>
                    <div><dt>Phương tiện</dt><dd><?= $e($booking['vehicle_type_name']) ?></dd></div>
                    <div><dt>Thành tiền</dt><dd><?= $e(VietnameseFormatter::vnd((string) $booking['final_price'])) ?></dd></div>
                </dl>

                <?php if ($booking['cancellation_reason'] !== null): ?>
                    <p class="muted-text"><strong>Lý do hủy:</strong> <?= $e($booking['cancellation_reason']) ?></p>
                <?php endif; ?>

                <div class="admin-booking-actions">
                    <?php if ((bool) $booking['can_confirm']): ?>
                        <form method="post" action="/admin/lich-dat/<?= $e($booking['id']) ?>/xac-nhan" data-confirm="Xác nhận lịch đặt này?">
                            <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                            <button class="button button-primary button-compact" type="submit">Xác nhận</button>
                        </form>
                    <?php endif; ?>
                    <?php if ((bool) $booking['can_complete']): ?>
                        <form method="post" action="/admin/lich-dat/<?= $e($booking['id']) ?>/hoan-thanh" data-confirm="Ghi nhận lịch đã hoàn thành? Điểm và quyền lợi sẽ được xử lý theo quy định.">
                            <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                            <button class="button button-primary button-compact" type="submit">Hoàn thành</button>
                        </form>
                    <?php endif; ?>
                    <?php if ((bool) $booking['can_no_show']): ?>
                        <form method="post" action="/admin/lich-dat/<?= $e($booking['id']) ?>/khong-den" data-confirm="Xác nhận khách không đến lịch này?">
                            <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                            <button class="button button-outline button-compact" type="submit">Không đến</button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if ((bool) $booking['can_cancel']): ?>
                    <form class="admin-cancel-form" method="post" action="/admin/lich-dat/<?= $e($booking['id']) ?>/huy" data-confirm="Hủy lịch đặt này và trả lại sức chứa đã giữ?">
                        <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                        <div class="form-field">
                            <label for="reason-<?= $e($booking['id']) ?>">Lý do hủy lịch</label>
                            <input id="reason-<?= $e($booking['id']) ?>" name="cancellation_reason" maxlength="500" required>
                        </div>
                        <button class="button button-danger button-compact" type="submit">Hủy lịch</button>
                    </form>
                <?php elseif (!(bool) $booking['can_confirm'] && !(bool) $booking['can_complete'] && !(bool) $booking['can_no_show']): ?>
                    <p class="muted-text terminal-state-text">Lịch đã kết thúc, không còn thao tác khả dụng.</p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
