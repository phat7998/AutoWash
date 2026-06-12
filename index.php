<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoWash Pro - Rua Xe Thong Minh</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">AutoWash<span>Pro</span></a>
    <ul class="nav-links">
        <?php if ($isLoggedIn): ?>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="booking_form.php">Dat lich</a></li>
            <li><a href="history.php">Lich su</a></li>
            <li><a href="dashboard.php" class="btn-nav">Tai khoan</a></li>
        <?php else: ?>
            <li><a href="login.php">Dang nhap</a></li>
            <li><a href="register.php" class="btn-nav">Dang ky</a></li>
        <?php endif; ?>
    </ul>
</nav>

<section class="hero">
    <h1>Rua Xe Thong Minh - Dat Lich Truoc</h1>
    <p>Trai nghiem dich vu rua xe chuyen nghiep voi chuong trinh khach hang than thiet. Tich diem, nang cap, nhan uu dai.</p>
    <div class="hero-buttons">
        <?php if ($isLoggedIn): ?>
            <a href="booking_form.php" class="btn btn-white">Dat lich ngay</a>
            <a href="dashboard.php" class="btn btn-outline" style="color:#fff;border-color:#fff;">Dashboard</a>
        <?php else: ?>
            <a href="register.php" class="btn btn-white">Dang ky ngay</a>
            <a href="login.php" class="btn btn-outline" style="color:#fff;border-color:#fff;">Dang nhap</a>
        <?php endif; ?>
    </div>
</section>

<div class="container">
    <section class="features">
        <h2>Tinh nang noi bat</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">&#128663;</div>
                <h3>Dat lich truoc</h3>
                <p>Dat lich rua xe truoc tu 7 den 14 ngay tuy theo cap do thanh vien. Khong phai cho doi.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">&#11088;</div>
                <h3>Tich diem thuong</h3>
                <p>Moi lan rua xe deu tich diem. Diem co the doi thanh giam gia hoac rua xe mien phi.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">&#127942;</div>
                <h3>4 cap do thanh vien</h3>
                <p>Member, Silver, Gold, Platinum. Cap do cang cao, cang nhieu dac quyen va uu tien.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">&#128179;</div>
                <h3>Uu dai ca nhan</h3>
                <p>Nhan khuyen mai rieng biet dua tren lich su su dung va cap do thanh vien cua ban.</p>
            </div>
        </div>
    </section>

    <section style="text-align:center;padding:40px 0;">
        <h2 style="margin-bottom:32px;">Cap do thanh vien</h2>
        <div class="tier-cards">
            <div class="tier-card">
                <div class="tier-name" style="color:#64748b;">Member</div>
                <div class="tier-points">0 - 499 diem</div>
                <ul class="tier-perks">
                    <li>Dat lich truoc 7 ngay</li>
                    <li>Tich diem co ban</li>
                    <li>Uu dai sinh nhat</li>
                </ul>
            </div>
            <div class="tier-card">
                <div class="tier-name" style="color:#8b5cf6;">Silver</div>
                <div class="tier-points">500 - 1499 diem</div>
                <ul class="tier-perks">
                    <li>Dat lich truoc 10 ngay</li>
                    <li>Uu tien xep hang</li>
                    <li>Tang 10% diem</li>
                </ul>
            </div>
            <div class="tier-card">
                <div class="tier-name" style="color:#d97706;">Gold</div>
                <div class="tier-points">1500 - 2999 diem</div>
                <ul class="tier-perks">
                    <li>Dat lich truoc 12 ngay</li>
                    <li>Uu tien cao</li>
                    <li>Tang 20% diem</li>
                </ul>
            </div>
            <div class="tier-card">
                <div class="tier-name" style="color:#be185d;">Platinum</div>
                <div class="tier-points">3000+ diem</div>
                <ul class="tier-perks">
                    <li>Dat lich truoc 14 ngay</li>
                    <li>Uu tien cao nhat</li>
                    <li>Tang 30% diem</li>
                    <li>Rua xe mien phi hang thang</li>
                </ul>
            </div>
        </div>
    </section>
</div>

<footer class="footer">
    <p>&copy; 2026 AutoWash Pro. All rights reserved.</p>
</footer>

</body>
</html>
