/**
 * AutoWash Pro - Customer Frontend JS
 * Kết nối Yii2 Customer API (Bearer Token)
 */

const API_BASE = 'http://localhost:8081';

function getToken() {
    return localStorage.getItem('customerToken');
}

function apiHeaders() {
    const headers = { 'Content-Type': 'application/json' };
    const token = getToken();
    if (token) headers['Authorization'] = 'Bearer ' + token;
    return headers;
}

// Response wrapper: {isSuccessful, statusCode, message, data}
function unwrap(res) {
    if (res && res.data !== undefined) return res.data;
    return res;
}

function getMessage(res) {
    if (res && res.message) return res.message;
    return null;
}

/* ===== AUTH CHECK ===== */
function checkAuth() {
    const token = getToken();
    if (!token) return Promise.resolve(null);
    return fetch(API_BASE + '/auth/profile', { headers: apiHeaders() })
        .then(r => r.json())
        .then(data => data.isSuccessful ? data.data : null)
        .catch(() => null);
}

function requireAuth() {
    checkAuth().then(user => {
        if (!user) window.location.href = 'login.php';
    });
}

function redirectIfAuth() {
    checkAuth().then(user => {
        if (user) window.location.href = 'dashboard.php';
    });
}

/* ===== ALERTS ===== */
function showAlert(elId, message, type) {
    const el = document.getElementById(elId);
    if (!el) return;
    el.textContent = message;
    el.className = 'alert alert-' + type;
    el.style.display = 'block';
    setTimeout(() => { el.style.display = 'none'; }, 5000);
}

function hideAlert(elId) {
    const el = document.getElementById(elId);
    if (el) el.style.display = 'none';
}

/* ===== HELPER ===== */
function timestampToDate(ts) {
    if (!ts) return '';
    var d = new Date(ts * 1000);
    return d.getDate().toString().padStart(2,'0') + '/' + (d.getMonth()+1).toString().padStart(2,'0') + '/' + d.getFullYear();
}

function timestampToTime(ts) {
    if (!ts) return '';
    var d = new Date(ts * 1000);
    return d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
}

function amountToWashType(amount) {
    var a = parseFloat(amount) || 0;
    if (a <= 30000) return 'Rửa cơ bản';
    if (a <= 50000) return 'Rửa cao cấp';
    return 'Rửa toàn diện';
}

function washTypeToAmount(type) {
    switch(type) {
        case 'basic': return 30000;
        case 'premium': return 50000;
        case 'full': return 80000;
        default: return 50000;
    }
}

/* ===== REGISTER ===== */
function initRegister() {
    const form = document.getElementById('registerForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        hideAlert('registerError');

        const fullname = document.getElementById('fullname').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const licensePlate = document.getElementById('licensePlate').value.trim();

        if (!fullname || !phone || !password) {
            showAlert('registerError', 'Vui lòng điền đầy đủ họ tên, số điện thoại và mật khẩu.', 'error');
            return;
        }
        if (password.length < 6) {
            showAlert('registerError', 'Mật khẩu phải có ít nhất 6 ký tự.', 'error');
            return;
        }
        if (password !== confirmPassword) {
            showAlert('registerError', 'Mật khẩu xác nhận không khớp.', 'error');
            return;
        }

        var payload = {
            username: phone,
            password: password,
            full_name: fullname,
            phone: phone,
            license_plate: licensePlate
        };

        var btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="loader"></span> Đang xử lý...';

        fetch(API_BASE + '/auth/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Đăng ký';
            if (data.isSuccessful) {
                showAlert('registerError', data.message || 'Đăng ký thành công!', 'success');
                setTimeout(function() { window.location.href = 'login.php'; }, 1500);
            } else {
                showAlert('registerError', data.message || 'Đăng ký thất bại.', 'error');
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Đăng ký';
            showAlert('registerError', 'Lỗi kết nối đến máy chủ.', 'error');
        });
    });
}

