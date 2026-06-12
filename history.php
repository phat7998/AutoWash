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
    <title>Lich su dat lich - AutoWash Pro</title>
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
    <div class="card-header" style="margin-bottom:8px;">
        <div>
            <h2 style="margin:0;">Lich su dat lich</h2>
            <p style="color:#64748b;margin-top:4px;">
                Tong so lan dat lich: <strong><span id="totalBookings">0</span></strong>
            </p>
        </div>
        <a href="booking_form.php" class="btn btn-primary btn-sm">+ Dat lich moi</a>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Ngay</th>
                        <th>Gio</th>
                        <th>Goi rua</th>
                        <th>Bien so</th>
                        <th>Trang thai</th>
                        <th>Ghi chu</th>
                    </tr>
                </thead>
                <tbody id="historyTable">
                    <tr><td colspan="7" style="text-align:center;color:#64748b;">Dang tai...</td></tr>
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
