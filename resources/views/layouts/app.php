<?php

declare(strict_types=1);

/** @var Closure(mixed): string $e */
/** @var string $content */
/** @var string|null $title */
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
                <a class="nav-link" href="/health">Trạng thái hệ thống</a>
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
