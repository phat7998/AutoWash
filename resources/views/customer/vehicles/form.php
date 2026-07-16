<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var list<array{id: int, code: string, display_name: string}> $vehicleTypes */
/** @var array<string, string> $values */
/** @var array<string, string> $errors */
/** @var string $mode */
/** @var int|null $vehicleId */
/** @var string $csrfToken */
$isEdit = $mode === 'edit';
$action = $isEdit ? '/phuong-tien/' . $vehicleId . '/sua' : '/phuong-tien/them';
?>
<section class="form-page">
    <div class="page-heading">
        <p class="eyebrow dark-eyebrow">Phương tiện</p>
        <h1><?= $isEdit ? 'Sửa thông tin phương tiện' : 'Thêm phương tiện' ?></h1>
        <p class="lead">
            Biển số được chuẩn hóa ở backend để kiểm tra trùng. Luồng này là nhập thủ công, không phải nhận diện ảnh.
        </p>
    </div>

    <form class="form-card vehicle-form" method="post" action="<?= $e($action) ?>" novalidate>
        <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">

        <?php if ($errors !== []): ?>
            <div class="notification notification-error" role="alert" tabindex="-1">
                <strong>Chưa thể lưu phương tiện</strong>
                <span>Vui lòng kiểm tra các trường được đánh dấu bên dưới.</span>
            </div>
        <?php endif; ?>

        <div class="form-field">
            <label for="vehicle_type_id">Loại phương tiện <span aria-hidden="true">*</span></label>
            <select
                id="vehicle_type_id"
                name="vehicle_type_id"
                required
                aria-invalid="<?= isset($errors['vehicle_type_id']) ? 'true' : 'false' ?>"
                <?= isset($errors['vehicle_type_id']) ? 'aria-describedby="vehicle-type-error"' : '' ?>
            >
                <option value="">Chọn loại phương tiện</option>
                <?php foreach ($vehicleTypes as $type): ?>
                    <option
                        value="<?= $e($type['id']) ?>"
                        <?= $values['vehicle_type_id'] === (string) $type['id'] ? 'selected' : '' ?>
                    ><?= $e($type['display_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['vehicle_type_id'])): ?>
                <span class="field-error" id="vehicle-type-error"><?= $e($errors['vehicle_type_id']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="display_plate">Biển số <span aria-hidden="true">*</span></label>
            <input
                id="display_plate"
                name="display_plate"
                type="text"
                value="<?= $e($values['display_plate']) ?>"
                maxlength="30"
                pattern="[0-9]{2}[\s.\-]*[A-Za-z]{1,2}(?:[\s.\-]*[0-9]){4,5}"
                autocomplete="off"
                required
                aria-invalid="<?= isset($errors['display_plate']) ? 'true' : 'false' ?>"
                aria-describedby="plate-help<?= isset($errors['display_plate']) ? ' plate-error' : '' ?>"
            >
            <span class="field-help" id="plate-help">
                Hỗ trợ biển dân sự thông dụng: 2 số, 1–2 chữ cái và 4–5 số; có thể nhập khoảng trắng, dấu chấm hoặc gạch ngang.
            </span>
            <?php if (isset($errors['display_plate'])): ?>
                <span class="field-error" id="plate-error"><?= $e($errors['display_plate']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-field">
                <label for="brand">Hãng xe</label>
                <input
                    id="brand"
                    name="brand"
                    type="text"
                    value="<?= $e($values['brand']) ?>"
                    maxlength="100"
                    aria-invalid="<?= isset($errors['brand']) ? 'true' : 'false' ?>"
                    <?= isset($errors['brand']) ? 'aria-describedby="brand-error"' : '' ?>
                >
                <?php if (isset($errors['brand'])): ?>
                    <span class="field-error" id="brand-error"><?= $e($errors['brand']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-field">
                <label for="model">Dòng xe</label>
                <input
                    id="model"
                    name="model"
                    type="text"
                    value="<?= $e($values['model']) ?>"
                    maxlength="100"
                    aria-invalid="<?= isset($errors['model']) ? 'true' : 'false' ?>"
                    <?= isset($errors['model']) ? 'aria-describedby="model-error"' : '' ?>
                >
                <?php if (isset($errors['model'])): ?>
                    <span class="field-error" id="model-error"><?= $e($errors['model']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-field">
            <label for="notes">Ghi chú</label>
            <textarea
                id="notes"
                name="notes"
                rows="4"
                maxlength="1000"
                aria-invalid="<?= isset($errors['notes']) ? 'true' : 'false' ?>"
                <?= isset($errors['notes']) ? 'aria-describedby="notes-error"' : '' ?>
            ><?= $e($values['notes']) ?></textarea>
            <?php if (isset($errors['notes'])): ?>
                <span class="field-error" id="notes-error"><?= $e($errors['notes']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button class="button button-primary" type="submit">
                <?= $isEdit ? 'Lưu thay đổi' : 'Thêm phương tiện' ?>
            </button>
            <a class="button button-outline" href="/phuong-tien">Quay lại danh sách</a>
        </div>
    </form>
</section>
