const ADMIN_API = 'http://localhost:8082';
const DEMO_TOKEN = 'demo-admin-token';

const FALLBACK_BOOKINGS = [
  { id: 'BK-DEMO-01', customer: { full_name: 'Nguyễn An' }, vehicle: { license_plate: '29A-12345' }, service_amount: 120000, status: 'PENDING' },
  { id: 'BK-DEMO-02', customer: { full_name: 'Trần Minh' }, vehicle: { license_plate: '51B-67890' }, service_amount: 240000, status: 'IN_PROGRESS' },
  { id: 'BK-DEMO-03', customer: { full_name: 'Lê Hòa' }, vehicle: { license_plate: '98C-11122' }, service_amount: 300000, status: 'COMPLETED' }
];

const FALLBACK_TIER_RULES = [
  { name: 'Member', booking_window_days: 7 },
  { name: 'Silver', booking_window_days: 10 },
  { name: 'Gold', booking_window_days: 12 }
];

const FALLBACK_PROMOTIONS = [
  { name: 'Khuyến mãi cuối tuần', status: 'ACTIVE' },
  { name: 'Ưu tiên member', status: 'ACTIVE' }
];

function getAdminToken() {
  return localStorage.getItem('adminToken');
}

function isDemoMode() {
  return getAdminToken() === DEMO_TOKEN;
}

function normalizeApiResponse(payload) {
  if (!payload) return null;
  if (Array.isArray(payload)) return payload;
  if (Array.isArray(payload.data)) return payload.data;
  if (payload.data && Array.isArray(payload.data.data)) return payload.data.data;
  return payload.data || payload;
}

function formatStatus(status) {
  const map = {
    PENDING: 'Đang chờ',
    COMPLETED: 'Hoàn tất',
    CANCELLED: 'Đã hủy',
    CONFIRMED: 'Đã xác nhận',
    IN_PROGRESS: 'Đang rửa'
  };
  return map[String(status).toUpperCase()] || status || 'Không xác định';
}

function statusClass(status) {
  const value = String(status || '').toUpperCase();
  if (value.includes('COMP')) return 'done';
  if (value.includes('PEND')) return 'pending';
  if (value.includes('CANC')) return 'cancelled';
  return 'washing';
}

function humanizeTier(value) {
  return String(value || '').toLowerCase().replace(/^./, (c) => c.toUpperCase());
}

async function requestAdmin(url, options = {}) {
  const token = getAdminToken();

  const response = await fetch(`${ADMIN_API}${url}`, {
    headers: {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(options.headers || {})
    },
    ...options
  });

  const result = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(result.message || 'API admin trả về lỗi');
  }
  return result;
}

function renderStats(bookings, tierRules) {
  const total = bookings.length;
  const pending = bookings.filter((item) => String(item.status || '').toUpperCase() === 'PENDING').length;
  const done = bookings.filter((item) => String(item.status || '').toUpperCase() === 'COMPLETED').length;

  document.getElementById('totalBookings').textContent = total;
  document.getElementById('pendingCount').textContent = pending;
  document.getElementById('doneCount').textContent = done;
  document.getElementById('tierCount').textContent = Array.isArray(tierRules) ? tierRules.length : 0;
}

function renderBookings(bookings) {
  const tbody = document.getElementById('bookingTableBody');
  if (!tbody) return;

  tbody.innerHTML = bookings.map((item) => {
    const bookingId = item.booking_code || item.id;
    const customerName = item.customer?.full_name || item.customer_name || 'Khách hàng';
    const vehicleName = item.vehicle?.license_plate || item.license_plate || 'Xe';
    const packageName = item.service_amount ? `${Number(item.service_amount).toLocaleString('vi-VN')}đ` : 'Dịch vụ';

    return `
      <tr>
        <td>${bookingId}</td>
        <td>${customerName}</td>
        <td>${vehicleName}</td>
        <td>${packageName}</td>
        <td><span class="badge ${statusClass(item.status)}">${formatStatus(item.status)}</span></td>
        <td class="actions">
          <button class="complete" data-action="complete" data-id="${item.id}">Hoàn tất</button>
          <button class="delete" data-action="delete" data-id="${item.id}">Xóa</button>
        </td>
      </tr>`;
  }).join('');
}

