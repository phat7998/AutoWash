<?php

declare(strict_types=1);

$action = $tierId === null ? '/admin/hang-thanh-vien/them' : '/admin/hang-thanh-vien/' . $tierId . '/sua';
$fields = [
    'code' => 'Mã hạng',
    'name' => 'Tên hạng',
    'rank_order' => 'Thứ tự',
    'booking_window_days' => 'Số ngày đặt trước',
    'min_monthly_spend' => 'Chi tiêu tối thiểu',
    'min_monthly_visits' => 'Số lượt tối thiểu',
    'point_rate' => 'Hệ số tích điểm',
];
?>
<section class="page-header"><div><p class="eyebrow dark-eyebrow">Hạng thành viên</p><h1><?= $tierId === null ? 'Thêm hạng' : 'Sửa hạng' ?></h1><p class="lead">Thiết lập điều kiện xét hạng, thời gian đặt trước và hệ số tích điểm.</p></div><a class="button button-outline" href="/admin/hang-thanh-vien">Quay lại</a></section>
<?php if ($errors !== []): ?><div class="notification notification-error" role="alert"><strong>Chưa thể lưu hạng</strong><span>Vui lòng sửa các trường được báo lỗi.</span></div><?php endif; ?>
<form class="form-card wide-form" method="post" action="<?= $e($action) ?>">
    <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
    <div class="form-grid">
        <?php foreach ($fields as $key => $label): ?>
            <label class="form-field"><span><?= $e($label) ?> *</span><input name="<?= $e($key) ?>" required value="<?= $e($values[$key]) ?>" aria-invalid="<?= isset($errors[$key]) ? 'true' : 'false' ?>"><?php if (isset($errors[$key])): ?><span class="field-error"><?= $e($errors[$key]) ?></span><?php endif; ?></label>
        <?php endforeach; ?>
    </div>
    <div class="form-actions"><button class="button button-primary" type="submit">Lưu hạng</button><a class="button button-outline" href="/admin/hang-thanh-vien">Hủy</a></div>
</form>
