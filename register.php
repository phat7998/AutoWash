<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - AutoWash Pro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">AutoWash<span>Pro</span></div>
        <h2>Tạo tài khoản</h2>
        <p class="auth-subtitle">Đăng ký để đặt lịch rửa xe và tích điểm</p>

        <div id="registerError" class="alert alert-error"></div>

        <form id="registerForm">
            <div class="form-group">
                <label for="fullname">Họ và tên *</label>
                <input type="text" id="fullname" class="form-control" placeholder="Nguyễn Văn A" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Số điện thoại *</label>
                    <input type="tel" id="phone" class="form-control" placeholder="0912345678" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" class="form-control" placeholder="email@example.com">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Mật khẩu *</label>
                    <input type="password" id="password" class="form-control" placeholder="Ít nhất 6 ký tự" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Xác nhận mật khẩu *</label>
                    <input type="password" id="confirmPassword" class="form-control" placeholder="Nhập lại mật khẩu" required>
                </div>
            </div>

            <div class="card-header" style="margin-top:8px;">
                <h3>Thông tin xe (không bắt buộc)</h3>
            </div>

            <div class="form-group">
                <label for="licensePlate">Biển số xe</label>
                <input type="text" id="licensePlate" class="form-control" placeholder="59A1-12345">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="vehicleBrand">Hãng xe</label>
                    <input type="text" id="vehicleBrand" class="form-control" placeholder="Honda">
                </div>
                <div class="form-group">
                    <label for="vehicleModel">Mẫu xe</label>
                    <input type="text" id="vehicleModel" class="form-control" placeholder="Vision">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Đăng ký</button>
        </form>

        <p class="auth-footer">
            Đã có tài khoản? <a href="login.php">Đăng nhập</a>
        </p>
        <p class="auth-footer">
            <a href="index.php">&larr; Quay lại trang chủ</a>
        </p>
    </div>
</div>

<script src="assets/js/customer.js"></script>
</body>
</html>
