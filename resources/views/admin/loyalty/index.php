<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var list<array<string, mixed>> $customers */
/** @var array<string, string> $values */
/** @var array<string, string> $errors */
/** @var string $csrfToken */
/** @var string|null $flashSuccess */
?>
<section class="page-heading">
    <p class="eyebrow dark-eyebrow">Chăm sóc khách hàng</p>
    <h1>Điều chỉnh điểm khách hàng</h1>
    <p class="lead">Cộng hoặc trừ điểm cho khách hàng với lý do rõ ràng; hệ thống không cho phép số dư âm.</p>
</section>

<?php if (isset($flashSuccess) && $flashSuccess !== ''): ?>
    <div class="notification notification-success" role="status">
        <strong>Thành công</strong><span><?= $e($flashSuccess) ?></span>
    </div>
<?php endif; ?>

<?php if ($customers === []): ?>
    <section class="empty-state">
        <h2>Không có khách hàng đang hoạt động</h2>
        <p>Chỉ tài khoản customer đang hoạt động mới có thể được điều chỉnh điểm.</p>
    </section>
<?php else: ?>
    <form class="form-card loyalty-adjustment-form" method="post" action="/admin/diem-thuong/dieu-chinh" novalidate>
        <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">

        <?php if ($errors !== []): ?>
            <div class="notification notification-error" role="alert">
                <strong>Chưa thể điều chỉnh điểm</strong>
                <span>Kiểm tra lại các trường được đánh dấu bên dưới.</span>
            </div>
        <?php endif; ?>

        <div class="form-field">
            <label for="user_id">Khách hàng <span aria-hidden="true">*</span></label>
            <select id="user_id" name="user_id" required aria-invalid="<?= isset($errors['user_id']) ? 'true' : 'false' ?>">
                <option value="">Chọn khách hàng</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= $e($customer['id']) ?>" <?= $values['user_id'] === (string) $customer['id'] ? 'selected' : '' ?>>
                        <?= $e($customer['full_name']) ?> · <?= $e($customer['phone']) ?> · <?= $e($customer['point_balance']) ?> điểm
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['user_id'])): ?><span class="field-error"><?= $e($errors['user_id']) ?></span><?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-field">
                <label for="points">Số điểm thay đổi <span aria-hidden="true">*</span></label>
                <input id="points" name="points" type="number" step="1" required value="<?= $e($values['points']) ?>" aria-invalid="<?= isset($errors['points']) ? 'true' : 'false' ?>">
                <span class="field-help">Nhập số dương để cộng, số âm để trừ. Không cho phép số 0.</span>
                <?php if (isset($errors['points'])): ?><span class="field-error"><?= $e($errors['points']) ?></span><?php endif; ?>
            </div>
            <div class="form-field">
                <label for="source_transaction_id">Mã giao dịch liên quan</label>
                <input id="source_transaction_id" name="source_transaction_id" inputmode="numeric" value="<?= $e($values['source_transaction_id']) ?>" aria-invalid="<?= isset($errors['source_transaction_id']) ? 'true' : 'false' ?>">
                <span class="field-help">Không bắt buộc; chỉ dùng khi điều chỉnh một giao dịch trước đó của cùng khách hàng.</span>
                <?php if (isset($errors['source_transaction_id'])): ?><span class="field-error"><?= $e($errors['source_transaction_id']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="form-field">
            <label for="reason">Lý do <span aria-hidden="true">*</span></label>
            <textarea id="reason" name="reason" maxlength="1000" required aria-invalid="<?= isset($errors['reason']) ? 'true' : 'false' ?>"><?= $e($values['reason']) ?></textarea>
            <?php if (isset($errors['reason'])): ?><span class="field-error"><?= $e($errors['reason']) ?></span><?php endif; ?>
        </div>

        <div class="form-actions">
            <button class="button button-primary" type="submit">Ghi nhận điều chỉnh</button>
            <a class="button button-outline" href="/admin">Hủy</a>
        </div>
    </form>
<?php endif; ?>
