<?php

declare(strict_types=1);

/** @var callable(mixed): string $e */
/** @var list<array<string, mixed>> $rewards */
$rewardTypeLabels = [
    'fixed_discount' => 'Giảm cố định',
    'percentage_discount' => 'Giảm phần trăm',
    'free_service' => 'Miễn phí dịch vụ',
    'add_on' => 'Tặng dịch vụ',
];
?>
<section class="page-header"><div><p class="eyebrow dark-eyebrow">Quà tặng thành viên</p><h1>Quản lý quà tặng</h1>
    <p class="lead">Thiết lập số điểm, thời hạn và điều kiện sử dụng; quà khách đã đổi luôn giữ nguyên thông tin.</p></div>
    <a class="button button-primary" href="/admin/reward/them">Thêm quà tặng</a></section>
<?php if (isset($flashSuccess) && is_string($flashSuccess)): ?>
    <div class="notification notification-success" role="status"><strong>Thành công</strong><span><?= $e($flashSuccess) ?></span></div>
<?php endif; ?>
<section class="content-section">
<?php if ($rewards === []): ?><div class="empty-state"><h2>Chưa có quà tặng</h2><p>Thêm quà tặng đầu tiên để khách hàng có thể sử dụng điểm.</p><a class="button button-primary" href="/admin/reward/them">Thêm quà tặng</a></div>
<?php else: ?><div class="table-shell"><table><thead><tr><th>Mã / tên</th><th>Loại</th><th>Điểm</th><th>Điều kiện</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
<?php foreach ($rewards as $reward): ?><tr>
    <td><strong><?= $e($reward['code']) ?></strong><br><?= $e($reward['name']) ?></td>
    <td><?= $e($rewardTypeLabels[$reward['reward_type']] ?? $reward['reward_type']) ?></td><td><?= $e((string) $reward['points_cost']) ?> điểm</td>
    <td><?= $reward['minimum_tier_name'] !== null ? 'Từ ' . $e($reward['minimum_tier_name']) : 'Mọi hạng' ?><?php if ($reward['vehicle_types'] !== null): ?><br><?= $e($reward['vehicle_types']) ?><?php endif; ?></td>
    <td><span class="status-badge <?= $reward['is_active'] ? 'status-completed' : 'status-neutral' ?>"><?= $reward['is_active'] ? 'Đang hoạt động' : 'Đã ngừng' ?></span></td>
    <td><div class="action-group"><a class="button button-outline button-compact" href="/admin/reward/<?= $e((string) $reward['id']) ?>/sua">Sửa</a>
        <form method="post" action="/admin/reward/<?= $e((string) $reward['id']) ?>/<?= $reward['is_active'] ? 'ngung-hoat-dong' : 'kich-hoat' ?>" data-confirm="<?= $reward['is_active'] ? 'Ngừng quà tặng này?' : 'Kích hoạt lại quà tặng này?' ?>">
            <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>"><button class="button button-ghost button-compact" type="submit"><?= $reward['is_active'] ? 'Ngừng' : 'Kích hoạt' ?></button>
        </form></div></td>
</tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
