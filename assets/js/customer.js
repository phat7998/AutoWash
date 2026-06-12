/**
 * AutoWash Pro - Customer Frontend JS
 */

const API_BASE = 'api/';

/* ===== SESSION CHECK ===== */
function checkAuth() {
    return new Promise((resolve) => {
        fetch(API_BASE + 'get_tier.php', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => resolve(data.success ? data : null))
            .catch(() => resolve(null));
    });
}

function requireAuth() {
    checkAuth().then(user => {
        if (!user) {
            window.location.href = 'login.php';
        }
    });
}

function redirectIfAuth() {
    checkAuth().then(user => {
        if (user) {
            window.location.href = 'dashboard.php';
        }
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

/* ===== REGISTER ===== */
function initRegister() {
    const form = document.getElementById('registerForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        hideAlert('registerError');

        const fullname = document.getElementById('fullname').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const licensePlate = document.getElementById('licensePlate').value.trim();
        const vehicleBrand = document.getElementById('vehicleBrand').value.trim();
        const vehicleModel = document.getElementById('vehicleModel').value.trim();

        if (!fullname || !phone || !password) {
            showAlert('registerError', 'Vui long dien day du ho ten, so dien thoai va mat khau.', 'error');
            return;
        }

        if (password.length < 6) {
            showAlert('registerError', 'Mat khau phai co it nhat 6 ky tu.', 'error');
            return;
        }

        if (password !== confirmPassword) {
            showAlert('registerError', 'Mat khau xac nhan khong khop.', 'error');
            return;
        }

        const payload = {
            fullname: fullname,
            phone: phone,
            email: email,
            password: password,
            license_plate: licensePlate,
            vehicle_brand: vehicleBrand,
            vehicle_model: vehicleModel
        };

        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="loader"></span> Dang xu ly...';

        fetch(API_BASE + 'register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Dang ky';

            if (data.success) {
                showAlert('registerError', data.message || 'Dang ky thanh cong! Dang chuyen huong...', 'success');
                setTimeout(() => { window.location.href = 'login.php'; }, 1500);
            } else {
                showAlert('registerError', data.message || 'Dang ky that bai.', 'error');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.textContent = 'Dang ky';
            showAlert('registerError', 'Loi ket noi den may chu.', 'error');
        });
    });
}

/* ===== LOGIN ===== */
function initLogin() {
    const form = document.getElementById('loginForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        hideAlert('loginError');

        const phone = document.getElementById('phone').value.trim();
        const password = document.getElementById('password').value;

        if (!phone || !password) {
            showAlert('loginError', 'Vui long nhap so dien thoai va mat khau.', 'error');
            return;
        }

        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="loader"></span> Dang dang nhap...';

        fetch(API_BASE + 'login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone: phone, password: password })
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Dang nhap';

            if (data.success) {
                window.location.href = 'dashboard.php';
            } else {
                showAlert('loginError', data.message || 'Sai so dien thoai hoac mat khau.', 'error');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.textContent = 'Dang nhap';
            showAlert('loginError', 'Loi ket noi den may chu.', 'error');
        });
    });
}

/* ===== LOGOUT ===== */
function logout() {
    fetch(API_BASE + 'login.php?action=logout', { credentials: 'same-origin' })
        .finally(() => { window.location.href = 'index.php'; });
}

/* ===== DASHBOARD ===== */
function loadDashboard() {
    fetch(API_BASE + 'get_tier.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error('Not authenticated');
            const user = data.data || data;

            document.getElementById('userName').textContent = user.fullname || user.phone;
            document.getElementById('userPhone').textContent = user.phone;
            document.getElementById('userTier').textContent = user.tier || 'Member';
            document.getElementById('userPoints').textContent = user.points || 0;

            const tierBadge = document.getElementById('tierBadge');
            tierBadge.textContent = user.tier || 'Member';
            tierBadge.className = 'tier-badge tier-' + (user.tier || 'member').toLowerCase();

            const avatar = document.getElementById('avatarLetter');
            if (avatar) avatar.textContent = (user.fullname || user.phone || 'U')[0].toUpperCase();

            if (user.next_tier_points) {
                document.getElementById('pointsToNext').textContent = user.next_tier_points;
                const pct = Math.min(100, ((user.points || 0) / user.next_tier_points) * 100);
                document.getElementById('tierProgress').style.width = pct + '%';
            }
        })
        .catch(() => { window.location.href = 'login.php'; });

    loadRecentBookings();
}

