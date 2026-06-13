<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoWash Pro - Rửa Xe Thông Minh</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">AutoWash<span>Pro</span></a>
    <ul class="nav-links">
        <?php if ($isLoggedIn): ?>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="booking_form.php">Đặt lịch</a></li>
            <li><a href="history.php">Lịch sử</a></li>
            <li><a href="dashboard.php" class="btn-nav">Tài khoản</a></li>
        <?php else: ?>
            <li><a href="login.php">Đăng nhập</a></li>
            <li><a href="register.php" class="btn-nav">Đăng ký</a></li>
        <?php endif; ?>
    </ul>
</nav>

<section class="hero">
    <h1>Rửa Xe Thông Minh - Đặt Lịch Trước</h1>
    <p>Trải nghiệm dịch vụ rửa xe chuyên nghiệp với chương trình khách hàng thân thiết. Tích điểm, nâng cấp, nhận ưu đãi.</p>
    <div class="hero-buttons">
        <?php if ($isLoggedIn): ?>
            <a href="booking_form.php" class="btn btn-white">Đặt lịch ngay</a>
            <a href="dashboard.php" class="btn btn-outline" style="color:#fff;border-color:#fff;">Dashboard</a>
        <?php else: ?>
            <a href="register.php" class="btn btn-white">Đăng ký ngay</a>
            <a href="login.php" class="btn btn-outline" style="color:#fff;border-color:#fff;">Đăng nhập</a>
        <?php endif; ?>
    </div>
</section>

<div class="container">
    <section class="features">
        <h2>Tính năng nổi bật</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">&#128663;</div>
                <h3>Đặt lịch trước</h3>
                <p>Đặt lịch rửa xe trước từ 7 đến 14 ngày tùy theo cấp độ thành viên. Không phải chờ đợi.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">&#11088;</div>
                <h3>Tích điểm thưởng</h3>
                <p>Mỗi lần rửa xe đều tích điểm. Điểm có thể đổi thành giảm giá hoặc rửa xe miễn phí.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">&#127942;</div>
                <h3>4 cấp độ thành viên</h3>
                <p>Member, Silver, Gold, Platinum. Cấp độ càng cao, càng nhiều đặc quyền và ưu tiên.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">&#128179;</div>
                <h3>Ưu đãi cá nhân</h3>
                <p>Nhận khuyến mãi riêng biệt dựa trên lịch sử sử dụng và cấp độ thành viên của bạn.</p>
            </div>
        </div>
    </section>

    <section style="text-align:center;padding:40px 0;">
        <h2 style="margin-bottom:32px;">Cấp độ thành viên</h2>
        <div class="tier-cards">
            <div class="tier-card">
                <div class="tier-name" style="color:#64748b;">Member</div>
                <div class="tier-points">0 - 499 điểm</div>
                <ul class="tier-perks">
                    <li>Đặt lịch trước 7 ngày</li>
                    <li>Tích điểm cơ bản</li>
                    <li>Ưu đãi sinh nhật</li>
                </ul>
            </div>
            <div class="tier-card">
                <div class="tier-name" style="color:#8b5cf6;">Silver</div>
                <div class="tier-points">500 - 1499 điểm</div>
                <ul class="tier-perks">
                    <li>Đặt lịch trước 10 ngày</li>
                    <li>Ưu tiên xếp hàng</li>
                    <li>Tặng 10% điểm</li>
                </ul>
            </div>
            <div class="tier-card">
                <div class="tier-name" style="color:#d97706;">Gold</div>
                <div class="tier-points">1500 - 2999 điểm</div>
                <ul class="tier-perks">
                    <li>Đặt lịch trước 12 ngày</li>
                    <li>Ưu tiên cao</li>
                    <li>Tặng 20% điểm</li>
                </ul>
            </div>
            <div class="tier-card">
                <div class="tier-name" style="color:#be185d;">Platinum</div>
                <div class="tier-points">3000+ điểm</div>
                <ul class="tier-perks">
                    <li>Đặt lịch trước 14 ngày</li>
                    <li>Ưu tiên cao nhất</li>
                    <li>Tặng 30% điểm</li>
                    <li>Rửa xe miễn phí hàng tháng</li>
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
