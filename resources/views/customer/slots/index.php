<?php

declare(strict_types=1);

use App\Support\VietnameseFormatter;

/** @var Closure(mixed): string $e */
/** @var list<array<string, mixed>> $slots */
/** @var string $selectedDate */
/** @var array<string, string> $errors */
?>
<section class="page-heading">
    <p class="eyebrow dark-eyebrow">Lịch vận hành</p>
    <h1>Khung giờ khả dụng</h1>
    <p class="lead">Sức chứa còn lại chỉ tính các booking pending hoặc confirmed đang giữ chỗ.</p>
</section>

<form class="filter-bar" method="get" action="/khung-gio">
    <div class="form-field filter-field">
        <label for="ngay">Ngày cần xem</label>
        <input id="ngay" name="ngay" type="date" value="<?= $e($selectedDate) ?>"
               aria-invalid="<?= isset($errors['slot_date']) ? 'true' : 'false' ?>">
        <?php if (isset($errors['slot_date'])): ?>
            <span class="field-error" role="alert"><?= $e($errors['slot_date']) ?></span>
        <?php endif; ?>
    </div>
    <button class="button button-primary" type="submit">Lọc khung giờ</button>
    <?php if ($selectedDate !== ''): ?>
        <a class="button button-outline" href="/khung-gio">Xóa bộ lọc</a>
    <?php endif; ?>
</form>

<?php if ($slots === []): ?>
    <section class="empty-state" aria-labelledby="slot-empty-title">
        <h2 id="slot-empty-title">Không có khung giờ mở</h2>
        <p>Chưa có khung giờ phù hợp với ngày đã chọn. Vui lòng xem một ngày khác.</p>
    </section>
<?php else: ?>
    <section class="slot-grid" aria-label="Danh sách khung giờ đang mở">
        <?php foreach ($slots as $slot): ?>
            <?php $remaining = (int) $slot['remaining_capacity_units']; ?>
            <article class="card slot-card <?= $remaining === 0 ? 'slot-full' : '' ?>">
                <div class="card-heading-row">
                    <div>
                        <p class="item-code"><?= $e(VietnameseFormatter::date((string) $slot['slot_date'])) ?></p>
                        <h2>
                            <?= $e(VietnameseFormatter::time((string) $slot['start_time'])) ?>–<?=
                            $e(VietnameseFormatter::time((string) $slot['end_time']))
                            ?>
                        </h2>
                    </div>
                    <span class="status-badge <?= $remaining === 0 ? 'status-danger' : '' ?>">
                        <?= $remaining === 0 ? 'Đã đầy' : 'Còn chỗ' ?>
                    </span>
                </div>
                <p>
                    Còn <strong><?= $e($remaining) ?></strong> / <?= $e($slot['capacity_units']) ?> capacity units.
                </p>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
