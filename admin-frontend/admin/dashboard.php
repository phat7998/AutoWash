<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard | Auto Wash Loyalty</title>
  <style>
    :root { color-scheme: dark; --bg:#07111f; --panel:#111827; --line:rgba(148,163,184,.16); --text:#eff6ff; --muted:#cbd5e1; --blue:#38bdf8; --green:#4ade80; --orange:#fb923c; --red:#f87171; }
    *{box-sizing:border-box;font-family:Segoe UI, Arial, sans-serif;}
    body{margin:0;background:linear-gradient(135deg,#020617 0%,#07111f 45%,#111827 100%);color:var(--text);}
    a{text-decoration:none;color:inherit;}
    .shell{display:flex;min-height:100vh;}
    .sidebar{width:300px;border-right:1px solid var(--line);background:rgba(8,15,27,.96);padding:18px;display:flex;flex-direction:column;justify-content:space-between;}
    .brand{display:flex;align-items:center;gap:12px;}
    .logo{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,var(--blue),#2563eb);display:grid;place-items:center;font-weight:800;}
    .eyebrow{text-transform:uppercase;letter-spacing:.25em;color:#93c5fd;font-size:.7rem;}
    .nav{display:flex;flex-direction:column;gap:8px;margin-top:18px;}
    .nav a{padding:10px 12px;border-radius:12px;border:1px solid transparent;color:var(--text);} 
    .nav a.active,.nav a:hover{background:rgba(56,189,248,.12);border-color:rgba(56,189,248,.28);} 
    .mini-card{border:1px solid var(--line);border-radius:18px;padding:12px;background:linear-gradient(180deg, rgba(17,24,39,.98), rgba(15,23,42,.92));color:var(--muted);} 
    .main{flex:1;padding:18px;}
    .topbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;}
    .topbar h1{margin:4px 0 0;font-size:1.7rem;}
    .top-actions{display:flex;gap:10px;}
    .btn{padding:10px 12px;border-radius:12px;border:1px solid var(--line);background:rgba(148,163,184,.12);color:var(--text);cursor:pointer;font-weight:600;}
    .btn.primary{background:linear-gradient(135deg,var(--blue),#2563eb);border-color:transparent;}
    .btn.warn{background:rgba(248,113,113,.12);border-color:rgba(248,113,113,.25);color:#fecaca;}
    .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px;}
    .card{background:linear-gradient(180deg, rgba(17,24,39,.98), rgba(15,23,42,.92));border:1px solid var(--line);border-radius:22px;padding:14px;box-shadow:0 18px 40px rgba(2,6,23,.35);} 
    .card h3{margin:0 0 8px;font-size:1.05rem;}
    .metric{font-size:1.8rem;font-weight:800;}
    .sub{color:var(--muted);font-size:.92rem;}
    .panel{display:grid;grid-template-columns:1.15fr .85fr;gap:14px;}
    table{width:100%;border-collapse:collapse;}
    th,td{padding:10px 8px;border-bottom:1px solid var(--line);text-align:left;font-size:.94rem;}
    th{color:#dbeafe;}
    .badge{display:inline-block;padding:6px 10px;border-radius:999px;font-weight:700;font-size:.82rem;}
    .badge.pending{background:rgba(251,146,60,.12);color:#fed7aa;} .badge.washing{background:rgba(56,189,248,.12);color:#bae6fd;} .badge.done{background:rgba(74,222,128,.12);color:#bbf7d0;} .badge.cancelled{background:rgba(248,113,113,.12);color:#fecaca;}
    .status-note{margin-top:8px;font-size:.95rem;color:#bfdbfe;}
    .small{font-size:.92rem;color:var(--muted);}
    .footer-note{margin-top:10px;color:var(--muted);font-size:.92rem;}
    .actions button{border:none;border-radius:10px;padding:8px 10px;background:rgba(56,189,248,.12);color:#dbeafe;cursor:pointer;margin-right:6px;font-weight:600;}
    .actions button.complete{background:rgba(74,222,128,.15);color:#bbf7d0;}
    .actions button.delete{background:rgba(248,113,113,.12);color:#fecaca;}
    @media (max-width: 1100px){.grid{grid-template-columns:1fr 1fr;} .panel{grid-template-columns:1fr;}}
    @media (max-width: 760px){.shell{flex-direction:column;} .sidebar{width:100%;border-right:none;border-bottom:1px solid var(--line);} .grid{grid-template-columns:1fr;}}
  </style>
</head>
<body>
  <div class="shell">
    <aside class="sidebar">
      <div>
        <div class="brand">
          <div class="logo">A</div>
          <div>
            <p class="eyebrow">Admin</p>
            <h3 style="margin:4px 0 0;">Auto Wash Loyalty</h3>
          </div>
        </div>
        <nav class="nav">
          <a class="active" href="dashboard.php">Tổng quan</a>
          <a href="#bookings">Booking</a>
          <a href="#analytics">Phân tích</a>
          <a href="login.php">Đăng xuất</a>
        </nav>
      </div>
      <div class="mini-card">
        <p class="eyebrow">Tình trạng</p>
        <strong>Đang dùng API admin production</strong>
        <p>Dashboard sẽ lấy dữ liệu thật từ backend, hỗ trợ loyalty tier, khuyến mãi và hoàn thành booking.</p>
      </div>
    </aside>

    <main class="main">
      <header class="topbar">
        <div>
          <p class="eyebrow">Xin chào, quản trị viên</p>
          <h1>Bảng điều khiển quản trị</h1>
        </div>
        <div class="top-actions">
          <button class="btn" id="refreshBtn" type="button">Tải lại dữ liệu</button>
          <a class="btn primary" href="login.php">Đăng xuất</a>
        </div>
      </header>

      <section class="grid">
        <article class="card"><h3>Tổng booking</h3><div id="totalBookings" class="metric">0</div><div class="sub">Số đơn hiện có trong hệ thống</div></article>
        <article class="card"><h3>Đang chờ xử lý</h3><div id="pendingCount" class="metric">0</div><div class="sub">Cần ưu tiên phục vụ</div></article>
        <article class="card"><h3>Hoàn tất</h3><div id="doneCount" class="metric">0</div><div class="sub">Đã hoàn thành dịch vụ</div></article>
        <article class="card"><h3>Tier rules</h3><div id="tierCount" class="metric">0</div><div class="sub">Số hạng loyalty đang cấu hình</div></article>
      </section>

      <section class="panel">
        <article class="card" id="bookings">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:8px;">
            <div>
              <p class="eyebrow">Booking</p>
              <h3 style="margin:4px 0 0;">Danh sách booking</h3>
            </div>
            <span class="small" id="apiStatus">Đang kết nối API...</span>
          </div>
          <div class="small">Dashboard admin đang dùng API thực tế từ backend. Bạn có thể bấm “Hoàn tất” để tích điểm loyalty cho booking.</div>
          <div style="overflow-x:auto;margin-top:10px;">
            <table>
              <thead>
                <tr>
                  <th>Mã đơn</th>
                  <th>Khách hàng</th>
                  <th>Xe</th>
                  <th>Gói</th>
                  <th>Trạng thái</th>
                  <th>Hành động</th>
                </tr>
              </thead>
              <tbody id="bookingTableBody"></tbody>
            </table>
          </div>
        </article>

        <article class="card" id="analytics">
          <p class="eyebrow">Mô tả</p>
          <h3 style="margin:4px 0 12px;">Thông tin admin hiện tại</h3>
          <ul class="small" style="padding-left:18px;line-height:1.5;">
            <li>Đăng nhập bằng tài khoản admin thật của backend.</li>
            <li>Gọi API /bookings, /tier-rules và /promotions.</li>
            <li>Hoàn thành booking sẽ tích điểm loyalty cho khách hàng.</li>
            <li>Chuẩn bị nền cho loyalty tier và ưu tiên booking.</li>
          </ul>
          <div class="status-note" id="statusNote">Đang chờ kết nối API admin...</div>
          <div id="tierRulesBox" class="small" style="margin-top:10px;"></div>
          <div id="promoBox" class="small" style="margin-top:10px;"></div>
        </article>
      </section>

      <p class="footer-note">Ghi chú: nếu backend chưa bật, dashboard sẽ hiển thị thông báo lỗi và bạn cần nhập đúng tài khoản admin được cung cấp bởi nhóm.</p>
    </main>
  </div>

  <script src="../assets/js/admin.js"></script>
</body>
</html>