/* ===== LOGIN ===== */
function initLogin() {
    var form = document.getElementById('loginForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        hideAlert('loginError');

        var phone = document.getElementById('phone').value.trim();
        var password = document.getElementById('password').value;

        if (!phone || !password) {
            showAlert('loginError', 'Vui lòng nhập số điện thoại và mật khẩu.', 'error');
            return;
        }

        var btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="loader"></span> Đang đăng nhập...';

        fetch(API_BASE + '/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: phone, password: password })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Đăng nhập';
            if (data.isSuccessful && data.data && data.data.access_token) {
                localStorage.setItem('customerToken', data.data.access_token);
                localStorage.setItem('customerUser', JSON.stringify(data.data.user || {}));
                window.location.href = 'dashboard.php';
            } else {
                showAlert('loginError', getMessage(data) || 'Sai số điện thoại hoặc mật khẩu.', 'error');
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Đăng nhập';
            showAlert('loginError', 'Lỗi kết nối đến máy chủ.', 'error');
        });
    });
}

/* ===== LOGOUT ===== */
function logout() {
    localStorage.removeItem('customerToken');
    localStorage.removeItem('customerUser');
    window.location.href = 'index.php';
}

/* ===== DASHBOARD ===== */
function loadDashboard() {
    fetch(API_BASE + '/auth/profile', { headers: apiHeaders() })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.isSuccessful) throw new Error('Not authenticated');
            var profile = data.data;
            var user = JSON.parse(localStorage.getItem('customerUser') || '{}');

            document.getElementById('userName').textContent = profile.full_name || user.full_name || user.username || '';
            document.getElementById('userPhone').textContent = profile.phone || '';
            document.getElementById('userTier').textContent = (profile.loyalty && profile.loyalty.tier) || 'Member';
            document.getElementById('userPoints').textContent = (profile.loyalty && profile.loyalty.point_balance) || 0;

            var tierBadge = document.getElementById('tierBadge');
            var tier = (profile.loyalty && profile.loyalty.tier) || 'Member';
            tierBadge.textContent = tier;
            tierBadge.className = 'tier-badge tier-' + tier.toLowerCase();
        })
        .catch(function() { window.location.href = 'login.php'; });

    // Load tier progress info (next tier, points needed, progress bar)
    fetch(API_BASE + '/loyalty/tier', { headers: apiHeaders() })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.isSuccessful && data.data) {
                var tierInfo = data.data;
                var pointsToNext = document.getElementById('pointsToNext');
                var progressFill = document.getElementById('tierProgress');
                if (tierInfo.points_to_next !== null && pointsToNext) {
                    pointsToNext.textContent = tierInfo.points_to_next;
                }
                if (tierInfo.progress_percent !== null && progressFill) {
                    progressFill.style.width = tierInfo.progress_percent + '%';
                }
            }
        })
        .catch(function() {});

    loadRecentBookings();
    loadBalanceStats();
}

function loadBalanceStats() {
    fetch(API_BASE + '/loyalty/balance', { headers: apiHeaders() })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.isSuccessful && data.data) {
                document.getElementById('userPoints2').textContent = data.data.point_balance || 0;
                document.getElementById('userTier2').textContent = data.data.tier || 'Member';
            }
        })
        .catch(function() {});
}

function loadRecentBookings() {
    var tbody = document.getElementById('recentBookings');
    if (!tbody) return;
    
    fetch(API_BASE + '/bookings', { headers: apiHeaders() })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var bookings = data.isSuccessful ? (data.data || []) : [];
            var countEl = document.getElementById('totalBookingsCount');
            if (countEl) countEl.textContent = bookings.length;

            if (bookings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#64748b;padding:24px;">Chưa có lịch sử đặt lịch.</td></tr>';
                return;
            }

            tbody.innerHTML = bookings.slice(0, 5).map(function(b) {
                var status = b.status || 'PENDING';
                return '<tr>' +
                    '<td>' + timestampToDate(b.scheduled_at) + '</td>' +
                    '<td>' + timestampToTime(b.scheduled_at) + '</td>' +
                    '<td>' + amountToWashType(b.service_amount) + '</td>' +
                    '<td><span class="status-badge status-' + status.toLowerCase() + '">' + status + '</span></td>' +
                    '<td>' + (b.booking_code || '') + '</td>' +
                    '</tr>';
            }).join('');
        })
        .catch(function() {});
}

