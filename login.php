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
    <title>Dang nhap - AutoWash Pro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">AutoWash<span>Pro</span></div>
        <h2>Dang nhap</h2>
        <p class="auth-subtitle">Chao mung quay tro lai!</p>

        <div id="loginError" class="alert alert-error"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="phone">So dien thoai</label>
                <input type="tel" id="phone" class="form-control" placeholder="0912345678" required>
            </div>

            <div class="form-group">
                <label for="password">Mat khau</label>
                <input type="password" id="password" class="form-control" placeholder="Nhap mat khau" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Dang nhap</button>
        </form>

        <p class="auth-footer">
            Chua co tai khoan? <a href="register.php">Dang ky ngay</a>
        </p>
        <p class="auth-footer">
            <a href="index.php">&larr; Quay lai trang chu</a>
        </p>
    </div>
</div>

<script src="assets/js/customer.js"></script>
</body>
</html>