function loadRecentBookings() {
    fetch(API_BASE + 'get_bookings.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('recentBookings');
            if (!tbody) return;

            if (!data.success || !data.bookings || data.bookings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#64748b;padding:24px;">Chua co lich su dat lich.</td></tr>';
                return;
            }

            tbody.innerHTML = data.bookings.slice(0, 5).map(b => `
                <tr>
                    <td>${b.booking_date || ''}</td>
                    <td>${b.booking_time || ''}</td>
                    <td>${b.wash_type || ''}</td>
                    <td><span class="status-badge status-${(b.status || 'pending').toLowerCase()}">${b.status || 'Pending'}</span></td>
                    <td>${b.license_plate || ''}</td>
                </tr>
            `).join('');
        })
        .catch(() => {});
}

/* ===== BOOKING FORM ===== */
function initBookingForm() {
    const form = document.getElementById('bookingForm');
    if (!form) return;

    loadUserVehicles();
    loadAdvanceDays();
    setupWashTypeSelection();

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        hideAlert('bookingError');
        hideAlert('bookingSuccess');

        const vehicleId = document.getElementById('vehicleId').value;
        const washType = document.querySelector('input[name="washType"]:checked');
        const bookingDate = document.getElementById('bookingDate').value;
        const bookingTime = document.getElementById('bookingTime').value;
        const notes = document.getElementById('notes').value.trim();

        if (!vehicleId) {
            showAlert('bookingError', 'Vui long chon xe.', 'error');
            return;
        }
        if (!washType) {
            showAlert('bookingError', 'Vui long chon goi rua xe.', 'error');
            return;
        }
        if (!bookingDate || !bookingTime) {
            showAlert('bookingError', 'Vui long chon ngay va gio dat lich.', 'error');
            return;
        }

        const payload = {
            vehicle_id: vehicleId,
            wash_type: washType.value,
            booking_date: bookingDate,
            booking_time: bookingTime,
            notes: notes
        };

        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="loader"></span> Dang dat lich...';

        fetch(API_BASE + 'booking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = 'Xac nhan dat lich';

            if (data.success) {
                showAlert('bookingSuccess', data.message || 'Dat lich thanh cong!', 'success');
                document.getElementById('bookingModal').classList.add('active');
            } else {
                showAlert('bookingError', data.message || 'Dat lich that bai.', 'error');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.textContent = 'Xac nhan dat lich';
            showAlert('bookingError', 'Loi ket noi den may chu.', 'error');
        });
    });
}

function loadUserVehicles() {
    fetch(API_BASE + 'get_bookings.php?action=vehicles', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            const select = document.getElementById('vehicleId');
            if (!select) return;

            select.innerHTML = '<option value="">-- Chon xe --</option>';

            if (data.success && data.vehicles && data.vehicles.length > 0) {
                data.vehicles.forEach(v => {
                    select.innerHTML += `<option value="${v.id}">${v.license_plate} - ${v.brand || ''} ${v.model || ''}</option>`;
                });
            }
        })
        .catch(() => {});
}

function loadAdvanceDays() {
    fetch(API_BASE + 'get_advance_days.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            const dateInput = document.getElementById('bookingDate');
            if (!dateInput) return;

            const days = data.days || 7;
            const today = new Date();
            const maxDate = new Date();
            maxDate.setDate(today.getDate() + days);

            dateInput.min = today.toISOString().split('T')[0];
            dateInput.max = maxDate.toISOString().split('T')[0];

            const info = document.getElementById('advanceDaysInfo');
            if (info) {
                info.textContent = `Ban co the dat lich truoc toi da ${days} ngay (cap do: ${data.tier || 'Member'}).`;
            }
        })
        .catch(() => {});
}

function setupWashTypeSelection() {
    const cards = document.querySelectorAll('.wash-type-card');
    cards.forEach(card => {
        card.addEventListener('click', function() {
            cards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });
}

function closeModal() {
    const modal = document.getElementById('bookingModal');
    if (modal) modal.classList.remove('active');
}

/* ===== HISTORY ===== */
function loadHistory() {
    fetch(API_BASE + 'get_bookings.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('historyTable');
            if (!tbody) return;

            const countEl = document.getElementById('totalBookings');
            if (countEl) countEl.textContent = data.bookings ? data.bookings.length : 0;

            if (!data.success || !data.bookings || data.bookings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#64748b;padding:32px;">Chua co lich su dat lich nao.</td></tr>';
                return;
            }

            tbody.innerHTML = data.bookings.map((b, i) => `
                <tr>
                    <td>${i + 1}</td>
                    <td>${b.booking_date || ''}</td>
                    <td>${b.booking_time || ''}</td>
                    <td>${b.wash_type || ''}</td>
                    <td>${b.license_plate || ''}</td>
                    <td><span class="status-badge status-${(b.status || 'pending').toLowerCase()}">${b.status || 'Pending'}</span></td>
                    <td>${b.notes || '-'}</td>
                </tr>
            `).join('');
        })
        .catch(() => {});
}

/* ===== INIT ===== */
document.addEventListener('DOMContentLoaded', function() {
    initRegister();
    initLogin();
    initBookingForm();
});
