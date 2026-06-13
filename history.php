<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đặt lịch - AutoWash Pro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">AutoWash<span>Pro</span></a>
    <ul class="nav-links">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="booking_form.php">Đặt lịch</a></li>
        <li><a href="history.php">Lịch sử</a></li>
        <li><a href="#" onclick="logout();return false;" class="btn-nav" style="background:#dc2626;">Đăng xuất</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card-header" style="margin-bottom:8px;">
        <div>
            <h2 style="margin:0;">Lịch sử đặt lịch</h2>
            <p style="color:#64748b;margin-top:4px;">
                Tổng số lần đặt lịch: <strong><span id="totalBookings">0</span></strong>
            </p>
        </div>
        <a href="booking_form.php" class="btn btn-primary btn-sm">+ Đặt lịch mới</a>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Ngày</th>
                        <th>Giờ</th>
                        <th>Gói rửa</th>
                        <th>Biển số</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody id="historyTable">
                    <tr><td colspan="7" style="text-align:center;color:#64748b;">Đang tải...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<footer class="footer">
    <p>&copy; 2026 AutoWash Pro. All rights reserved.</p>
</footer>

<script>
var API_BASE = 'api/';
function logout() {
    fetch(API_BASE + 'login.php?action=logout', { credentials: 'same-origin' })
        .finally(function() { window.location.href = 'index.php'; });
}
</script>
<script src="assets/js/customer.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadHistory();
});
</script>

</body>
</html>
