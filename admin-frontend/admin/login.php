<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login | Auto Wash Loyalty</title>
  <style>
    :root { color-scheme: dark; --bg:#07111f; --panel:#111827; --line:rgba(148,163,184,.18); --text:#eff6ff; --muted:#cbd5e1; --accent:#38bdf8; }
    *{box-sizing:border-box;font-family:Segoe UI, Arial, sans-serif;}
    body{margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(circle at top,#1e293b 0%, #07111f 50%, #020617 100%);color:var(--text);}
    .card{width:min(420px,92vw);background:linear-gradient(180deg, rgba(17,24,39,.98), rgba(15,23,42,.95));border:1px solid var(--line);border-radius:24px;box-shadow:0 18px 40px rgba(2,6,23,.55);padding:24px;}
    .eyebrow{text-transform:uppercase;letter-spacing:.28em;color:#93c5fd;font-size:.72rem;}
    h1{margin:8px 0 6px;font-size:1.5rem;}
    p{color:var(--muted);line-height:1.5;}
    label{display:block;margin-top:12px;font-weight:600;font-size:.95rem;}
    input{width:100%;margin-top:6px;padding:10px 12px;border:1px solid rgba(148,163,184,.2);border-radius:12px;background:#0f172a;color:var(--text);}
    button{width:100%;margin-top:16px;padding:10px 12px;border:none;border-radius:12px;background:linear-gradient(135deg,#38bdf8,#2563eb);color:white;font-weight:700;cursor:pointer;}
    .alert{margin-top:12px;padding:10px 12px;border-radius:12px;background:rgba(248,113,113,.12);color:#fecaca;border:1px solid rgba(248,113,113,.25);}
    .hint{margin-top:12px;font-size:.92rem;color:#bfdbfe;}
  </style>
</head>
<body>
  <main class="card">
    <p class="eyebrow">Admin Panel</p>
    <h1>Đăng nhập quản trị Auto Wash</h1>
    <p>Trang này dùng cho nhân viên admin xem booking, cập nhật trạng thái và quản lý loyalty cơ bản.</p>

    <div id="messageBox" class="alert" style="display:none;"></div>

    <form id="loginForm" autocomplete="off">
      <label for="username">Tên đăng nhập</label>
      <input id="username" name="username" type="text" placeholder="admin" required />

      <label for="password">Mật khẩu</label>
      <input id="password" name="password" type="password" placeholder="your-password" required />

      <button type="submit">Đăng nhập</button>
    </form>

    <div class="hint">Sử dụng tài khoản admin thật của backend khi có sẵn. Nếu backend chưa bật, bạn có thể dùng tài khoản demo <strong>admin / admin123</strong> để vào chế độ thử.</div>
  </main>

  <script>
    const ADMIN_API = 'http://localhost/AutoWash/backend/web';

    function showMessage(text, type = 'error') {
      const box = document.getElementById('messageBox');
      box.textContent = text;
      box.style.display = 'block';
      box.style.background = type === 'success' ? 'rgba(74,222,128,0.12)' : 'rgba(248,113,113,0.12)';
      box.style.borderColor = type === 'success' ? 'rgba(74,222,128,0.25)' : 'rgba(248,113,113,0.25)';
      box.style.color = type === 'success' ? '#bbf7d0' : '#fecaca';
    }

    document.getElementById('loginForm').addEventListener('submit', async (event) => {
      event.preventDefault();
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;

      showMessage('Đang đăng nhập...', 'success');

      try {
        const response = await fetch(`${ADMIN_API}/auth/login`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ username, password })
        });

        const result = await response.json().catch(() => ({}));

        const payload = result && typeof result === 'object' && 'data' in result ? result.data : result;
        const token = payload && (payload.access_token || payload.data?.access_token);

        if (response.ok && token) {
          localStorage.setItem('adminToken', token);
          localStorage.setItem('adminUser', JSON.stringify(payload.user || {}));
          showMessage('Đăng nhập thành công. Đang chuyển đến dashboard...', 'success');
          window.location.href = 'dashboard.php';
          return;
        }

        if (username === 'admin' && password === 'admin123') {
          localStorage.setItem('adminToken', 'demo-admin-token');
          localStorage.setItem('adminUser', JSON.stringify({ username: 'admin', role: 'demo' }));
          showMessage('Backend chưa sẵn — đang bật chế độ demo cho admin.', 'success');
          setTimeout(() => {
            window.location.href = 'dashboard.php';
          }, 400);
          return;
        }

        const message = result.message || 'Đăng nhập thất bại';
        showMessage(message, 'error');
      } catch (error) {
        console.error(error);
        showMessage('Không thể kết nối API admin. Hãy kiểm tra kết nối internet hoặc tài khoản backend.', 'error');
      }
    });
  </script>
</body>
</html>
