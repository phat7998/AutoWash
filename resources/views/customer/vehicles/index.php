<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var list<array<string, mixed>> $vehicles */
/** @var string $csrfToken */
/** @var string|null $flashSuccess */
?>
<section class="page-heading page-heading-actions">
    <div>
        <p class="eyebrow dark-eyebrow">Khu vực khách hàng</p>
        <h1>Phương tiện của tôi</h1>
        <p class="lead">Quản lý biển số và thông tin xe dùng cho các lần đặt lịch sau.</p>
    </div>
    <a class="button button-primary" href="/phuong-tien/them">Thêm phương tiện</a>
</section>

<?php if (isset($flashSuccess) && is_string($flashSuccess)): ?>
    <div class="notification notification-success" role="status">
        <strong>Thành công</strong>
        <span><?= $e($flashSuccess) ?></span>
    </div>
<?php endif; ?>

<?php if ($vehicles === []): ?>
    <section class="empty-state" aria-labelledby="vehicle-empty-title">
        <h2 id="vehicle-empty-title">Bạn chưa có phương tiện</h2>
        <p>Thêm biển số và loại xe để chuẩn bị cho luồng đặt lịch.</p>
        <a class="button button-primary" href="/phuong-tien/them">Thêm phương tiện đầu tiên</a>
    </section>
<?php else: ?>
    <section class="vehicle-grid" aria-label="Danh sách phương tiện">
        <?php foreach ($vehicles as $vehicle): ?>
            <article class="vehicle-card">
                <div class="vehicle-card-header">
                    <div>
                        <p class="vehicle-plate"><?= $e($vehicle['display_plate']) ?></p>
                        <p class="vehicle-meta">
                            <?= $e($vehicle['vehicle_type_name']) ?>
                            <?php if (($vehicle['brand'] ?? null) !== null): ?>
                                · <?= $e($vehicle['brand']) ?>
                            <?php endif; ?>
                            <?php if (($vehicle['model'] ?? null) !== null): ?>
                                <?= $e($vehicle['model']) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <span class="status-badge <?= (bool) $vehicle['is_active'] ? '' : 'status-inactive' ?>">
                        <?= (bool) $vehicle['is_active'] ? 'Đang sử dụng' : 'Ngừng sử dụng' ?>
                    </span>
                </div>

                <?php if (($vehicle['notes'] ?? null) !== null): ?>
                    <p class="vehicle-notes"><?= $e($vehicle['notes']) ?></p>
                <?php endif; ?>

                <div class="vehicle-actions">
                    <a class="button button-outline" href="/phuong-tien/<?= $e($vehicle['id']) ?>/sua">
                        Sửa thông tin
                    </a>
                    <?php if ((bool) $vehicle['is_active']): ?>
                        <form method="post" action="/phuong-tien/<?= $e($vehicle['id']) ?>/ngung-su-dung">
                            <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                            <button class="button button-danger" type="submit">
                                Ngừng sử dụng
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
