<?php

declare(strict_types=1);

use App\Support\VietnameseFormatter;

$perkLabels = ['percentage_discount' => 'Giảm phần trăm', 'fixed_discount' => 'Giảm cố định', 'free_add_on' => 'Tặng dịch vụ'];
?>
<section class="page-header"><div><p class="eyebrow dark-eyebrow">Hạng thành viên</p><h1>Cấu hình hạng và quyền lợi</h1><p class="lead">Quản lý điều kiện xét hạng, hệ số tích điểm và quyền lợi tự động.</p></div><div class="action-group"><a class="button button-primary" href="/admin/hang-thanh-vien/them">Thêm hạng</a><a class="button button-outline" href="/admin/quyen-loi/them">Thêm quyền lợi</a></div></section>
<?php if (isset($flashSuccess) && is_string($flashSuccess)): ?><div class="notification notification-success" role="status"><strong>Đã cập nhật</strong><span><?= $e($flashSuccess) ?></span></div><?php endif; ?>

<section class="content-section" aria-labelledby="tier-rules-title"><div class="section-heading"><h2 id="tier-rules-title">Quy tắc hạng</h2><p>Điều kiện chi tiêu và số lượt phải cùng đạt trong kỳ xét.</p></div>
<?php if ($tiers === []): ?><div class="empty-state"><h3>Chưa có hạng thành viên</h3><p>Thêm hạng đầu tiên để thiết lập chương trình thành viên.</p><a class="button button-primary" href="/admin/hang-thanh-vien/them">Thêm hạng</a></div><?php else: ?>
<div class="table-shell"><table><caption>Danh sách hạng thành viên</caption><thead><tr><th scope="col">Hạng</th><th scope="col">Điều kiện mỗi tháng</th><th scope="col">Đặt trước và tích điểm</th><th scope="col">Trạng thái</th><th scope="col">Thao tác</th></tr></thead><tbody>
<?php foreach ($tiers as $tier): ?><tr>
    <td><strong><?= $e($tier['name']) ?></strong><small class="transaction-meta">Mã <?= $e($tier['code']) ?> · Thứ tự <?= $e($tier['rank_order']) ?></small></td>
    <td><?= $e(VietnameseFormatter::vnd((string) $tier['min_monthly_spend'])) ?> và <?= $e($tier['min_monthly_visits']) ?> lượt</td>
    <td><?= $e($tier['booking_window_days']) ?> ngày · hệ số <?= $e($tier['point_rate']) ?></td>
    <td><span class="status-badge <?= $tier['is_active'] ? 'status-completed' : 'status-neutral' ?>"><?= $tier['is_active'] ? 'Đang hoạt động' : 'Đã ngừng' ?></span></td>
    <td><div class="action-group"><a class="button button-outline button-compact" href="/admin/hang-thanh-vien/<?= $e($tier['id']) ?>/sua">Sửa</a><form method="post" action="/admin/hang-thanh-vien/<?= $e($tier['id']) ?>/<?= $tier['is_active'] ? 'ngung-hoat-dong' : 'kich-hoat' ?>" data-confirm="<?= $tier['is_active'] ? 'Ngừng áp dụng hạng thành viên này?' : 'Kích hoạt lại hạng thành viên này?' ?>"><input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>"><button class="button button-ghost button-compact" type="submit"><?= $tier['is_active'] ? 'Ngừng' : 'Kích hoạt' ?></button></form></div></td>
</tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>

<section class="content-section" aria-labelledby="perks-title"><div class="section-heading"><h2 id="perks-title">Quyền lợi tự động</h2><p>Quyền lợi được áp dụng khi lịch đặt đáp ứng điều kiện hạng và dịch vụ.</p></div>
<?php if ($perks === []): ?><div class="empty-state"><h3>Chưa có quyền lợi</h3><p>Lịch đặt vẫn hoạt động bình thường khi chưa cấu hình quyền lợi hạng.</p><a class="button button-outline" href="/admin/quyen-loi/them">Thêm quyền lợi</a></div><?php else: ?>
<div class="table-shell"><table><caption>Danh sách quyền lợi thành viên</caption><thead><tr><th scope="col">Hạng</th><th scope="col">Quyền lợi</th><th scope="col">Dịch vụ</th><th scope="col">Trạng thái</th><th scope="col">Thao tác</th></tr></thead><tbody>
<?php foreach ($perks as $perk): ?><tr><td><?= $e($perk['tier_name']) ?></td><td><?= $e($perkLabels[$perk['perk_type']] ?? $perk['perk_type']) ?> · <?= $e($perk['value']) ?></td><td><?= $perk['service_name'] === null ? 'Không giới hạn' : $e($perk['service_name']) ?></td><td><span class="status-badge <?= $perk['is_active'] ? 'status-completed' : 'status-neutral' ?>"><?= $perk['is_active'] ? 'Đang hoạt động' : 'Đã ngừng' ?></span></td><td><div class="action-group"><a class="button button-outline button-compact" href="/admin/quyen-loi/<?= $e($perk['id']) ?>/sua">Sửa</a><form method="post" action="/admin/quyen-loi/<?= $e($perk['id']) ?>/<?= $perk['is_active'] ? 'ngung-hoat-dong' : 'kich-hoat' ?>" data-confirm="<?= $perk['is_active'] ? 'Ngừng áp dụng quyền lợi này?' : 'Kích hoạt lại quyền lợi này?' ?>"><input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>"><button class="button button-ghost button-compact" type="submit"><?= $perk['is_active'] ? 'Ngừng' : 'Kích hoạt' ?></button></form></div></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
