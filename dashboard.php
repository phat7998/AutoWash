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
    <title>Dashboard - AutoWash Pro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">AutoWash<span>Pro</span></a>
    <ul class="nav-links">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="booking_form.php">Dat lich</a></li>
        <li><a href="history.php">Lich su</a></li>
        <li><a href="#" onclick="logout();return false;" class="btn-nav" style="background:#dc2626;">Dang xuat</a></li>
    </ul>
</nav>

<div class="container">
    <div class="tier-banner">
        <div class="tier-info">
            <h3>Xin chao, <span id="userName">...</span>!</h3>
            <p>
                <span id="userPhone"></span> &bull;
                Cap do: <strong><span id="userTier">Member</span></strong>
                <span id="tierBadge" class="tier-badge tier-member" style="margin-left:8px;">Member</span>
            </p>
        </div>
        <div class="next-tier">
            <div class="progress-text">Diem hien tai</div>
            <div class="progress-points"><span id="userPoints">0</span> diem</div>
            <div>Can them <strong><span id="pointsToNext">500</span></strong> diem de len hang</div>
            <div class="progress-bar">
                <div class="progress-fill" id="tierProgress" style="width:0%"></div>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="stat-card points">
            <div class="stat-icon">&#11088;</div>
            <div class="stat-value" id="userPoints2">0</div>
            <div class="stat-label">Tong diem tich luy</div>
        </div>
        <div class="stat-card tier">
            <div class="stat-icon">&#127942;</div>
            <div class="stat-value" id="userTier2">Member</div>
            <div class="stat-label">Cap do thanh vien</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">&#128663;</div>
            <div class="stat-value" id="totalBookingsCount">0</div>
            <div class="stat-label">Tong lan dat lich</div>
        </div>
    </div>

    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
        <a href="booking_form.php" class="btn btn-primary">+ Dat lich ngay</a>
        <a href="history.php" class="btn btn-outline">Xem lich su</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Lich su dat lich gan day</h3>
            <a href="history.php">Xem tat ca &rarr;</a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Ngay</th>
                        <th>Gio</th>
                        <th>Goi rua</th>
                        <th>Trang thai</th>
                        <th>Bien so</th>
                    </tr>
                </thead>
                <tbody id="recentBookings">
                    <tr><td colspan="5" style="text-align:center;color:#64748b;">Dang tai...</td></tr>
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
    loadDashboard();
    fetch('api/get_bookings.php', { credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var count = data.bookings ? data.bookings.length : 0;
            document.getElementById('totalBookingsCount').textContent = count;
        })
        .catch(function() {});

    fetch('api/get_tier.php', { credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var user = data.data || data;
                document.getElementById('userPoints2').textContent = user.points || 0;
                document.getElementById('userTier2').textContent = user.tier || 'Member';
            }
        })
        .catch(function() {});
});
</script>

</body>
</html>
