<?php

declare(strict_types=1);

$action = $promotionId === null ? '/admin/promotion/them' : '/admin/promotion/' . $promotionId . '/sua';
$fields = [
    'code' => 'Mã khuyến mãi', 'name' => 'Tên khuyến mãi', 'discount_value' => 'Giá trị giảm',
    'max_discount' => 'Mức giảm tối đa', 'minimum_order_value' => 'Giá trị đơn tối thiểu',
    'usage_limit' => 'Giới hạn toàn chương trình', 'per_user_limit' => 'Giới hạn mỗi khách',
];
$requiredFields = ['code', 'name', 'discount_value', 'minimum_order_value'];
?>
<section class="page-header"><div><p class="eyebrow dark-eyebrow">Khuyến mãi</p><h1><?= $promotionId === null ? 'Thêm khuyến mãi' : 'Sửa khuyến mãi' ?></h1><p class="lead">Thiết lập nội dung, thời gian, điều kiện áp dụng và giới hạn sử dụng.</p></div><a class="button button-outline" href="/admin/promotion">Quay lại</a></section>
<?php if ($errors !== []): ?><div class="notification notification-error" role="alert"><strong>Chưa thể lưu khuyến mãi</strong><ul class="error-list"><?php foreach (array_unique($errors) as $message): ?><li><?= $e($message) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form class="form-card wide-form" method="post" action="<?= $e($action) ?>">
    <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
    <fieldset class="form-section"><legend>Thông tin chương trình</legend><div class="form-grid">
        <?php foreach ($fields as $key => $label): ?><label class="form-field"><span><?= $e($label) ?><?= in_array($key, $requiredFields, true) ? ' *' : '' ?></span><input name="<?= $e($key) ?>" value="<?= $e($values[$key]) ?>" <?= in_array($key, $requiredFields, true) ? 'required' : '' ?> aria-invalid="<?= isset($errors[$key]) ? 'true' : 'false' ?>"><?php if (isset($errors[$key])): ?><span class="field-error"><?= $e($errors[$key]) ?></span><?php endif; ?></label><?php endforeach; ?>
        <label class="form-field"><span>Loại giảm *</span><select name="discount_type"><option value="fixed" <?= $values['discount_type'] === 'fixed' ? 'selected' : '' ?>>Giảm cố định</option><option value="percentage" <?= $values['discount_type'] === 'percentage' ? 'selected' : '' ?>>Giảm phần trăm</option></select></label>
    </div><label class="form-field"><span>Mô tả</span><textarea name="description" maxlength="2000" rows="4"><?= $e($values['description']) ?></textarea></label></fieldset>
    <fieldset class="form-section"><legend>Thời gian áp dụng</legend><div class="form-grid"><label class="form-field"><span>Bắt đầu *</span><input type="datetime-local" name="start_at" required value="<?= $e($values['start_at']) ?>"></label><label class="form-field"><span>Kết thúc *</span><input type="datetime-local" name="end_at" required value="<?= $e($values['end_at']) ?>"></label></div></fieldset>
    <fieldset class="form-section"><legend>Phạm vi áp dụng</legend><p class="field-help">Không chọn mục nào trong một nhóm nghĩa là áp dụng cho tất cả trong nhóm đó.</p>
        <?php foreach (['tier_ids' => 'Hạng thành viên', 'service_ids' => 'Dịch vụ', 'vehicle_type_ids' => 'Loại phương tiện'] as $key => $legend): ?>
            <fieldset class="choice-section"><legend><?= $e($legend) ?></legend><div class="check-grid"><?php $optionKey = $key === 'tier_ids' ? 'tiers' : ($key === 'service_ids' ? 'services' : 'vehicle_types'); foreach ($options[$optionKey] as $option): $label = $option['name'] ?? $option['display_name']; ?><label class="check-field"><input type="checkbox" name="<?= $e($key) ?>[]" value="<?= $e($option['id']) ?>" <?= in_array((int) $option['id'], array_map('intval', $values[$key]), true) ? 'checked' : '' ?>> <?= $e($label) ?></label><?php endforeach; ?></div></fieldset>
        <?php endforeach; ?>
    </fieldset>
    <div class="form-actions"><button class="button button-primary" type="submit">Lưu khuyến mãi</button><a class="button button-outline" href="/admin/promotion">Hủy</a></div>
</form>
