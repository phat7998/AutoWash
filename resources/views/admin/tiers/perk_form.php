<?php

declare(strict_types=1);

$action = $perkId === null ? '/admin/quyen-loi/them' : '/admin/quyen-loi/' . $perkId . '/sua';
$perkTypes = ['percentage_discount' => 'Giảm phần trăm', 'fixed_discount' => 'Giảm cố định', 'free_add_on' => 'Tặng dịch vụ'];
?>
<section class="page-header"><div><p class="eyebrow dark-eyebrow">Quyền lợi thành viên</p><h1><?= $perkId === null ? 'Thêm quyền lợi' : 'Sửa quyền lợi' ?></h1><p class="lead">Khi đặt lịch, hệ thống tự áp dụng một quyền lợi phù hợp và có giá trị tốt nhất.</p></div><a class="button button-outline" href="/admin/hang-thanh-vien">Quay lại</a></section>
<?php if ($errors !== []): ?><div class="notification notification-error" role="alert"><strong>Chưa thể lưu quyền lợi</strong><span>Vui lòng kiểm tra thông tin bên dưới.</span></div><?php endif; ?>
<form class="form-card" method="post" action="<?= $e($action) ?>">
    <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
    <div class="form-grid">
        <label class="form-field"><span>Hạng thành viên *</span><select name="tier_id" required><?php foreach ($options['tiers'] as $tier): ?><option value="<?= $e($tier['id']) ?>" <?= (string) $tier['id'] === (string) $values['tier_id'] ? 'selected' : '' ?>><?= $e($tier['name']) ?></option><?php endforeach; ?></select><?php if (isset($errors['tier_id'])): ?><span class="field-error"><?= $e($errors['tier_id']) ?></span><?php endif; ?></label>
        <label class="form-field"><span>Loại quyền lợi *</span><select name="perk_type"><?php foreach ($perkTypes as $key => $label): ?><option value="<?= $e($key) ?>" <?= $values['perk_type'] === $key ? 'selected' : '' ?>><?= $e($label) ?></option><?php endforeach; ?></select></label>
        <label class="form-field"><span>Giá trị *</span><input name="value" required value="<?= $e($values['value']) ?>"><?php if (isset($errors['value'])): ?><span class="field-error"><?= $e($errors['value']) ?></span><?php endif; ?></label>
        <label class="form-field"><span>Dịch vụ áp dụng</span><select name="service_id"><option value="">Không giới hạn</option><?php foreach ($options['services'] as $service): ?><option value="<?= $e($service['id']) ?>" <?= (string) $service['id'] === (string) $values['service_id'] ? 'selected' : '' ?>><?= $e($service['name']) ?></option><?php endforeach; ?></select><?php if (isset($errors['service_id'])): ?><span class="field-error"><?= $e($errors['service_id']) ?></span><?php endif; ?></label>
    </div>
    <div class="form-actions"><button class="button button-primary" type="submit">Lưu quyền lợi</button><a class="button button-outline" href="/admin/hang-thanh-vien">Hủy</a></div>
</form>
