<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tier Rules | Auto Wash Loyalty</title>
  <link rel="stylesheet" href="../assets/css/admin.css" />
  
</head>
<body data-page="tier-rules">
  <div class="shell">
    
<aside class="sidebar">
  <div>
    <div class="brand">
      <div class="logo">A</div>
      <div>
        <p class="eyebrow">Admin</p>
        <h3>Auto Wash Loyalty</h3>
      </div>
    </div>
    <nav class="nav">
      <a data-nav="dashboard" href="dashboard.php">Tổng quan <span class="pill">Week 9</span></a>
      <a data-nav="tier-rules" href="tier_rules.php">Tier rules <span class="pill">Week 6</span></a>
      <a data-nav="promotions" href="promotions.php">Promotion <span class="pill">Week 6</span></a>
      <a data-nav="queue" href="queue.php">Priority Queue <span class="pill">Week 7</span></a>
      <a data-nav="analytics" href="analytics.php">Analytics <span class="pill">Week 8</span></a>
      <button type="button" data-logout>Đăng xuất</button>
    </nav>
  </div>
  <div class="mini-card">
    <p class="eyebrow">TV3 Admin FE</p>
    <strong>Tuần 6 → 10</strong>
    <p>Quản lý tier, khuyến mãi, hàng đợi ưu tiên, thống kê và hoàn thiện dashboard responsive.</p>
  </div>
</aside>

    <main class="main">
      <header class="topbar">
        <div>
          <p class="eyebrow">Xin chào, quản trị viên</p>
          <h1>Quản lý Tier Rules</h1>
          <div class="sub">Tuần 6: xem và sửa min_spend, min_visits, booking_window_days, priority.</div>
        </div>
        <div class="top-actions">
          <button class="btn" id="refreshBtn" type="button">Tải lại</button>
          <button class="btn danger" type="button" data-logout>Đăng xuất</button>
        </div>
      </header>
      
<section class="card">
  <div class="toolbar">
    <div>
      <p class="eyebrow">Week 6</p>
      <h3 style="margin:4px 0 0;">Bảng cấu hình Tier Rules</h3>
      <div class="small" id="tierStatus">Đang tải tier rules...</div>
    </div>
    <button class="btn warn" id="resetTierDemoBtn" type="button">Reset demo data</button>
  </div>
  <div class="small">Admin có thể sửa trực tiếp min_spend, min_visits, booking_window_days và priority. Nếu backend chưa sẵn, trang tự lưu vào localStorage để demo.</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Tier</th>
          <th>Min spend</th>
          <th>Min visits</th>
          <th>Booking window days</th>
          <th>Priority</th>
          <th>Trạng thái</th>
          <th>Hành động</th>
        </tr>
      </thead>
      <tbody id="tierRulesTableBody"></tbody>
    </table>
  </div>
</section>

<section class="card" style="margin-top:14px;">
  <p class="eyebrow">Gợi ý demo</p>
  <h3>Ý nghĩa các trường</h3>
  <div class="config-list">
    <div class="config-item"><span><strong>min_spend</strong><br><span class="sub">Tổng tiền khách cần chi để đạt hạng.</span></span></div>
    <div class="config-item"><span><strong>min_visits</strong><br><span class="sub">Số lần rửa xe tối thiểu để xét lên hạng.</span></span></div>
    <div class="config-item"><span><strong>booking_window_days</strong><br><span class="sub">Số ngày được ưu tiên đặt lịch trước hoặc giữ quyền lợi.</span></span></div>
    <div class="config-item"><span><strong>priority</strong><br><span class="sub">Điểm ưu tiên dùng cho Priority Queue: Platinum cao nhất.</span></span></div>
  </div>
</section>

    </main>
  </div>
  <div id="toast" class="toast"></div>
  <script src="../assets/js/admin.js"></script>
  
</body>
</html>
