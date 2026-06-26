<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard | Auto Wash Loyalty</title>
  <link rel="stylesheet" href="../assets/css/admin.css" />
  
</head>
<body data-page="dashboard">
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
          <h1>Bảng điều khiển quản trị</h1>
          <div class="sub">Tổng quan booking, cấu hình loyalty, queue và analytics cho TV3.</div>
        </div>
        <div class="top-actions">
          <button class="btn" id="refreshBtn" type="button">Tải lại</button>
          <button class="btn danger" type="button" data-logout>Đăng xuất</button>
        </div>
      </header>
      
<section class="grid">
  <article class="card"><h3>Tổng booking</h3><div id="totalBookings" class="metric">0</div><div class="sub">Số đơn hiện có trong hệ thống</div></article>
  <article class="card"><h3>Đang chờ xử lý</h3><div id="pendingCount" class="metric">0</div><div class="sub">Cần ưu tiên phục vụ</div></article>
  <article class="card"><h3>Hoàn tất</h3><div id="doneCount" class="metric">0</div><div class="sub">Đã hoàn thành dịch vụ</div></article>
  <article class="card"><h3>Tier rules</h3><div id="tierCount" class="metric">0</div><div class="sub">Số hạng loyalty đang cấu hình</div></article>
</section>

<section class="grid three">
  <article class="card">
    <p class="eyebrow">Config</p>
    <h3>Quản lý cấu hình loyalty</h3>
    <div class="config-list">
      <a class="config-item" href="tier_rules.php"><span><strong>Tier Rules</strong><br><span class="sub">Sửa min_spend, min_visits, booking window, priority.</span></span><span>→</span></a>
      <a class="config-item" href="promotions.php"><span><strong>Promotions</strong><br><span class="sub">Thêm, sửa, xóa khuyến mãi.</span></span><span>→</span></a>
    </div>
  </article>
  <article class="card">
    <p class="eyebrow">Operation</p>
    <h3>Priority Queue</h3>
    <div class="sub">Xem hàng đợi theo ngày và lọc theo hạng khách hàng.</div>
    <a class="btn primary" style="margin-top:12px;" href="queue.php">Mở queue</a>
  </article>
  <article class="card">
    <p class="eyebrow">Analytics</p>
    <h3>Dashboard thống kê</h3>
    <div class="sub">Biểu đồ phân bố tier, doanh thu, booking theo giờ, top khách hàng.</div>
    <a class="btn primary" style="margin-top:12px;" href="analytics.php">Xem analytics</a>
  </article>
</section>

<section class="card" id="bookings" style="margin-top:14px;">
  <div class="toolbar">
    <div>
      <p class="eyebrow">Booking</p>
      <h3 style="margin:4px 0 0;">Danh sách booking</h3>
      <div class="small" id="apiStatus">Đang kết nối API...</div>
    </div>
    <div class="filters">
      <label>Tìm kiếm
        <input id="bookingSearch" type="search" placeholder="Tên, biển số, mã đơn..." />
      </label>
      <label>Trạng thái
        <select id="bookingStatusFilter">
          <option value="ALL">Tất cả</option>
          <option value="PENDING">Đang chờ</option>
          <option value="CONFIRMED">Đã xác nhận</option>
          <option value="IN_PROGRESS">Đang rửa</option>
          <option value="COMPLETED">Hoàn tất</option>
          <option value="CANCELLED">Đã hủy</option>
        </select>
      </label>
      <label>Tier
        <select id="bookingTierFilter">
          <option value="ALL">Tất cả</option>
          <option value="Member">Member</option>
          <option value="Silver">Silver</option>
          <option value="Gold">Gold</option>
          <option value="Platinum">Platinum</option>
        </select>
      </label>
    </div>
  </div>
  <div class="small">Có phân trang, search và filter để đáp ứng phần hoàn thiện Admin tuần 9.</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Mã đơn</th>
          <th>Khách hàng</th>
          <th>Tier</th>
          <th>Xe</th>
          <th>Dịch vụ</th>
          <th>Lịch hẹn</th>
          <th>Trạng thái</th>
          <th>Hành động</th>
        </tr>
      </thead>
      <tbody id="bookingTableBody"></tbody>
    </table>
  </div>
  <div id="bookingPagination" class="pagination"></div>
</section>

<section class="grid two" style="margin-top:14px;">
  <article class="card">
    <p class="eyebrow">Tóm tắt cấu hình</p>
    <h3>Tier Rules</h3>
    <div id="tierRulesBox" class="small"></div>
  </article>
  <article class="card">
    <p class="eyebrow">Khuyến mãi</p>
    <h3>Promotion đang có</h3>
    <div id="promoBox" class="small"></div>
  </article>
</section>
<p class="footer-note" id="statusNote">Đang chờ kết nối API admin...</p>

    </main>
  </div>
  <div id="toast" class="toast"></div>
  <script src="../assets/js/admin.js"></script>
  
</body>
</html>
