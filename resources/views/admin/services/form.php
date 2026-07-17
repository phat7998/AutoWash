<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var list<array{id: int, code: string, display_name: string}> $vehicleTypes */
/** @var list<array<string, mixed>> $serviceGroups */
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
/** @var string $mode */
/** @var int|null $serviceId */
/** @var string $csrfToken */
$action = $mode === 'create' ? '/admin/dich-vu/them' : '/admin/dich-vu/' . $serviceId . '/sua';
?>
<section class="page-heading">
    <p class="eyebrow dark-eyebrow">Quản trị danh mục</p>
    <h1><?= $mode === 'create' ? 'Thêm dịch vụ' : 'Sửa dịch vụ' ?></h1>
    <p class="lead">Giá và thời lượng bắt buộc khi một loại xe được đánh dấu hỗ trợ.</p>
</section>
<form class="form-card wide-form" method="post" action="<?= $e($action) ?>">
    <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
    <?php if ($errors !== []): ?>
        <div class="notification notification-error" role="alert"><strong>Chưa thể lưu</strong><span>Vui lòng kiểm tra các trường được đánh dấu.</span></div>
    <?php endif; ?>
    <div class="form-grid">
        <div class="form-field">
            <label for="code">Mã dịch vụ *</label>
            <input id="code" name="code" required maxlength="50" value="<?= $e($values['code']) ?>" aria-invalid="<?= isset($errors['code']) ? 'true' : 'false' ?>">
            <?php if (isset($errors['code'])): ?><span class="field-error"><?= $e($errors['code']) ?></span><?php endif; ?>
        </div>
        <div class="form-field">
            <label for="name">Tên dịch vụ *</label>
            <input id="name" name="name" required maxlength="150" value="<?= $e($values['name']) ?>" aria-invalid="<?= isset($errors['name']) ? 'true' : 'false' ?>">
            <?php if (isset($errors['name'])): ?><span class="field-error"><?= $e($errors['name']) ?></span><?php endif; ?>
        </div>
    </div>
    <div class="form-field">
        <label for="description">Mô tả</label>
        <textarea id="description" name="description" rows="3" maxlength="2000" aria-invalid="<?= isset($errors['description']) ? 'true' : 'false' ?>"><?= $e($values['description']) ?></textarea>
        <?php if (isset($errors['description'])): ?><span class="field-error"><?= $e($errors['description']) ?></span><?php endif; ?>
    </div>
    <div class="form-field">
        <label for="service_group_id">Nhóm dịch vụ *</label>
        <select id="service_group_id" name="service_group_id" required
            aria-invalid="<?= isset($errors['service_group_id']) ? 'true' : 'false' ?>">
            <option value="">Chọn nhóm dịch vụ</option>
            <?php foreach ($serviceGroups as $group): ?>
                <option value="<?= $e($group['id']) ?>"
                    <?= (string) $group['id'] === (string) $values['service_group_id'] ? 'selected' : '' ?>>
                    <?= $e($group['name']) ?> · <?= $e($group['selection_mode'] === 'single' ? 'chọn một' : 'chọn nhiều') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span class="field-help">Chính sách lựa chọn được tải từ nhóm hệ thống.</span>
        <?php if (isset($errors['service_group_id'])): ?>
            <span class="field-error"><?= $e($errors['service_group_id']) ?></span>
        <?php endif; ?>
    </div>
    <fieldset class="price-configurations">
        <legend>Cấu hình theo loại phương tiện</legend>
        <?php foreach ($vehicleTypes as $type): ?>
            <?php
            $key = (string) $type['id'];
            $price = $values['prices'][$key] ?? [];
            $active = !array_key_exists('is_active', $price) || ($price['is_active'] ?? '') === '1';
            ?>
            <section class="price-row" aria-labelledby="type-<?= $e($key) ?>">
                <div class="price-row-heading">
                    <h2 id="type-<?= $e($key) ?>"><?= $e($type['display_name']) ?></h2>
                    <label class="check-field"><input type="checkbox" name="prices[<?= $e($key) ?>][is_supported]" value="1" <?= ($price['is_supported'] ?? '') === '1' ? 'checked' : '' ?>> Hỗ trợ</label>
                    <label class="check-field"><input type="checkbox" name="prices[<?= $e($key) ?>][is_active]" value="1" <?= $active ? 'checked' : '' ?>> Đang áp dụng</label>
                </div>
                <div class="form-grid form-grid-three">
                    <div class="form-field"><label for="price-<?= $e($key) ?>">Giá (VND)</label><input id="price-<?= $e($key) ?>" name="prices[<?= $e($key) ?>][price]" inputmode="decimal" value="<?= $e($price['price'] ?? '') ?>" aria-invalid="<?= isset($errors['prices.' . $key . '.price']) ? 'true' : 'false' ?>"><?php if (isset($errors['prices.' . $key . '.price'])): ?><span class="field-error"><?= $e($errors['prices.' . $key . '.price']) ?></span><?php endif; ?></div>
                    <div class="form-field"><label for="duration-<?= $e($key) ?>">Thời lượng (phút)</label><input id="duration-<?= $e($key) ?>" name="prices[<?= $e($key) ?>][duration_minutes]" inputmode="numeric" value="<?= $e($price['duration_minutes'] ?? '') ?>" aria-invalid="<?= isset($errors['prices.' . $key . '.duration_minutes']) ? 'true' : 'false' ?>"><?php if (isset($errors['prices.' . $key . '.duration_minutes'])): ?><span class="field-error"><?= $e($errors['prices.' . $key . '.duration_minutes']) ?></span><?php endif; ?></div>
                    <div class="form-field"><label for="capacity-<?= $e($key) ?>">Sức chứa riêng</label><input id="capacity-<?= $e($key) ?>" name="prices[<?= $e($key) ?>][capacity_units_override]" inputmode="numeric" value="<?= $e($price['capacity_units_override'] ?? '') ?>" aria-invalid="<?= isset($errors['prices.' . $key . '.capacity_units_override']) ? 'true' : 'false' ?>"><span class="field-help">Để trống để dùng mức mặc định của loại xe.</span><?php if (isset($errors['prices.' . $key . '.capacity_units_override'])): ?><span class="field-error"><?= $e($errors['prices.' . $key . '.capacity_units_override']) ?></span><?php endif; ?></div>
                </div>
            </section>
        <?php endforeach; ?>
    </fieldset>
    <div class="form-actions"><a class="button button-outline" href="/admin/dich-vu">Hủy</a><button class="button button-primary" type="submit">Lưu dịch vụ</button></div>
</form>
