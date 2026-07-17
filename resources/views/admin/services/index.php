<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var list<array<string, mixed>> $services */
/** @var string|null $flashSuccess */
/** @var string $csrfToken */
?>
<section class="page-heading page-heading-actions">
    <div>
        <p class="eyebrow dark-eyebrow">Quản trị danh mục</p>
        <h1>Dịch vụ và cấu hình giá</h1>
        <p class="lead">Mỗi dịch vụ có một cấu hình hiện hành cho từng loại phương tiện.</p>
    </div>
    <a class="button button-primary" href="/admin/dich-vu/them">Thêm dịch vụ</a>
</section>
<?php if (isset($flashSuccess) && is_string($flashSuccess)): ?>
    <div class="notification notification-success" role="status">
        <strong>Thành công</strong><span><?= $e($flashSuccess) ?></span>
    </div>
<?php endif; ?>
<?php if ($services === []): ?>
    <section class="empty-state"><h2>Chưa có dịch vụ</h2><p>Hãy tạo dịch vụ đầu tiên cho danh mục.</p></section>
<?php else: ?>
    <div class="table-shell">
        <table>
            <caption>Danh sách dịch vụ và số loại xe được hỗ trợ</caption>
            <thead><tr><th scope="col">Dịch vụ</th><th scope="col">Mã</th><th scope="col">Nhóm / chính sách</th><th scope="col">Loại xe hỗ trợ</th><th scope="col">Trạng thái</th><th scope="col">Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($services as $service): ?>
                <tr>
                    <td data-label="Dịch vụ"><strong><?= $e($service['name']) ?></strong></td>
                    <td data-label="Mã"><?= $e($service['code']) ?></td>
                    <td data-label="Nhóm / chính sách">
                        <?= $e($service['service_group_name']) ?> ·
                        <?= $e($service['selection_mode'] === 'single' ? 'chọn một' : 'chọn nhiều') ?>
                    </td>
                    <td data-label="Loại xe hỗ trợ"><?= $e((int) $service['supported_type_count']) ?>/4</td>
                    <td data-label="Trạng thái"><span class="status-badge <?= (bool) $service['is_active'] ? '' : 'status-neutral' ?>"><?= (bool) $service['is_active'] ? 'Đang hoạt động' : 'Ngừng hoạt động' ?></span></td>
                    <td data-label="Thao tác" class="table-actions">
                        <a class="button button-outline button-compact" href="/admin/dich-vu/<?= $e($service['id']) ?>/sua">Sửa</a>
                        <?php if ((bool) $service['is_active']): ?>
                            <form method="post" action="/admin/dich-vu/<?= $e($service['id']) ?>/ngung-hoat-dong">
                                <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                                <button class="button button-danger button-compact" type="submit">Ngừng</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="/admin/dich-vu/<?= $e($service['id']) ?>/kich-hoat">
                                <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                                <button class="button button-primary button-compact" type="submit">Kích hoạt</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
