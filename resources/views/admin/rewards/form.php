<?php

declare(strict_types=1);

/** @var callable(mixed): string $e */
/** @var array<string, mixed> $values */
/** @var array<string, string> $errors */
/** @var array<string, list<array<string, mixed>>> $options */
$action = $mode === 'create' ? '/admin/reward/them' : '/admin/reward/' . $rewardId . '/sua';
$error = static fn (string $field): string => isset($errors[$field]) ? '<span class="field-error">' . $e($errors[$field]) . '</span>' : '';
?>
<section class="page-header"><div><span class="eyebrow">REWARD</span><h1><?= $mode === 'create' ? 'Thêm reward' : 'Sửa reward' ?></h1>
    <p>Backend kiểm tra lại điểm, hạng, dịch vụ và loại xe khi lưu.</p></div><a class="button button-outline" href="/admin/reward">Quay lại</a></section>
<form class="form-card" method="post" action="<?= $e($action) ?>">
    <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
    <div class="form-grid">
        <label class="form-field"><span>Mã reward *</span><input name="code" maxlength="50" required value="<?= $e($values['code']) ?>"><?= $error('code') ?></label>
        <label class="form-field"><span>Tên reward *</span><input name="name" maxlength="150" required value="<?= $e($values['name']) ?>"><?= $error('name') ?></label>
        <label class="form-field"><span>Loại *</span><select name="reward_type" required>
            <?php foreach (['fixed_discount' => 'Giảm cố định', 'percentage_discount' => 'Giảm phần trăm', 'free_service' => 'Miễn phí dịch vụ', 'add_on' => 'Tặng dịch vụ'] as $key => $label): ?>
                <option value="<?= $e($key) ?>" <?= $values['reward_type'] === $key ? 'selected' : '' ?>><?= $e($label) ?></option>
            <?php endforeach; ?></select><?= $error('reward_type') ?></label>
        <label class="form-field"><span>Điểm đổi *</span><input type="number" min="1" name="points_cost" required value="<?= $e($values['points_cost']) ?>"><?= $error('points_cost') ?></label>
        <label class="form-field"><span>Giá trị *</span><input inputmode="decimal" name="value" required value="<?= $e($values['value']) ?>"><?= $error('value') ?></label>
        <label class="form-field"><span>Hiệu lực sau đổi (ngày) *</span><input type="number" min="1" name="valid_days_after_redeem" required value="<?= $e($values['valid_days_after_redeem']) ?>"><?= $error('valid_days_after_redeem') ?></label>
        <label class="form-field"><span>Dịch vụ</span><select name="service_id"><option value="">Không giới hạn</option><?php foreach ($options['services'] as $service): ?><option value="<?= $e((string) $service['id']) ?>" <?= $values['service_id'] === (string) $service['id'] ? 'selected' : '' ?>><?= $e($service['name']) ?></option><?php endforeach; ?></select><?= $error('service_id') ?></label>
        <label class="form-field"><span>Hạng tối thiểu</span><select name="minimum_tier_id"><option value="">Mọi hạng</option><?php foreach ($options['tiers'] as $tier): ?><option value="<?= $e((string) $tier['id']) ?>" <?= $values['minimum_tier_id'] === (string) $tier['id'] ? 'selected' : '' ?>><?= $e($tier['name']) ?></option><?php endforeach; ?></select><?= $error('minimum_tier_id') ?></label>
    </div>
    <fieldset class="form-field"><legend>Loại phương tiện được phép</legend><p class="field-help">Không chọn nghĩa là không giới hạn; việc áp dụng khi checkout thuộc Slice 12.</p>
        <div class="form-grid"><?php foreach ($options['vehicle_types'] as $type): ?><label><input type="checkbox" name="vehicle_type_ids[]" value="<?= $e((string) $type['id']) ?>" <?= in_array((string) $type['id'], $values['vehicle_type_ids'], true) ? 'checked' : '' ?>> <?= $e($type['display_name']) ?></label><?php endforeach; ?></div><?= $error('vehicle_type_ids') ?>
    </fieldset>
    <div class="form-actions"><button class="button button-primary" type="submit">Lưu reward</button><a class="button button-outline" href="/admin/reward">Hủy</a></div>
</form>
