<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var array<string, string> $values */
/** @var array<string, string> $errors */
/** @var string $csrfToken */
?>
<section class="page-heading">
    <p class="eyebrow dark-eyebrow">Lịch vận hành</p>
    <h1>Thêm khung giờ</h1>
    <p class="lead">Khung giờ mới luôn ở trạng thái mở và phải thuộc hôm nay hoặc tương lai.</p>
</section>
<form class="form-card" method="post" action="/admin/khung-gio/them">
    <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
    <?php if ($errors !== []): ?>
        <div class="notification notification-error" role="alert"><strong>Chưa thể tạo khung giờ</strong><span>Vui lòng kiểm tra dữ liệu bên dưới.</span></div>
    <?php endif; ?>
    <div class="form-field">
        <label for="slot_date">Ngày *</label>
        <input id="slot_date" name="slot_date" type="date" required value="<?= $e($values['slot_date']) ?>" aria-invalid="<?= isset($errors['slot_date']) ? 'true' : 'false' ?>">
        <?php if (isset($errors['slot_date'])): ?><span class="field-error"><?= $e($errors['slot_date']) ?></span><?php endif; ?>
    </div>
    <div class="form-grid">
        <div class="form-field"><label for="start_time">Giờ bắt đầu *</label><input id="start_time" name="start_time" type="time" required value="<?= $e($values['start_time']) ?>" aria-invalid="<?= isset($errors['start_time']) ? 'true' : 'false' ?>"><?php if (isset($errors['start_time'])): ?><span class="field-error"><?= $e($errors['start_time']) ?></span><?php endif; ?></div>
        <div class="form-field"><label for="end_time">Giờ kết thúc *</label><input id="end_time" name="end_time" type="time" required value="<?= $e($values['end_time']) ?>" aria-invalid="<?= isset($errors['end_time']) ? 'true' : 'false' ?>"><?php if (isset($errors['end_time'])): ?><span class="field-error"><?= $e($errors['end_time']) ?></span><?php endif; ?></div>
    </div>
    <div class="form-field">
        <label for="capacity_units">Sức chứa phục vụ *</label>
        <input id="capacity_units" name="capacity_units" inputmode="numeric" required value="<?= $e($values['capacity_units']) ?>" aria-invalid="<?= isset($errors['capacity_units']) ? 'true' : 'false' ?>">
        <span class="field-help">Nhập theo đơn vị sức chứa của từng loại phương tiện, không phải số lượng xe.</span>
        <?php if (isset($errors['capacity_units'])): ?><span class="field-error"><?= $e($errors['capacity_units']) ?></span><?php endif; ?>
    </div>
    <div class="form-actions"><a class="button button-outline" href="/admin/khung-gio">Hủy</a><button class="button button-primary" type="submit">Tạo khung giờ</button></div>
</form>
