<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Promotions | Auto Wash Loyalty</title>
  <link rel="stylesheet" href="../assets/css/admin.css" />
  
</head>
<body data-page="promotions">
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
          <h1>Quản lý Promotions</h1>
          <div class="sub">Tuần 6: thêm, sửa, xóa promotion cho chương trình loyalty.</div>
        </div>
        <div class="top-actions">
          <button class="btn" id="refreshBtn" type="button">Tải lại</button>
          <button class="btn danger" type="button" data-logout>Đăng xuất</button>
        </div>
      </header>
      
<section class="grid two">
  <article class="card">
    <p class="eyebrow">Week 6</p>
    <h3 id="promotionFormTitle">Thêm khuyến mãi</h3>
    <form id="promotionForm" class="form-grid" autocomplete="off">
      <input type="hidden" id="promotionId" />
      <label>Tên khuyến mãi
        <input id="promotionName" required placeholder="Khuyến mãi cuối tuần" />
      </label>
      <label>Mã code
        <input id="promotionCode" required placeholder="WEEKEND20" />
      </label>
      <label>Loại giảm
        <select id="promotionType">
          <option value="PERCENT">Phần trăm</option>
          <option value="FIXED">Số tiền cố định</option>
          <option value="FREE_WASH">Miễn phí lượt rửa</option>
        </select>
      </label>
      <label>Giá trị
        <input id="promotionValue" type="number" min="0" step="1000" value="10" />
      </label>
      <label>Ngày bắt đầu
        <input id="promotionStart" type="date" required />
      </label>
      <label>Ngày kết thúc
        <input id="promotionEnd" type="date" required />
      </label>
      <label>Giới hạn lượt dùng
        <input id="promotionLimit" type="number" min="0" value="100" />
      </label>
      <label>Trạng thái
        <select id="promotionStatus">
          <option value="ACTIVE">ACTIVE</option>
          <option value="INACTIVE">INACTIVE</option>
          <option value="EXPIRED">EXPIRED</option>
        </select>
      </label>
      <label class="full">Mô tả
        <textarea id="promotionDescription" placeholder="Mô tả ngắn về chương trình"></textarea>
      </label>
      <div class="full row-actions">
        <button class="btn primary" type="submit">Lưu khuyến mãi</button>
        <button class="btn" type="button" id="cancelPromotionEditBtn">Làm mới form</button>
      </div>
    </form>
  </article>

  <article class="card">
    <p class="eyebrow">Danh sách</p>
    <h3>Promotions</h3>
    <div class="toolbar">
      <label>Tìm kiếm
        <input id="promotionSearch" type="search" placeholder="Tên hoặc mã code..." />
      </label>
      <label>Trạng thái
        <select id="promotionStatusFilter">
          <option value="ALL">Tất cả</option>
          <option value="ACTIVE">ACTIVE</option>
          <option value="INACTIVE">INACTIVE</option>
          <option value="EXPIRED">EXPIRED</option>
        </select>
      </label>
    </div>
    <div id="promotionList" class="config-list"></div>
  </article>
</section>

    </main>
  </div>
  <div id="toast" class="toast"></div>
  <script src="../assets/js/admin.js"></script>
  
</body>
</html>