function renderSummary(tierRules, promotions) {
  const tierBox = document.getElementById('tierRulesBox');
  const promoBox = document.getElementById('promoBox');

  if (tierBox) {
    const list = Array.isArray(tierRules) ? tierRules.slice(0, 3).map((rule) => `${humanizeTier(rule.name || rule.code)} (${rule.booking_window_days || 0} ngày)`).join(' • ') : 'Không có hạng nào.';
    tierBox.innerHTML = `<strong>Tier rules:</strong> ${list}`;
  }

  if (promoBox) {
    const promo = Array.isArray(promotions) ? promotions.slice(0, 3).map((item) => `${item.name || 'Khuyến mãi'} (${item.status || 'ACTIVE'})`).join(' • ') : 'Không có khuyến mãi.';
    promoBox.innerHTML = `<strong>Promotions:</strong> ${promo}`;
  }
}

async function loadDashboard() {
  const apiStatus = document.getElementById('apiStatus');
  const statusNote = document.getElementById('statusNote');

  if (!getAdminToken()) {
    window.location.href = 'login.php';
    return;
  }

  apiStatus.textContent = 'Đang gọi API admin...';
  statusNote.textContent = 'Đang tải booking, tier rules và promotion từ backend production.';

  try {
    const [bookingsResponse, tierRulesResponse, promotionsResponse] = await Promise.all([
      requestAdmin('/bookings'),
      requestAdmin('/tier-rules'),
      requestAdmin('/promotions')
    ]);

    const bookings = normalizeApiResponse(bookingsResponse) || [];
    const tierRules = normalizeApiResponse(tierRulesResponse) || [];
    const promotions = normalizeApiResponse(promotionsResponse) || [];

    if (!Array.isArray(bookings) || !Array.isArray(tierRules) || !Array.isArray(promotions)) {
      throw new Error('Dữ liệu trả về không hợp lệ');
    }

    renderStats(bookings, tierRules);
    renderBookings(bookings);
    renderSummary(tierRules, promotions);

    apiStatus.textContent = 'API admin kết nối thành công';
    statusNote.textContent = 'Dashboard đang dùng dữ liệu real-time từ backend. Bạn có thể hoàn thành booking để tích điểm loyalty.';
  } catch (error) {
    const bookings = FALLBACK_BOOKINGS;
    const tierRules = FALLBACK_TIER_RULES;
    const promotions = FALLBACK_PROMOTIONS;

    renderStats(bookings, tierRules);
    renderBookings(bookings);
    renderSummary(tierRules, promotions);

    apiStatus.textContent = 'Backend chưa sẵn — đang dùng dữ liệu demo';
    statusNote.textContent = 'Bạn vẫn có thể xem dashboard. Nếu backend được bật, hãy refresh lại để dùng dữ liệu thật.';
    console.error(error);
  }
}

async function completeBooking(id) {
  if (isDemoMode()) {
    const bookings = FALLBACK_BOOKINGS.map((item) =>
      item.id === id ? { ...item, status: 'COMPLETED' } : item
    );
    localStorage.setItem('demoAdminBookings', JSON.stringify(bookings));
    renderStats(bookings, FALLBACK_TIER_RULES);
    renderBookings(bookings);
    alert('Đã hoàn thành booking demo.');
    return;
  }

  try {
    const result = await requestAdmin(`/bookings/complete?id=${id}`, { method: 'POST', body: '{}' });
    const message = result.message || 'Đã hoàn thành booking';
    alert(message);
    await loadDashboard();
  } catch (error) {
    alert(error.message || 'Không thể hoàn thành booking');
  }
}

window.addEventListener('DOMContentLoaded', () => {
  loadDashboard();

  document.getElementById('refreshBtn')?.addEventListener('click', () => loadDashboard());

  document.getElementById('bookingTableBody')
?.addEventListener('click', (event) => {

  const button = event.target.closest('button[data-action]');
  if (!button) return;

  const action = button.dataset.action;
  const id = button.dataset.id;

  if (!id) return;

  if (action === 'complete') {
    completeBooking(id);
  }

  if (action === 'delete') {
    deleteBooking(id);
  }
});
});

async function deleteBooking(id) {
  if (!confirm('Bạn có chắc muốn xóa booking này?')) {
    return;
  }

  try {
    await requestAdmin(`/bookings/${id}`, {
      method: 'DELETE'
    });

    alert('Xóa booking thành công');
    loadDashboard();
  } catch (error) {
    alert(error.message || 'Không thể xóa booking');
  }
}