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
    <title>Dat lich rua xe - AutoWash Pro</title>
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
    <div class="booking-steps">
        <div class="step active">
            <span class="step-num">1</span> Chon goi rua
        </div>
        <div class="step-divider"></div>
        <div class="step active">
            <span class="step-num">2</span> Chon thoi gian
        </div>
        <div class="step-divider"></div>
        <div class="step active">
            <span class="step-num">3</span> Xac nhan
        </div>
    </div>

    <div style="max-width:700px;margin:0 auto;">
        <div class="card">
            <div class="card-header">
                <h2>Dat lich rua xe</h2>
                <span id="advanceDaysInfo" style="font-size:0.85rem;color:#64748b;"></span>
            </div>

            <div id="bookingError" class="alert alert-error"></div>
            <div id="bookingSuccess" class="alert alert-success"></div>

            <form id="bookingForm">
                <div class="form-group">
                    <label for="vehicleId">Chon xe *</label>
                    <select id="vehicleId" class="form-control" required>
                        <option value="">-- Chon xe --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Goi rua xe *</label>
                    <div class="wash-type-options">
                        <div class="wash-type-card">
                            <input type="radio" name="washType" value="basic" style="display:none;">
                            <div class="type-name">Rua co ban</div>
                            <div class="type-price">30.000d</div>
                            <div class="type-desc">Rua sach, xa phong, say kho</div>
                        </div>
                        <div class="wash-type-card">
                            <input type="radio" name="washType" value="premium" style="display:none;">
                            <div class="type-name">Rua cao cap</div>
                            <div class="type-price">50.000d</div>
                            <div class="type-desc">Rua sach + danh bong + ve sinh noi that</div>
                        </div>
                        <div class="wash-type-card">
                            <input type="radio" name="washType" value="full" style="display:none;">
                            <div class="type-name">Rua toan dien</div>
                            <div class="type-price">80.000d</div>
                            <div class="type-desc">Tron goi: rua + danh bong + ve sinh + bao duong lop</div>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="bookingDate">Ngay dat lich *</label>
                        <input type="date" id="bookingDate" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="bookingTime">Gio dat lich *</label>
                        <select id="bookingTime" class="form-control" required>
                            <option value="">-- Chon gio --</option>
                            <option value="07:00">07:00</option>
                            <option value="07:30">07:30</option>
                            <option value="08:00">08:00</option>
                            <option value="08:30">08:30</option>
                            <option value="09:00">09:00</option>
                            <option value="09:30">09:30</option>
                            <option value="10:00">10:00</option>
                            <option value="10:30">10:30</option>
                            <option value="11:00">11:00</option>
                            <option value="13:00">13:00</option>
                            <option value="13:30">13:30</option>
                            <option value="14:00">14:00</option>
                            <option value="14:30">14:30</option>
                            <option value="15:00">15:00</option>
                            <option value="15:30">15:30</option>
                            <option value="16:00">16:00</option>
                            <option value="16:30">16:30</option>
                            <option value="17:00">17:00</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Ghi chu</label>
                    <textarea id="notes" class="form-control" rows="3" placeholder="Yeu cau them (neu co)..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Xac nhan dat lich</button>
            </form>
        </div>
    </div>
</div>

<div class="modal-overlay" id="bookingModal">
    <div class="modal">
        <div class="modal-icon">&#9989;</div>
        <h3>Dat lich thanh cong!</h3>
        <p>Cam on ban da dat lich tai AutoWash Pro. Vui long den dung gio de duoc phuc vu tot nhat.</p>
        <a href="dashboard.php" class="btn btn-primary">Ve Dashboard</a>
        <button onclick="closeModal()" class="btn btn-outline" style="margin-left:8px;">Dat lich khac</button>
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

</body>
</html>
