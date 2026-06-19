<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - AutoWash Pro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">AutoWash<span>Pro</span></div>
        <h2>Đăng nhập</h2>
        <p class="auth-subtitle">Chào mừng quay trở lại!</p>

        <div id="loginError" class="alert alert-error"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="tel" id="phone" class="form-control" placeholder="0912345678" required>
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" class="form-control" placeholder="Nhập mật khẩu" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
        </form>

        <p class="auth-footer">
            Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
        </p>
        <p class="auth-footer">
            <a href="index.php">&larr; Quay lại trang chủ</a>
        </p>
    </div>
</div>

<script src="assets/js/customer.js"></script>
<script>
if (localStorage.getItem('customerToken')) {
    window.location.href = 'dashboard.php';
}
</script>
</body>
</html>
