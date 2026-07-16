<?php

declare(strict_types=1);

/** @var callable(mixed): string $e */
/** @var list<array<string, mixed>> $rewards */
?>
<section class="page-header"><div><span class="eyebrow">CẤU HÌNH</span><h1>Quản lý reward</h1>
    <p>Reward là dữ liệu cấu hình; lịch sử redemption không bị sửa khi cấu hình thay đổi.</p></div>
    <a class="button button-primary" href="/admin/reward/them">Thêm reward</a></section>
<?php if (isset($flashSuccess) && is_string($flashSuccess)): ?>
    <div class="notification notification-success" role="status"><strong>Thành công</strong><span><?= $e($flashSuccess) ?></span></div>
<?php endif; ?>
<section class="content-section">
<?php if ($rewards === []): ?><div class="empty-state"><h2>Chưa có reward</h2><p>Thêm reward đầu tiên để customer có thể đổi điểm.</p></div>
<?php else: ?><div class="table-shell"><table><thead><tr><th>Mã / tên</th><th>Loại</th><th>Điểm</th><th>Điều kiện</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
<?php foreach ($rewards as $reward): ?><tr>
    <td><strong><?= $e($reward['code']) ?></strong><br><?= $e($reward['name']) ?></td>
    <td><?= $e($reward['reward_type']) ?></td><td><?= $e((string) $reward['points_cost']) ?></td>
    <td><?= $reward['minimum_tier_name'] !== null ? 'Từ ' . $e($reward['minimum_tier_name']) : 'Mọi hạng' ?><?php if ($reward['vehicle_types'] !== null): ?><br><?= $e($reward['vehicle_types']) ?><?php endif; ?></td>
    <td><span class="status-badge <?= $reward['is_active'] ? 'status-completed' : 'status-neutral' ?>"><?= $reward['is_active'] ? 'Đang hoạt động' : 'Đã ngừng' ?></span></td>
    <td><div class="action-group"><a class="button button-outline button-compact" href="/admin/reward/<?= $e((string) $reward['id']) ?>/sua">Sửa</a>
        <form method="post" action="/admin/reward/<?= $e((string) $reward['id']) ?>/<?= $reward['is_active'] ? 'ngung-hoat-dong' : 'kich-hoat' ?>">
            <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>"><button class="button button-ghost button-compact" type="submit"><?= $reward['is_active'] ? 'Ngừng' : 'Kích hoạt' ?></button>
        </form></div></td>
</tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
