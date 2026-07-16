<?php

declare(strict_types=1);

use App\Support\VietnameseFormatter;

/** @var Closure(mixed): string $e */
/** @var list<array<string, mixed>> $slots */
/** @var string|null $flashSuccess */
/** @var string $csrfToken */
?>
<section class="page-heading page-heading-actions">
    <div>
        <p class="eyebrow dark-eyebrow">Lịch vận hành</p>
        <h1>Khung giờ và capacity</h1>
        <p class="lead">Sức chứa đã dùng được tổng hợp từ reservation của booking đang hoạt động.</p>
    </div>
    <a class="button button-primary" href="/admin/khung-gio/them">Thêm khung giờ</a>
</section>
<?php if (isset($flashSuccess) && is_string($flashSuccess)): ?>
    <div class="notification notification-success" role="status"><strong>Thành công</strong><span><?= $e($flashSuccess) ?></span></div>
<?php endif; ?>
<?php if ($slots === []): ?>
    <section class="empty-state"><h2>Chưa có khung giờ</h2><p>Hãy tạo khung giờ vận hành đầu tiên.</p></section>
<?php else: ?>
    <div class="table-shell">
        <table>
            <caption>Danh sách khung giờ và sức chứa</caption>
            <thead><tr><th scope="col">Ngày</th><th scope="col">Thời gian</th><th scope="col">Đã dùng</th><th scope="col">Còn lại</th><th scope="col">Trạng thái</th><th scope="col">Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($slots as $slot): ?>
                <tr>
                    <td data-label="Ngày"><?= $e(VietnameseFormatter::date((string) $slot['slot_date'])) ?></td>
                    <td data-label="Thời gian"><?= $e(VietnameseFormatter::time((string) $slot['start_time'])) ?>–<?= $e(VietnameseFormatter::time((string) $slot['end_time'])) ?></td>
                    <td data-label="Đã dùng"><?= $e($slot['used_capacity_units']) ?> / <?= $e($slot['capacity_units']) ?></td>
                    <td data-label="Còn lại"><strong><?= $e($slot['remaining_capacity_units']) ?> units</strong></td>
                    <td data-label="Trạng thái"><span class="status-badge <?= $slot['status'] === 'open' ? '' : 'status-neutral' ?>"><?= $slot['status'] === 'open' ? 'Đang mở' : 'Đã đóng' ?></span></td>
                    <td data-label="Thao tác" class="table-actions">
                        <?php if ($slot['status'] === 'open'): ?>
                            <form method="post" action="/admin/khung-gio/<?= $e($slot['id']) ?>/dong">
                                <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                                <button class="button button-danger button-compact" type="submit">Đóng khung giờ</button>
                            </form>
                        <?php else: ?>
                            <span class="muted-text">Không có thao tác</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
