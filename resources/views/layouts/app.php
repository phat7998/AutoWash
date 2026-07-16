<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var string $content */
/** @var string|null $title */
/** @var array<string, mixed>|null $authUser */
/** @var string|null $csrfToken */
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= $e($title ?? 'AutoWash Pro') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <a class="skip-link" href="#noi-dung-chinh">Bỏ qua đến nội dung chính</a>
    <header class="site-header">
        <div class="container header-content">
            <a class="brand" href="/" aria-label="AutoWash Pro — Trang chủ">
                <span class="brand-mark" aria-hidden="true">AW</span>
                <span>AutoWash Pro</span>
            </a>
            <nav aria-label="Điều hướng chính">
                <a class="nav-link" href="/">Trang chủ</a>
                <a class="nav-link" href="/dich-vu">Dịch vụ</a>
                <?php if (isset($authUser) && is_array($authUser)): ?>
                    <a class="nav-link" href="<?= ($authUser['role'] ?? null) === 'admin' ? '/admin' : '/tai-khoan' ?>">
                        Tổng quan
                    </a>
                    <?php if (($authUser['role'] ?? null) === 'customer'): ?>
                        <a class="nav-link" href="/dat-lich">Đặt lịch</a>
                        <a class="nav-link" href="/lich-dat">Lịch sử</a>
                        <a class="nav-link" href="/diem-thuong">Điểm</a>
                        <a class="nav-link" href="/doi-thuong">Reward</a>
                        <a class="nav-link" href="/phuong-tien">Phương tiện</a>
                        <a class="nav-link" href="/khung-gio">Khung giờ</a>
                    <?php else: ?>
                        <a class="nav-link" href="/admin/lich-dat">Lịch đặt</a>
                        <a class="nav-link" href="/admin/dich-vu">Dịch vụ</a>
                        <a class="nav-link" href="/admin/khung-gio">Khung giờ</a>
                        <a class="nav-link" href="/admin/diem-thuong">Điểm</a>
                        <a class="nav-link" href="/admin/reward">Reward</a>
                    <?php endif; ?>
                    <form class="nav-form" method="post" action="/dang-xuat">
                        <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken ?? '') ?>">
                        <button class="nav-link nav-button" type="submit">Đăng xuất</button>
                    </form>
                <?php else: ?>
                    <a class="nav-link" href="/dang-nhap">Đăng nhập</a>
                    <a class="button button-primary header-action" href="/dang-ky">Đăng ký</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main id="noi-dung-chinh" class="container page-content" tabindex="-1">
        <?= $content ?>
    </main>
    <footer class="site-footer">
        <div class="container">AutoWash Pro · Modern PHP 8.2+</div>
    </footer>
</body>
</html>
