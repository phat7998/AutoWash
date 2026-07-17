<?php

declare(strict_types=1);

use App\Support\VietnameseFormatter;

/** @var Closure(mixed): string $e */
/** @var list<array{id: int, code: string, display_name: string}> $vehicleTypes */
/** @var array{id: int, code: string, display_name: string} $selectedType */
/** @var list<array<string, mixed>> $services */
/** @var array<string, string> $errors */
?>
<section class="page-heading">
    <p class="eyebrow dark-eyebrow">Danh mục chăm sóc xe</p>
    <h1>Dịch vụ theo loại phương tiện</h1>
    <p class="lead">Xem giá và thời lượng phù hợp trước khi chọn lịch chăm sóc xe.</p>
</section>

<form class="filter-bar" method="get" action="/dich-vu">
    <div class="form-field filter-field">
        <label for="vehicle_type_id">Loại phương tiện</label>
        <select id="vehicle_type_id" name="vehicle_type_id" aria-invalid="<?= isset($errors['vehicle_type_id']) ? 'true' : 'false' ?>">
            <?php foreach ($vehicleTypes as $type): ?>
                <option value="<?= $e($type['id']) ?>" <?= $type['id'] === $selectedType['id'] ? 'selected' : '' ?>>
                    <?= $e($type['display_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['vehicle_type_id'])): ?>
            <span class="field-error" role="alert"><?= $e($errors['vehicle_type_id']) ?></span>
        <?php endif; ?>
    </div>
    <button class="button button-primary" type="submit">Xem dịch vụ</button>
</form>

<?php if ($services === []): ?>
    <section class="empty-state" aria-labelledby="catalog-empty-title">
        <h2 id="catalog-empty-title">Chưa có dịch vụ phù hợp</h2>
        <p>Hiện chưa có dịch vụ phù hợp cho <?= $e($selectedType['display_name']) ?>. Vui lòng chọn loại xe khác.</p>
    </section>
<?php else: ?>
    <section class="catalog-grid" aria-label="Dịch vụ cho <?= $e($selectedType['display_name']) ?>">
        <?php foreach ($services as $service): ?>
            <article class="card service-card">
                <div class="card-heading-row">
                    <div>
                        <p class="item-code"><?= $e($service['code']) ?></p>
                        <h2><?= $e($service['name']) ?></h2>
                    </div>
                    <strong class="service-price"><?= $e(VietnameseFormatter::vnd((string) $service['price'])) ?></strong>
                </div>
                <p><?= $e($service['description'] ?? 'Không có mô tả.') ?></p>
                <dl class="detail-list">
                    <div><dt>Thời lượng</dt><dd><?= $e($service['duration_minutes']) ?> phút</dd></div>
                    <div><dt>Khả năng phục vụ</dt><dd><?= $e($service['capacity_units']) ?> đơn vị sức chứa</dd></div>
                </dl>
                <a class="button button-outline" href="/dat-lich">Chọn dịch vụ này</a>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
