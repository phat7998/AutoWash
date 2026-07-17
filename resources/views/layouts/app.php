<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var string $content */
/** @var string|null $title */
/** @var array<string, mixed>|null $authUser */
/** @var string|null $csrfToken */
$role = is_array($authUser ?? null) ? ($authUser['role'] ?? null) : null;
$isAdmin = $role === 'admin';
$isCustomer = $role === 'customer';
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
<body class="<?= $isAdmin ? 'admin-area' : ($isCustomer ? 'customer-area' : 'guest-area') ?>">
    <a class="skip-link" href="#noi-dung-chinh">Bỏ qua đến nội dung chính</a>
    <?php if (!$isAdmin): ?>
    <header class="site-header" data-site-header>
        <div class="container header-content">
            <a class="brand" href="/" aria-label="AutoWash Pro — Trang chủ">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" focusable="false"><path d="M8 20h16l-2-6H10l-2 6Zm2 0v4m12-4v4M6 20h20M12 14l2-4h4l2 4"/></svg>
                </span>
                <span><strong>AutoWash</strong><small>PRO</small></span>
            </a>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-navigation" data-nav-toggle>
                <span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>
                <span class="sr-only">Mở menu</span>
            </button>
            <nav id="site-navigation" class="site-navigation" aria-label="Điều hướng chính" data-navigation>
                <?php if ($isCustomer): ?>
                    <a class="nav-link" href="/tai-khoan">Tổng quan</a>
                    <a class="nav-link nav-link-emphasis" href="/dat-lich">Đặt lịch</a>
                    <a class="nav-link" href="/lich-dat">Lịch sử</a>
                    <a class="nav-link" href="/diem-thuong">Điểm thưởng</a>
                    <a class="nav-link" href="/doi-thuong">Quà tặng</a>
                    <a class="nav-link" href="/phuong-tien">Phương tiện</a>
                    <a class="nav-link" href="/khung-gio">Khung giờ</a>
                    <form class="nav-form" method="post" action="/dang-xuat">
                        <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken ?? '') ?>">
                        <button class="nav-link nav-button" type="submit">Đăng xuất</button>
                    </form>
                <?php else: ?>
                    <a class="nav-link" href="/">Trang chủ</a>
                    <a class="nav-link" href="/dich-vu">Dịch vụ</a>
                    <a class="nav-link" href="/dang-nhap">Đăng nhập</a>
                    <a class="button button-primary header-action" href="/dat-lich">Đặt lịch ngay</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <?php else: ?>
    <header class="admin-mobile-header">
        <a class="brand brand-inverse" href="/admin" aria-label="AutoWash Pro — Quản trị">
            <span class="brand-mark" aria-hidden="true">AW</span><span>AutoWash Pro</span>
        </a>
        <button class="admin-menu-toggle" type="button" aria-expanded="false" aria-controls="admin-sidebar" data-admin-toggle>
            <span class="menu-lines" aria-hidden="true"><i></i><i></i><i></i></span><span class="sr-only">Mở menu quản trị</span>
        </button>
    </header>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
    <div class="admin-shell">
        <aside id="admin-sidebar" class="admin-sidebar" aria-label="Điều hướng quản trị" data-admin-sidebar>
            <a class="brand brand-inverse admin-brand" href="/admin">
                <span class="brand-mark" aria-hidden="true">AW</span><span>AutoWash <small>PRO</small></span>
            </a>
            <nav class="admin-navigation" aria-label="Các phân hệ quản trị">
                <p class="admin-nav-group">Vận hành</p>
                <a class="admin-nav-link" href="/admin">Tổng quan</a>
                <a class="admin-nav-link" href="/admin/lich-dat">Lịch đặt</a>
                <a class="admin-nav-link" href="/admin/dich-vu">Dịch vụ</a>
                <a class="admin-nav-link" href="/admin/khung-gio">Khung giờ</a>
                <p class="admin-nav-group">Khách hàng thân thiết</p>
                <a class="admin-nav-link" href="/admin/diem-thuong">Khách hàng &amp; điểm</a>
                <a class="admin-nav-link" href="/admin/xet-hang">Xét hạng</a>
                <a class="admin-nav-link" href="/admin/hang-thanh-vien">Hạng &amp; quyền lợi</a>
                <a class="admin-nav-link" href="/admin/promotion">Khuyến mãi</a>
                <a class="admin-nav-link" href="/admin/reward">Quà tặng</a>
                <p class="admin-nav-group">Phân tích</p>
                <a class="admin-nav-link" href="/admin#bao-cao">Báo cáo</a>
            </nav>
            <div class="admin-account">
                <span><?= $e($authUser['full_name'] ?? 'Quản trị viên') ?></span>
                <form method="post" action="/dang-xuat">
                    <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken ?? '') ?>">
                    <button class="admin-logout" type="submit">Đăng xuất</button>
                </form>
            </div>
        </aside>
        <button class="admin-overlay" type="button" aria-label="Đóng menu quản trị" data-admin-overlay></button>
    <?php endif; ?>
    <main id="noi-dung-chinh" class="container page-content" tabindex="-1">
        <?= $content ?>
    </main>
    <?php if ($isAdmin): ?></div><?php endif; ?>
    <?php if (!$isAdmin): ?>
    <footer class="site-footer">
        <div class="container footer-content">
            <div><strong>AutoWash Pro</strong><span>Chăm sóc phương tiện, chủ động từng khung giờ.</span></div>
            <nav aria-label="Điều hướng chân trang"><a href="/dich-vu">Dịch vụ</a><a href="/dat-lich">Đặt lịch</a><a href="<?= $isCustomer ? '/tai-khoan' : '/dang-nhap' ?>">Tài khoản</a></nav>
        </div>
    </footer>
    <?php endif; ?>
    <script src="/assets/js/app.js" defer></script>
</body>
</html>