/* ===== BOOKING FORM ===== */
function initBookingForm() {
    var form = document.getElementById('bookingForm');
    if (!form) return;

    loadUserVehicles();
    setupWashTypeSelection();
    loadAdvanceDays();

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        hideAlert('bookingError');
        hideAlert('bookingSuccess');

        var vehicleId = document.getElementById('vehicleId').value;
        var washTypeEl = document.querySelector('input[name="washType"]:checked');
        var bookingDate = document.getElementById('bookingDate').value;
        var bookingTime = document.getElementById('bookingTime').value;

        if (!vehicleId) {
            showAlert('bookingError', 'Vui lòng chọn xe.', 'error');
            return;
        }
        if (!washTypeEl) {
            showAlert('bookingError', 'Vui lòng chọn gói rửa xe.', 'error');
            return;
        }
        if (!bookingDate || !bookingTime) {
            showAlert('bookingError', 'Vui lòng chọn ngày và giờ đặt lịch.', 'error');
            return;
        }

        // Convert date+time to Unix timestamp
        var scheduledAt = Math.floor(new Date(bookingDate + 'T' + bookingTime + ':00').getTime() / 1000);
        var serviceAmount = washTypeToAmount(washTypeEl.value);

        var payload = {
            vehicle_id: parseInt(vehicleId),
            scheduled_at: scheduledAt,
            service_amount: serviceAmount
        };

        var btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="loader"></span> Đang đặt lịch...';

        fetch(API_BASE + '/bookings', {
            method: 'POST',
            headers: apiHeaders(),
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Xác nhận đặt lịch';
            if (data.isSuccessful) {
                showAlert('bookingSuccess', data.message || 'Đặt lịch thành công!', 'success');
                document.getElementById('bookingModal').classList.add('active');
            } else {
                showAlert('bookingError', data.message || 'Đặt lịch thất bại.', 'error');
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Xác nhận đặt lịch';
            showAlert('bookingError', 'Lỗi kết nối đến máy chủ.', 'error');
        });
    });
}

function loadUserVehicles() {
    fetch(API_BASE + '/vehicles', { headers: apiHeaders() })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var select = document.getElementById('vehicleId');
            if (!select) return;
            select.innerHTML = '<option value="">-- Chọn xe --</option>';
            var vehicles = data.isSuccessful ? (data.data || []) : [];
            vehicles.forEach(function(v) {
                select.innerHTML += '<option value="' + v.id + '">' + v.license_plate + ' - ' + (v.brand_name || '') + '</option>';
            });
        })
        .catch(function() {});
}

function loadAdvanceDays() {
    var infoEl = document.getElementById('advanceDaysInfo');
    if (!infoEl) return;

    fetch(API_BASE + '/loyalty/advance-days', { headers: apiHeaders() })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.isSuccessful && data.data) {
                infoEl.textContent = 'Bạn có thể đặt trước tối đa ' + data.data.advance_days + ' ngày';
            }
        })
        .catch(function() {});
}

function setupWashTypeSelection() {
    var cards = document.querySelectorAll('.wash-type-card');
    cards.forEach(function(card) {
        card.addEventListener('click', function() {
            cards.forEach(function(c) { c.classList.remove('selected'); });
            this.classList.add('selected');
            var radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });
}

function closeModal() {
    var modal = document.getElementById('bookingModal');
    if (modal) modal.classList.remove('active');
}

/* ===== HISTORY ===== */
function loadHistory() {
    fetch(API_BASE + '/bookings', { headers: apiHeaders() })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var tbody = document.getElementById('historyTable');
            if (!tbody) return;
            var bookings = data.isSuccessful ? (data.data || []) : [];
            var countEl = document.getElementById('totalBookings');
            if (countEl) countEl.textContent = bookings.length;

            if (bookings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#64748b;padding:32px;">Chưa có lịch sử đặt lịch nào.</td></tr>';
                return;
            }

            tbody.innerHTML = bookings.map(function(b, i) {
                var status = b.status || 'PENDING';
                return '<tr>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td>' + timestampToDate(b.scheduled_at) + '</td>' +
                    '<td>' + timestampToTime(b.scheduled_at) + '</td>' +
                    '<td>' + amountToWashType(b.service_amount) + '</td>' +
                    '<td>' + (b.booking_code || '') + '</td>' +
                    '<td><span class="status-badge status-' + status.toLowerCase() + '">' + status + '</span></td>' +
                    '<td>-</td>' +
                    '</tr>';
            }).join('');
        })
        .catch(function() {});
}

/* ===== INIT ===== */
document.addEventListener('DOMContentLoaded', function() {
    initRegister();
    initLogin();
    initBookingForm();
});
