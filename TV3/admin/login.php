<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login | Auto Wash Loyalty</title>
  <link rel="stylesheet" href="../assets/css/admin.css" />
</head>
<body class="login-body" data-page="login">
  <main class="login-card">
    <p class="eyebrow">Admin Panel</p>
    <h1>Đăng nhập quản trị Auto Wash</h1>
    <p class="sub">Trang này dùng cho admin xem booking, cập nhật trạng thái, quản lý tier rules, promotion, priority queue và analytics.</p>

    <div id="messageBox" class="alert" style="display:none;"></div>

    <form id="loginForm" autocomplete="off">
      <label for="username">Tên đăng nhập</label>
      <input id="username" name="username" type="text" placeholder="admin" required />

      <label for="password" style="margin-top:12px;">Mật khẩu</label>
      <input id="password" name="password" type="password" placeholder="your-password" required />

      <button class="btn primary" type="submit" style="width:100%;margin-top:16px;">Đăng nhập</button>
    </form>

    <div class="hint">Nếu backend chưa bật, dùng tài khoản demo <strong>admin / admin123</strong> để test toàn bộ phần TV3.</div>
  </main>
  <script src="../assets/js/admin.js"></script>
</body>
</html>
