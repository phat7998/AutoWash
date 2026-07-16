<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var string $csrfToken */
/** @var string|null $error */
/** @var string|null $flashSuccess */
/** @var string $phone */
?>
<section class="auth-shell" aria-labelledby="login-title">
    <div class="auth-intro">
        <p class="eyebrow dark-eyebrow">Chào mừng trở lại</p>
        <h1 id="login-title">Đăng nhập AutoWash Pro</h1>
        <p class="lead">Truy cập khu vực phù hợp với vai trò được cấp cho tài khoản của bạn.</p>
    </div>
    <form class="form-card" method="post" action="/dang-nhap" novalidate>
        <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
        <?php if (isset($flashSuccess) && is_string($flashSuccess)): ?>
            <div class="notification notification-success" role="status">
                <strong>Thành công</strong>
                <span><?= $e($flashSuccess) ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($error) && is_string($error)): ?>
            <div class="notification notification-error" role="alert">
                <strong>Không thể đăng nhập</strong>
                <span><?= $e($error) ?></span>
            </div>
        <?php endif; ?>
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
            >
        </div>
        <div class="form-field">
            <label for="password">Mật khẩu <span aria-hidden="true">*</span></label>
            <input
                id="password"
                name="password"
                type="password"
                minlength="8"
                maxlength="72"
                autocomplete="current-password"
                required
            >
        </div>
        <button class="button button-primary" type="submit">Đăng nhập</button>
        <p class="form-switch">Chưa có tài khoản? <a href="/dang-ky">Đăng ký ngay</a></p>
    </form>
</section>
