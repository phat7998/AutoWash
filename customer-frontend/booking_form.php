<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lịch rửa xe - AutoWash Pro</title>
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
    <div class="booking-steps">
        <div class="step active">
            <span class="step-num">1</span> Chọn gói rửa
        </div>
        <div class="step-divider"></div>
        <div class="step active">
            <span class="step-num">2</span> Chọn thời gian
        </div>
        <div class="step-divider"></div>
        <div class="step active">
            <span class="step-num">3</span> Xác nhận
        </div>
    </div>

    <div style="max-width:700px;margin:0 auto;">
        <div class="card">
            <div class="card-header">
                <h2>Đặt lịch rửa xe</h2>
                <span id="advanceDaysInfo" style="font-size:0.85rem;color:#64748b;"></span>
            </div>

            <div id="bookingError" class="alert alert-error"></div>
            <div id="bookingSuccess" class="alert alert-success"></div>

            <form id="bookingForm">
                <div class="form-group">
                    <label for="vehicleId">Chọn xe *</label>
                    <select id="vehicleId" class="form-control" required>
                        <option value="">-- Chọn xe --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Gói rửa xe *</label>
                    <div class="wash-type-options">
                        <div class="wash-type-card">
                            <input type="radio" name="washType" value="basic" style="display:none;">
                            <div class="type-name">Rửa cơ bản</div>
                            <div class="type-price">30.000đ</div>
                            <div class="type-desc">Rửa sạch, xà phòng, sấy khô</div>
                        </div>
                        <div class="wash-type-card">
                            <input type="radio" name="washType" value="premium" style="display:none;">
                            <div class="type-name">Rửa cao cấp</div>
                            <div class="type-price">50.000đ</div>
                            <div class="type-desc">Rửa sạch + đánh bóng + vệ sinh nội thất</div>
                        </div>
                        <div class="wash-type-card">
                            <input type="radio" name="washType" value="full" style="display:none;">
                            <div class="type-name">Rửa toàn diện</div>
                            <div class="type-price">80.000đ</div>
                            <div class="type-desc">Trọn gói: rửa + đánh bóng + vệ sinh + bảo dưỡng lốp</div>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="bookingDate">Ngày đặt lịch *</label>
                        <input type="date" id="bookingDate" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="bookingTime">Giờ đặt lịch *</label>
                        <select id="bookingTime" class="form-control" required>
                            <option value="">-- Chọn giờ --</option>
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
                    <label for="notes">Ghi chú</label>
                    <textarea id="notes" class="form-control" rows="3" placeholder="Yêu cầu thêm (nếu có)..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Xác nhận đặt lịch</button>
            </form>
        </div>
    </div>
</div>

<div class="modal-overlay" id="bookingModal">
    <div class="modal">
        <div class="modal-icon">&#9989;</div>
        <h3>Đặt lịch thành công!</h3>
        <p>Cảm ơn bạn đã đặt lịch tại AutoWash Pro. Vui lòng đến đúng giờ để được phục vụ tốt nhất.</p>
        <a href="dashboard.php" class="btn btn-primary">Về Dashboard</a>
        <button onclick="closeModal()" class="btn btn-outline" style="margin-left:8px;">Đặt lịch khác</button>
    </div>
</div>

<footer class="footer">
    <p>&copy; 2026 AutoWash Pro. All rights reserved.</p>
</footer>

<script>
if (!localStorage.getItem('customerToken')) {
    window.location.href = 'login.php';
}
</script>
<script src="assets/js/customer.js"></script>

</body>
</html>
