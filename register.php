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
    <title>Dang ky - AutoWash Pro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">AutoWash<span>Pro</span></div>
        <h2>Tao tai khoan</h2>
        <p class="auth-subtitle">Dang ky de dat lich rua xe va tich diem</p>

        <div id="registerError" class="alert alert-error"></div>

        <form id="registerForm">
            <div class="form-group">
                <label for="fullname">Ho va ten *</label>
                <input type="text" id="fullname" class="form-control" placeholder="Nguyen Van A" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">So dien thoai *</label>
                    <input type="tel" id="phone" class="form-control" placeholder="0912345678" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" class="form-control" placeholder="email@example.com">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Mat khau *</label>
                    <input type="password" id="password" class="form-control" placeholder="It nhat 6 ky tu" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Xac nhan mat khau *</label>
                    <input type="password" id="confirmPassword" class="form-control" placeholder="Nhap lai mat khau" required>
                </div>
            </div>

            <div class="card-header" style="margin-top:8px;">
                <h3>Thong tin xe (khong bat buoc)</h3>
            </div>

            <div class="form-group">
                <label for="licensePlate">Bien so xe</label>
                <input type="text" id="licensePlate" class="form-control" placeholder="59A1-12345">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="vehicleBrand">Hang xe</label>
                    <input type="text" id="vehicleBrand" class="form-control" placeholder="Honda">
                </div>
                <div class="form-group">
                    <label for="vehicleModel">Mau xe</label>
                    <input type="text" id="vehicleModel" class="form-control" placeholder="Vision">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Dang ky</button>
        </form>

        <p class="auth-footer">
            Da co tai khoan? <a href="login.php">Dang nhap</a>
        </p>
        <p class="auth-footer">
            <a href="index.php">&larr; Quay lai trang chu</a>
        </p>
    </div>
</div>

<script src="assets/js/customer.js"></script>
</body>
</html>
