<?php

declare(strict_types=1);

use App\Support\VietnameseFormatter;

/** @var Closure(mixed): string $e */
/** @var list<array<string, mixed>> $vehicles */
/** @var array<string, mixed>|null $selected_vehicle */
/** @var list<array<string, mixed>> $services */
/** @var list<array<string, mixed>> $slots */
/** @var string $selectedVehicleId */
/** @var string $selectedSlotId */
/** @var list<string> $selectedServiceIds */
/** @var array<string, string> $errors */
/** @var string $csrfToken */
/** @var string|null $flashSuccess */
?>
<section class="page-heading">
    <p class="eyebrow dark-eyebrow">Đặt lịch trước</p>
    <h1>Đặt lịch rửa xe</h1>
    <p class="lead">
        Giá, thời lượng, sức chứa và giới hạn đặt trước được hệ thống tải lại từ cấu hình khi xác nhận.
    </p>
</section>

<?php if (isset($flashSuccess) && $flashSuccess !== ''): ?>
    <div class="notification notification-success" role="status">
        <strong>Đặt lịch thành công</strong>
        <span><?= $e($flashSuccess) ?></span>
    </div>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <section class="notification notification-error" role="alert" aria-labelledby="booking-error-title">
        <strong id="booking-error-title">Chưa thể tạo lịch đặt</strong>
        <ul class="error-list">
            <?php foreach (array_unique($errors) as $error): ?>
                <li><?= $e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<?php if ($vehicles === []): ?>
    <section class="empty-state" aria-labelledby="booking-no-vehicle-title">
        <h2 id="booking-no-vehicle-title">Bạn chưa có phương tiện đang hoạt động</h2>
        <p>Hãy thêm phương tiện trước khi chọn dịch vụ và khung giờ.</p>
        <a class="button button-primary" href="/phuong-tien/them">Thêm phương tiện</a>
    </section>
