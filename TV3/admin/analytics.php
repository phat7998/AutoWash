<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Analytics | Auto Wash Loyalty</title>
  <link rel="stylesheet" href="../assets/css/admin.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body data-page="analytics">
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
          <h1>Admin Analytics Dashboard</h1>
          <div class="sub">Tuần 8: biểu đồ phân bố tier, doanh thu, booking theo giờ và top khách hàng.</div>
        </div>
        <div class="top-actions">
          <button class="btn" id="refreshBtn" type="button">Tải lại</button>
          <button class="btn danger" type="button" data-logout>Đăng xuất</button>
        </div>
      </header>
      
<section class="grid">
  <article class="card"><h3>Tổng booking</h3><div id="analyticsTotalBookings" class="metric">0</div><div class="sub">Theo dữ liệu admin</div></article>
  <article class="card"><h3>Doanh thu</h3><div id="analyticsRevenue" class="metric">0đ</div><div class="sub">Tổng giá trị booking hoàn tất</div></article>
  <article class="card"><h3>Khách hàng</h3><div id="analyticsCustomers" class="metric">0</div><div class="sub">Số khách hàng duy nhất</div></article>
  <article class="card"><h3>Retention</h3><div id="analyticsRetention" class="metric">0%</div><div class="sub">Tỷ lệ quay lại ước tính</div></article>
</section>

<section class="grid two">
  <article class="card">
    <p class="eyebrow">Week 8</p>
    <h3>Phân bố tier</h3>
    <div class="chart-box"><canvas id="tierDistributionChart"></canvas></div>
  </article>
  <article class="card">
    <p class="eyebrow">Doanh thu</p>
    <h3>Doanh thu theo ngày</h3>
    <div class="chart-box"><canvas id="revenueChart"></canvas></div>
  </article>
</section>

<section class="grid two" style="margin-top:14px;">
  <article class="card">
    <p class="eyebrow">Booking</p>
    <h3>Booking theo giờ</h3>
    <div class="chart-box"><canvas id="bookingHourChart"></canvas></div>
  </article>
  <article class="card">
    <p class="eyebrow">Top khách hàng</p>
    <h3>Khách hàng thân thiết</h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Khách hàng</th><th>Tier</th><th>Số lượt</th><th>Tổng chi</th></tr></thead>
        <tbody id="topCustomersTableBody"></tbody>
      </table>
    </div>
  </article>
</section>
<p class="footer-note" id="analyticsStatus">Đang tải analytics...</p>

    </main>
  </div>
  <div id="toast" class="toast"></div>
  <script src="../assets/js/admin.js"></script>
  
</body>
</html>
