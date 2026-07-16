<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var string $csrfToken */
/** @var array<string, string> $errors */
/** @var string $phone */
/** @var string $fullName */
?>
<section class="auth-shell" aria-labelledby="register-title">
    <div class="auth-intro">
        <p class="eyebrow dark-eyebrow">Tài khoản khách hàng</p>
        <h1 id="register-title">Bắt đầu cùng AutoWash Pro</h1>
        <p class="lead">
            Tạo tài khoản bằng số điện thoại để quản lý phương tiện và sử dụng các dịch vụ ở bước tiếp theo.
        </p>
    </div>
    <form class="form-card" method="post" action="/dang-ky" novalidate>
        <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
        <?php if ($errors !== []): ?>
            <div class="notification notification-error" role="alert" tabindex="-1">
                <strong>Thông tin chưa hợp lệ</strong>
                <span>Vui lòng kiểm tra các trường được đánh dấu bên dưới.</span>
            </div>
        <?php endif; ?>
        <div class="form-field">
            <label for="full_name">Họ và tên <span aria-hidden="true">*</span></label>
            <input
                id="full_name"
                name="full_name"
                type="text"
                value="<?= $e($fullName) ?>"
                minlength="2"
                maxlength="150"
                autocomplete="name"
                required
                <?= isset($errors['full_name']) ? 'aria-invalid="true" aria-describedby="full-name-error"' : '' ?>
            >
            <?php if (isset($errors['full_name'])): ?>
                <span id="full-name-error" class="field-error"><?= $e($errors['full_name']) ?></span>
            <?php endif; ?>
        </div>
        <div class="form-field">
            <label for="phone">Số điện thoại <span aria-hidden="true">*</span></label>
            <input
                id="phone"
                name="phone"
                type="tel"
                inputmode="numeric"
                value="<?= $e($phone) ?>"
                minlength="9"
                maxlength="15"
                pattern="[0-9]{9,15}"
                autocomplete="tel"
                required
                <?= isset($errors['phone']) ? 'aria-invalid="true" aria-describedby="phone-error"' : '' ?>
            >
            <span class="field-help">Nhập từ 9 đến 15 chữ số, không dùng khoảng trắng.</span>
            <?php if (isset($errors['phone'])): ?>
                <span id="phone-error" class="field-error"><?= $e($errors['phone']) ?></span>
            <?php endif; ?>
        </div>
        <div class="form-field">
            <label for="password">Mật khẩu <span aria-hidden="true">*</span></label>
            <input
                id="password"
                name="password"
                type="password"
                minlength="8"
                maxlength="72"
                autocomplete="new-password"
                required
                <?= isset($errors['password']) ? 'aria-invalid="true" aria-describedby="password-error"' : '' ?>
            >
            <span class="field-help">Dùng từ 8 đến 72 ký tự.</span>
            <?php if (isset($errors['password'])): ?>
                <span id="password-error" class="field-error"><?= $e($errors['password']) ?></span>
            <?php endif; ?>
        </div>
        <button class="button button-primary" type="submit">Tạo tài khoản</button>
        <p class="form-switch">Đã có tài khoản? <a href="/dang-nhap">Đăng nhập</a></p>
    </form>
</section>
