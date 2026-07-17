<?php

declare(strict_types=1);

$discountLabels = ['fixed' => 'Giảm cố định', 'percentage' => 'Giảm phần trăm'];
?>
<section class="page-header"><div><p class="eyebrow dark-eyebrow">Khuyến mãi</p><h1>Quản lý khuyến mãi</h1><p class="lead">Cấu hình thời gian, mức giảm, giới hạn sử dụng và nhóm khách hàng áp dụng.</p></div><a class="button button-primary" href="/admin/promotion/them">Thêm khuyến mãi</a></section>
<?php if (isset($flashSuccess) && is_string($flashSuccess)): ?><div class="notification notification-success" role="status"><strong>Đã cập nhật</strong><span><?= $e($flashSuccess) ?></span></div><?php endif; ?>
<section class="content-section">
<?php if ($promotions === []): ?><div class="empty-state"><h2>Chưa có khuyến mãi</h2><p>Tạo chương trình đầu tiên để hệ thống tự áp dụng khi khách đặt lịch đủ điều kiện.</p><a class="button button-primary" href="/admin/promotion/them">Thêm khuyến mãi</a></div>
<?php else: ?><div class="table-shell"><table><caption>Danh sách chương trình khuyến mãi</caption><thead><tr><th scope="col">Chương trình</th><th scope="col">Mức giảm</th><th scope="col">Thời gian</th><th scope="col">Áp dụng cho</th><th scope="col">Giới hạn</th><th scope="col">Thao tác</th></tr></thead><tbody>
<?php foreach ($promotions as $promotion): ?><tr>
    <td><strong><?= $e($promotion['name']) ?></strong><small class="transaction-meta">Mã <?= $e($promotion['code']) ?></small></td>
    <td><?= $e($discountLabels[$promotion['discount_type']] ?? $promotion['discount_type']) ?> · <?= $e($promotion['discount_value']) ?></td>
    <td><?= $e($promotion['start_at']) ?><br><?= $e($promotion['end_at']) ?></td>
    <td><?= $promotion['tier_names'] === null ? 'Mọi hạng' : $e($promotion['tier_names']) ?><br><?= $promotion['service_names'] === null ? 'Mọi dịch vụ' : $e($promotion['service_names']) ?><br><?= $promotion['vehicle_type_names'] === null ? 'Mọi loại xe' : $e($promotion['vehicle_type_names']) ?></td>
    <td>Tổng: <?= $e($promotion['usage_limit'] ?? 'Không giới hạn') ?><br>Mỗi khách: <?= $e($promotion['per_user_limit'] ?? 'Không giới hạn') ?></td>
    <td><div class="action-group"><a class="button button-outline button-compact" href="/admin/promotion/<?= $e($promotion['id']) ?>/sua">Sửa</a><form method="post" action="/admin/promotion/<?= $e($promotion['id']) ?>/<?= $promotion['is_active'] ? 'ngung-hoat-dong' : 'kich-hoat' ?>" data-confirm="<?= $promotion['is_active'] ? 'Ngừng chương trình khuyến mãi này?' : 'Kích hoạt chương trình khuyến mãi này?' ?>"><input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>"><button class="button button-ghost button-compact" type="submit"><?= $promotion['is_active'] ? 'Ngừng' : 'Kích hoạt' ?></button></form></div></td>
</tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
