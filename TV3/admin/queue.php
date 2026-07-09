<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Priority Queue | Auto Wash Loyalty</title>
  <link rel="stylesheet" href="../assets/css/admin.css" />
  
</head>
<body data-page="queue">
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
          <h1>Priority Queue</h1>
          <div class="sub">Tuần 7: xem hàng đợi ưu tiên theo ngày, tier và trạng thái.</div>
        </div>
        <div class="top-actions">
          <button class="btn" id="refreshBtn" type="button">Tải lại</button>
          <button class="btn danger" type="button" data-logout>Đăng xuất</button>
        </div>
      </header>
      
<section class="card">
  <div class="toolbar">
    <div>
      <p class="eyebrow">Week 7</p>
      <h3 style="margin:4px 0 0;">Priority Queue theo ngày</h3>
      <div class="small" id="queueStatus">Đang tải hàng đợi ưu tiên...</div>
    </div>
    <div class="filters">
      <label>Ngày
        <input id="queueDate" type="date" />
      </label>
      <label>Tier
        <select id="queueTierFilter">
          <option value="ALL">Tất cả</option>
          <option value="Member">Member</option>
          <option value="Silver">Silver</option>
          <option value="Gold">Gold</option>
          <option value="Platinum">Platinum</option>
        </select>
      </label>
      <label>Trạng thái
        <select id="queueStatusFilter">
          <option value="ALL">Tất cả</option>
          <option value="PENDING">Đang chờ</option>
          <option value="CONFIRMED">Đã xác nhận</option>
          <option value="IN_PROGRESS">Đang rửa</option>
          <option value="COMPLETED">Hoàn tất</option>
        </select>
      </label>
    </div>
  </div>
  <div class="small">Queue sắp xếp theo priority_score/tier. Hạng Platinum và Gold sẽ nằm trên khi cùng ngày đặt lịch.</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Mã đơn</th>
          <th>Khách hàng</th>
          <th>Tier</th>
          <th>Biển số</th>
          <th>Giờ hẹn</th>
          <th>Priority score</th>
          <th>Trạng thái</th>
        </tr>
      </thead>
      <tbody id="queueTableBody"></tbody>
    </table>
  </div>
</section>

    </main>
  </div>
  <div id="toast" class="toast"></div>
  <script src="../assets/js/admin.js"></script>
  
</body>
</html>