<?php else: ?>
    <ol class="booking-steps" aria-label="Các bước đặt lịch">
        <li class="is-active">1. Chọn xe</li>
        <li class="is-active">2. Chọn dịch vụ</li>
        <li class="is-active">3. Chọn khung giờ</li>
        <li>4. Xác nhận</li>
    </ol>

    <form class="filter-bar booking-vehicle-filter" method="get" action="/dat-lich">
        <div class="form-field filter-field">
            <label for="vehicle_id_filter">Phương tiện</label>
            <select id="vehicle_id_filter" name="vehicle_id" required>
                <?php foreach ($vehicles as $vehicle): ?>
                    <option value="<?= $e($vehicle['id']) ?>"
                        <?= (string) $vehicle['id'] === $selectedVehicleId ? 'selected' : '' ?>>
                        <?= $e($vehicle['display_plate']) ?> · <?= $e($vehicle['vehicle_type_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['vehicle_id'])): ?>
                <span class="field-error" role="alert"><?= $e($errors['vehicle_id']) ?></span>
            <?php endif; ?>
        </div>
        <button class="button button-outline" type="submit">Xem dịch vụ phù hợp</button>
    </form>

    <?php if (is_array($selected_vehicle)): ?>
        <section class="booking-context card" aria-labelledby="booking-context-title">
            <div>
                <p class="item-code">Phương tiện đã chọn</p>
                <h2 id="booking-context-title"><?= $e($selected_vehicle['display_plate']) ?></h2>
                <p><?= $e($selected_vehicle['vehicle_type_name']) ?></p>
            </div>
            <div>
                <p class="item-code">Quyền đặt trước</p>
                <strong><?= $e($selected_vehicle['tier_name']) ?></strong>
                <p>Tối đa <?= $e($selected_vehicle['booking_window_days']) ?> ngày.</p>
            </div>
        </section>
    <?php endif; ?>

    <form class="stack-form wide-form booking-form" method="post" action="/dat-lich">
        <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
        <input type="hidden" name="vehicle_id" value="<?= $e($selectedVehicleId) ?>">

        <fieldset class="booking-fieldset">
            <legend>Chọn một hoặc nhiều dịch vụ</legend>
            <p class="field-help">Thời lượng được cộng; capacity units lấy mức lớn nhất, không cộng dồn.</p>
            <?php if ($services === []): ?>
                <div class="empty-state compact-empty">
                    <h2>Không có dịch vụ phù hợp</h2>
                    <p>Loại phương tiện này hiện chưa có dịch vụ đang hoạt động.</p>
                </div>
            <?php else: ?>
                <div class="booking-option-grid">
                    <?php foreach ($services as $service): ?>
                        <?php $serviceId = (string) $service['service_id']; ?>
                        <label class="booking-option">
                            <input type="checkbox" name="service_ids[]" value="<?= $e($serviceId) ?>"
                                <?= in_array($serviceId, $selectedServiceIds, true) ? 'checked' : '' ?>>
                            <span>
                                <strong><?= $e($service['service_name']) ?></strong>
                                <small><?= $e($service['description'] ?? '') ?></small>
                                <span class="booking-option-meta">
                                    <?= $e(VietnameseFormatter::vnd((string) $service['price'])) ?> ·
                                    <?= $e($service['duration_minutes']) ?> phút ·
                                    <?= $e($service['capacity_units']) ?> units
                                </span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (isset($errors['service_ids'])): ?>
                <span class="field-error" role="alert"><?= $e($errors['service_ids']) ?></span>
            <?php endif; ?>
        </fieldset>

        <fieldset class="booking-fieldset">
            <legend>Chọn khung giờ bắt đầu</legend>
            <p class="field-help">Hệ thống sẽ kiểm tra đủ chỗ trên mọi khung giờ mà dịch vụ kéo dài qua.</p>
            <?php if ($slots === []): ?>
                <div class="empty-state compact-empty">
                    <h2>Chưa có khung giờ mở</h2>
                    <p>Vui lòng quay lại khi lịch vận hành được cập nhật.</p>
                </div>
            <?php else: ?>
                <div class="booking-option-grid slot-option-grid">
                    <?php foreach ($slots as $slot): ?>
                        <?php
                        $slotId = (string) $slot['id'];
                        $remaining = (int) $slot['remaining_capacity_units'];
                        $selectable = $remaining > 0 && (bool) $slot['within_window'];
                        ?>
                        <label class="booking-option <?= $selectable ? '' : 'is-disabled' ?>">
                            <input type="radio" name="start_slot_id" value="<?= $e($slotId) ?>"
                                <?= $slotId === $selectedSlotId ? 'checked' : '' ?>
                                <?= $selectable ? '' : 'disabled' ?> required>
                            <span>
                                <strong>
                                    <?= $e(VietnameseFormatter::date((string) $slot['slot_date'])) ?> ·
                                    <?= $e(VietnameseFormatter::time((string) $slot['start_time'])) ?>–<?=
                                    $e(VietnameseFormatter::time((string) $slot['end_time']))
                                    ?>
                                </strong>
                                <small>
                                    <?php if ($remaining === 0): ?>
                                        Đã đầy
                                    <?php elseif (!(bool) $slot['within_window']): ?>
                                        Ngoài giới hạn đặt trước của hạng hiện tại
                                    <?php else: ?>
                                        Còn <?= $e($remaining) ?> / <?= $e($slot['capacity_units']) ?> capacity units
                                    <?php endif; ?>
                                </small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (isset($errors['start_slot_id'])): ?>
                <span class="field-error" role="alert"><?= $e($errors['start_slot_id']) ?></span>
            <?php endif; ?>
        </fieldset>

        <div class="form-actions">
            <button class="button button-primary" type="submit"
                <?= $services === [] || $slots === [] ? 'disabled' : '' ?>>
                Xác nhận đặt lịch
            </button>
            <a class="button button-outline" href="/khung-gio">Xem toàn bộ khung giờ</a>
        </div>
    </form>
<?php endif; ?>
