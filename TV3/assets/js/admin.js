const ADMIN_API = window.ADMIN_API_BASE || localStorage.getItem('adminApiBase') || 'https://api-admin.wp-fl-demo.xyz';
const DEMO_TOKEN = 'demo-admin-token';
const PAGE = document.body.dataset.page || 'dashboard';

const DEFAULT_TIER_RULES = [
  { id: 1, code: 'MEMBER', name: 'Member', min_spend: 0, min_visits: 0, booking_window_days: 7, priority: 1, is_active: true },
  { id: 2, code: 'SILVER', name: 'Silver', min_spend: 500000, min_visits: 3, booking_window_days: 10, priority: 2, is_active: true },
  { id: 3, code: 'GOLD', name: 'Gold', min_spend: 1500000, min_visits: 8, booking_window_days: 14, priority: 3, is_active: true },
  { id: 4, code: 'PLATINUM', name: 'Platinum', min_spend: 3500000, min_visits: 15, booking_window_days: 21, priority: 4, is_active: true }
];

const DEFAULT_PROMOTIONS = [
  { id: 1, name: 'Khuyến mãi cuối tuần', code: 'WEEKEND20', discount_type: 'PERCENT', discount_value: 20, start_date: '2026-06-01', end_date: '2026-07-31', usage_limit: 300, used_count: 87, status: 'ACTIVE', description: 'Giảm 20% cho khách đặt lịch cuối tuần.' },
  { id: 2, name: 'Ưu đãi thành viên Gold', code: 'GOLD50K', discount_type: 'FIXED', discount_value: 50000, start_date: '2026-06-10', end_date: '2026-08-10', usage_limit: 120, used_count: 34, status: 'ACTIVE', description: 'Giảm 50.000đ cho khách Gold và Platinum.' },
  { id: 3, name: 'Rửa xe miễn phí lần thứ 5', code: 'FREEWASH5', discount_type: 'FREE_WASH', discount_value: 1, start_date: '2026-05-01', end_date: '2026-06-30', usage_limit: 50, used_count: 44, status: 'INACTIVE', description: 'Tặng 1 lượt rửa xe cho khách quay lại nhiều lần.' }
];

const DEFAULT_BOOKINGS = [
  { id: 'BK-DEMO-01', booking_code: 'BK-DEMO-01', customer: { full_name: 'Nguyễn An' }, customer_name: 'Nguyễn An', customer_tier: 'Gold', vehicle: { license_plate: '29A-12345' }, license_plate: '29A-12345', service_name: 'Rửa xe tiêu chuẩn', service_amount: 120000, scheduled_at: '2026-06-27T08:30:00', status: 'PENDING', priority_score: 305 },
  { id: 'BK-DEMO-02', booking_code: 'BK-DEMO-02', customer: { full_name: 'Trần Minh' }, customer_name: 'Trần Minh', customer_tier: 'Platinum', vehicle: { license_plate: '51B-67890' }, license_plate: '51B-67890', service_name: 'Rửa xe cao cấp', service_amount: 240000, scheduled_at: '2026-06-27T09:00:00', status: 'IN_PROGRESS', priority_score: 410 },
  { id: 'BK-DEMO-03', booking_code: 'BK-DEMO-03', customer: { full_name: 'Lê Hòa' }, customer_name: 'Lê Hòa', customer_tier: 'Silver', vehicle: { license_plate: '98C-11122' }, license_plate: '98C-11122', service_name: 'Rửa xe + hút bụi', service_amount: 300000, scheduled_at: '2026-06-27T10:30:00', status: 'COMPLETED', priority_score: 215 },
  { id: 'BK-DEMO-04', booking_code: 'BK-DEMO-04', customer: { full_name: 'Phạm Lan' }, customer_name: 'Phạm Lan', customer_tier: 'Member', vehicle: { license_plate: '60A-22233' }, license_plate: '60A-22233', service_name: 'Rửa xe nhanh', service_amount: 90000, scheduled_at: '2026-06-27T11:00:00', status: 'CONFIRMED', priority_score: 120 },
  { id: 'BK-DEMO-05', booking_code: 'BK-DEMO-05', customer: { full_name: 'Võ Khang' }, customer_name: 'Võ Khang', customer_tier: 'Gold', vehicle: { license_plate: '71B-33344' }, license_plate: '71B-33344', service_name: 'Rửa xe cao cấp', service_amount: 260000, scheduled_at: '2026-06-28T08:00:00', status: 'PENDING', priority_score: 320 },
  { id: 'BK-DEMO-06', booking_code: 'BK-DEMO-06', customer: { full_name: 'Hoàng Vy' }, customer_name: 'Hoàng Vy', customer_tier: 'Silver', vehicle: { license_plate: '50F-45678' }, license_plate: '50F-45678', service_name: 'Rửa xe tiêu chuẩn', service_amount: 130000, scheduled_at: '2026-06-28T13:30:00', status: 'CANCELLED', priority_score: 200 },
  { id: 'BK-DEMO-07', booking_code: 'BK-DEMO-07', customer: { full_name: 'Đặng Bảo' }, customer_name: 'Đặng Bảo', customer_tier: 'Platinum', vehicle: { license_plate: '59G-78910' }, license_plate: '59G-78910', service_name: 'Detailing nội thất', service_amount: 520000, scheduled_at: '2026-06-29T15:00:00', status: 'COMPLETED', priority_score: 430 },
  { id: 'BK-DEMO-08', booking_code: 'BK-DEMO-08', customer: { full_name: 'Mai Chi' }, customer_name: 'Mai Chi', customer_tier: 'Member', vehicle: { license_plate: '65A-65432' }, license_plate: '65A-65432', service_name: 'Rửa xe nhanh', service_amount: 95000, scheduled_at: '2026-06-29T16:00:00', status: 'PENDING', priority_score: 110 }
];

const state = {
  bookings: [],
  tierRules: [],
  promotions: [],
  bookingPage: 1,
  bookingPageSize: 6,
  charts: {}
};

function readDemo(key, fallback) {
  try {
    const value = localStorage.getItem(`tv3Admin.${key}`);
    return value ? JSON.parse(value) : JSON.parse(JSON.stringify(fallback));
  } catch (_) {
    return JSON.parse(JSON.stringify(fallback));
  }
}

function writeDemo(key, value) {
  localStorage.setItem(`tv3Admin.${key}`, JSON.stringify(value));
}

function getAdminToken() {
  return localStorage.getItem('adminToken');
}

function isDemoMode() {
  return getAdminToken() === DEMO_TOKEN;
}

function requireAuth() {
  if (PAGE !== 'login' && !getAdminToken()) {
    window.location.href = 'login.php';
  }
}

function logout() {
  localStorage.removeItem('adminToken');
  localStorage.removeItem('adminUser');
  window.location.href = 'login.php';
}

function normalizeApiResponse(payload) {
  if (!payload) return null;
  if (Array.isArray(payload)) return payload;
  if (Array.isArray(payload.data)) return payload.data;
  if (payload.data && Array.isArray(payload.data.data)) return payload.data.data;
  if (payload.data && Array.isArray(payload.data.items)) return payload.data.items;
  if (Array.isArray(payload.items)) return payload.items;
  return payload.data || payload;
}

async function requestAdmin(url, options = {}) {
  const token = getAdminToken();
  if (isDemoMode()) {
    throw new Error('Demo mode dùng dữ liệu localStorage');
  }

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

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
}

function formatMoney(value) {
  return `${Number(value || 0).toLocaleString('vi-VN')}đ`;
}

function formatDateTime(value) {
  if (!value) return 'Chưa có lịch';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return String(value);
  return date.toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' });
}

function toInputDate(value) {
  const date = value ? new Date(value) : new Date();
  if (Number.isNaN(date.getTime())) return new Date().toISOString().slice(0, 10);
  return date.toISOString().slice(0, 10);
}

function formatStatus(status) {
  const map = {
    PENDING: 'Đang chờ',
    COMPLETED: 'Hoàn tất',
    CANCELLED: 'Đã hủy',
    CONFIRMED: 'Đã xác nhận',
    IN_PROGRESS: 'Đang rửa'
  };
  return map[String(status || '').toUpperCase()] || status || 'Không xác định';
}

function statusClass(status) {
  const value = String(status || '').toUpperCase();
  if (value.includes('COMP')) return 'done';
  if (value.includes('PEND')) return 'pending';
  if (value.includes('CANC')) return 'cancelled';
  return 'washing';
}

function normalizeTier(value) {
  const raw = String(value || 'Member').toLowerCase();
  if (raw.includes('plat')) return 'Platinum';
  if (raw.includes('gold')) return 'Gold';
  if (raw.includes('silver')) return 'Silver';
  return 'Member';
}

function tierClass(value) {
  return normalizeTier(value).toLowerCase();
}

function tierPriority(value) {
  const map = { Member: 1, Silver: 2, Gold: 3, Platinum: 4 };
  return map[normalizeTier(value)] || 1;
}

function getBookingTier(item) {
  return normalizeTier(item.customer_tier || item.tier || item.customer?.tier || item.loyalty_account?.tier || item.loyaltyAccount?.tier);
}

function getCustomerName(item) {
  return item.customer?.full_name || item.customer?.name || item.customer_name || item.full_name || 'Khách hàng';
}

function getVehicleName(item) {
  return item.vehicle?.license_plate || item.license_plate || item.vehicle_plate || 'Xe';
}

function getServiceName(item) {
  return item.service_name || item.package_name || item.service?.name || 'Dịch vụ';
}

function showToast(message, type = 'success') {
  const toast = document.getElementById('toast');
  if (!toast) return alert(message);
  toast.textContent = message;
  toast.className = `toast show ${type}`;
  clearTimeout(showToast.timer);
  showToast.timer = setTimeout(() => toast.className = 'toast', 2800);
}

function setActiveNav() {
  document.querySelectorAll('[data-nav]').forEach((link) => {
    link.classList.toggle('active', link.dataset.nav === PAGE);
  });
}

function renderEmpty(tbody, colspan, message) {
  tbody.innerHTML = `<tr><td colspan="${colspan}"><div class="empty">${escapeHtml(message)}</div></td></tr>`;
}

async function getBookings() {
  try {
    const result = await requestAdmin('/bookings');
    const data = normalizeApiResponse(result);
    if (Array.isArray(data)) return data;
    throw new Error('Dữ liệu booking không hợp lệ');
  } catch (error) {
    return readDemo('bookings', DEFAULT_BOOKINGS);
  }
}

async function getTierRules() {
  try {
    const result = await requestAdmin('/tier-rules');
    const data = normalizeApiResponse(result);
    if (Array.isArray(data)) return data;
    throw new Error('Dữ liệu tier rules không hợp lệ');
  } catch (error) {
    return readDemo('tierRules', DEFAULT_TIER_RULES);
  }
}

async function getPromotions() {
  try {
    const result = await requestAdmin('/promotions');
    const data = normalizeApiResponse(result);
    if (Array.isArray(data)) return data;
    throw new Error('Dữ liệu promotions không hợp lệ');
  } catch (error) {
    return readDemo('promotions', DEFAULT_PROMOTIONS);
  }
}

function renderDashboardStats(bookings, tierRules) {
  const total = bookings.length;
  const pending = bookings.filter((item) => String(item.status || '').toUpperCase() === 'PENDING').length;
  const done = bookings.filter((item) => String(item.status || '').toUpperCase() === 'COMPLETED').length;

  document.getElementById('totalBookings').textContent = total;
  document.getElementById('pendingCount').textContent = pending;
  document.getElementById('doneCount').textContent = done;
  document.getElementById('tierCount').textContent = Array.isArray(tierRules) ? tierRules.length : 0;
}

function filteredDashboardBookings() {
  const query = (document.getElementById('bookingSearch')?.value || '').trim().toLowerCase();
  const status = document.getElementById('bookingStatusFilter')?.value || 'ALL';
  const tier = document.getElementById('bookingTierFilter')?.value || 'ALL';

  return state.bookings.filter((item) => {
    const haystack = [item.booking_code, item.id, getCustomerName(item), getVehicleName(item), getServiceName(item)].join(' ').toLowerCase();
    const matchQuery = !query || haystack.includes(query);
    const matchStatus = status === 'ALL' || String(item.status || '').toUpperCase() === status;
    const matchTier = tier === 'ALL' || getBookingTier(item) === tier;
    return matchQuery && matchStatus && matchTier;
  });
}

function renderDashboardBookings() {
  const tbody = document.getElementById('bookingTableBody');
  if (!tbody) return;

  const filtered = filteredDashboardBookings();
  const totalPages = Math.max(1, Math.ceil(filtered.length / state.bookingPageSize));
  if (state.bookingPage > totalPages) state.bookingPage = totalPages;
  const start = (state.bookingPage - 1) * state.bookingPageSize;
  const pageItems = filtered.slice(start, start + state.bookingPageSize);

  if (!pageItems.length) {
    renderEmpty(tbody, 8, 'Không có booking phù hợp với bộ lọc.');
  } else {
    tbody.innerHTML = pageItems.map((item) => {
      const bookingId = item.booking_code || item.id;
      const tier = getBookingTier(item);
      return `
        <tr>
          <td>${escapeHtml(bookingId)}</td>
          <td>${escapeHtml(getCustomerName(item))}</td>
          <td><span class="badge ${tierClass(tier)}">${escapeHtml(tier)}</span></td>
          <td>${escapeHtml(getVehicleName(item))}</td>
          <td>${escapeHtml(getServiceName(item))}<br><span class="small">${formatMoney(item.service_amount || item.amount || item.total_amount)}</span></td>
          <td>${escapeHtml(formatDateTime(item.scheduled_at || item.booking_time || item.date))}</td>
          <td><span class="badge ${statusClass(item.status)}">${formatStatus(item.status)}</span></td>
          <td class="actions">
            <button class="btn small success" data-action="complete" data-id="${escapeHtml(item.id)}">Hoàn tất</button>
            <button class="btn small danger" data-action="delete" data-id="${escapeHtml(item.id)}">Xóa</button>
          </td>
        </tr>`;
    }).join('');
  }

  const pager = document.getElementById('bookingPagination');
  if (pager) {
    pager.innerHTML = `
      <span class="small">${filtered.length} kết quả • Trang ${state.bookingPage}/${totalPages}</span>
      <button class="btn small" data-page-action="prev" ${state.bookingPage <= 1 ? 'disabled' : ''}>Trước</button>
      <button class="btn small" data-page-action="next" ${state.bookingPage >= totalPages ? 'disabled' : ''}>Sau</button>`;
  }
}

function renderDashboardSummary(tierRules, promotions) {
  const tierBox = document.getElementById('tierRulesBox');
  const promoBox = document.getElementById('promoBox');

  if (tierBox) {
    const list = tierRules.map((rule) => {
      const name = normalizeTier(rule.name || rule.code);
      return `<div class="config-item"><span><strong>${escapeHtml(name)}</strong><br><span class="sub">Spend: ${formatMoney(rule.min_spend)} • Visits: ${Number(rule.min_visits || 0)} • Window: ${Number(rule.booking_window_days || 0)} ngày</span></span><span class="badge ${tierClass(name)}">P${Number(rule.priority || tierPriority(name))}</span></div>`;
    }).join('');
    tierBox.innerHTML = `<div class="config-list">${list}</div>`;
  }

  if (promoBox) {
    const promo = promotions.slice(0, 4).map((item) => `<div class="config-item"><span><strong>${escapeHtml(item.name || 'Khuyến mãi')}</strong><br><span class="sub">${escapeHtml(item.code || '')} • ${escapeHtml(item.status || 'ACTIVE')}</span></span><a class="btn small" href="promotions.php">Sửa</a></div>`).join('');
    promoBox.innerHTML = `<div class="config-list">${promo || '<div class="empty">Chưa có khuyến mãi.</div>'}</div>`;
  }
}

async function loadDashboard() {
  const apiStatus = document.getElementById('apiStatus');
  const statusNote = document.getElementById('statusNote');
  if (apiStatus) apiStatus.textContent = isDemoMode() ? 'Đang dùng dữ liệu demo' : 'Đang gọi API admin...';
  if (statusNote) statusNote.textContent = 'Đang tải booking, tier rules và promotion.';

  const [bookings, tierRules, promotions] = await Promise.all([getBookings(), getTierRules(), getPromotions()]);
  state.bookings = bookings;
  state.tierRules = tierRules;
  state.promotions = promotions;

  renderDashboardStats(bookings, tierRules);
  renderDashboardBookings();
  renderDashboardSummary(tierRules, promotions);

  if (apiStatus) apiStatus.textContent = isDemoMode() ? 'Demo mode — dữ liệu lưu trên trình duyệt' : 'API admin hoặc fallback đã sẵn sàng';
  if (statusNote) statusNote.textContent = 'Admin dashboard đã sẵn sàng: có search, filter, phân trang và thao tác booking.';
}

async function completeBooking(id) {
  if (!id) return;
  if (isDemoMode()) {
    const bookings = readDemo('bookings', DEFAULT_BOOKINGS).map((item) => item.id === id ? { ...item, status: 'COMPLETED' } : item);
    writeDemo('bookings', bookings);
    showToast('Đã hoàn thành booking demo.');
    await loadDashboard();
    return;
  }

  try {
    const result = await requestAdmin(`/bookings/complete?id=${encodeURIComponent(id)}`, { method: 'POST', body: '{}' });
    showToast(result.message || 'Đã hoàn thành booking');
    await loadDashboard();
  } catch (error) {
    showToast(error.message || 'Không thể hoàn thành booking', 'error');
  }
}

async function deleteBooking(id) {
  if (!id || !confirm('Bạn có chắc muốn xóa booking này?')) return;
  if (isDemoMode()) {
    const bookings = readDemo('bookings', DEFAULT_BOOKINGS).filter((item) => item.id !== id);
    writeDemo('bookings', bookings);
    showToast('Đã xóa booking demo.');
    await loadDashboard();
    return;
  }

  try {
    await requestAdmin(`/bookings/${encodeURIComponent(id)}`, { method: 'DELETE' });
    showToast('Xóa booking thành công');
    await loadDashboard();
  } catch (error) {
    showToast(error.message || 'Không thể xóa booking', 'error');
  }
}

function wireDashboardEvents() {
  ['bookingSearch', 'bookingStatusFilter', 'bookingTierFilter'].forEach((id) => {
    document.getElementById(id)?.addEventListener('input', () => {
      state.bookingPage = 1;
      renderDashboardBookings();
    });
  });

  document.getElementById('bookingTableBody')?.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-action]');
    if (!button) return;
    if (button.dataset.action === 'complete') completeBooking(button.dataset.id);
    if (button.dataset.action === 'delete') deleteBooking(button.dataset.id);
  });

  document.getElementById('bookingPagination')?.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-page-action]');
    if (!button) return;
    if (button.dataset.pageAction === 'prev') state.bookingPage -= 1;
    if (button.dataset.pageAction === 'next') state.bookingPage += 1;
    renderDashboardBookings();
  });
}

function renderTierRules(rules) {
  const tbody = document.getElementById('tierRulesTableBody');
  if (!tbody) return;
  if (!rules.length) return renderEmpty(tbody, 7, 'Chưa có tier rule nào.');

  tbody.innerHTML = rules.map((rule) => {
    const name = normalizeTier(rule.name || rule.code);
    return `
      <tr data-tier-id="${escapeHtml(rule.id)}">
        <td><span class="badge ${tierClass(name)}">${escapeHtml(name)}</span><input data-field="name" value="${escapeHtml(name)}" type="hidden" /></td>
        <td><input data-field="min_spend" type="number" min="0" step="50000" value="${Number(rule.min_spend || 0)}" /></td>
        <td><input data-field="min_visits" type="number" min="0" value="${Number(rule.min_visits || 0)}" /></td>
        <td><input data-field="booking_window_days" type="number" min="0" value="${Number(rule.booking_window_days || 0)}" /></td>
        <td><input data-field="priority" type="number" min="1" max="10" value="${Number(rule.priority || tierPriority(name))}" /></td>
        <td><select data-field="is_active"><option value="1" ${rule.is_active !== false ? 'selected' : ''}>Active</option><option value="0" ${rule.is_active === false ? 'selected' : ''}>Inactive</option></select></td>
        <td><button class="btn small primary" data-action="save-tier" data-id="${escapeHtml(rule.id)}">Lưu</button></td>
      </tr>`;
  }).join('');
}

async function loadTierRulesPage() {
  const status = document.getElementById('tierStatus');
  if (status) status.textContent = 'Đang tải tier rules...';
  state.tierRules = await getTierRules();
  renderTierRules(state.tierRules);
  if (status) status.textContent = isDemoMode() ? 'Demo mode — chỉnh sửa sẽ lưu trên trình duyệt.' : 'Dữ liệu tier rules đã sẵn sàng.';
}

function getTierRuleFromRow(row) {
  const data = {};
  row.querySelectorAll('[data-field]').forEach((input) => {
    const field = input.dataset.field;
    if (field === 'is_active') data[field] = input.value === '1';
    else if (['min_spend', 'min_visits', 'booking_window_days', 'priority'].includes(field)) data[field] = Number(input.value || 0);
    else data[field] = input.value;
  });
  return data;
}

async function saveTierRule(id) {
  const row = Array.from(document.querySelectorAll('tr[data-tier-id]')).find((item) => String(item.dataset.tierId) === String(id));
  if (!row) return;
  const payload = getTierRuleFromRow(row);

  if (isDemoMode()) {
    const rules = readDemo('tierRules', DEFAULT_TIER_RULES).map((rule) => String(rule.id) === String(id) ? { ...rule, ...payload } : rule);
    writeDemo('tierRules', rules);
    showToast('Đã lưu tier rule demo.');
    await loadTierRulesPage();
    return;
  }

  try {
    await requestAdmin(`/tier-rules/${encodeURIComponent(id)}`, { method: 'PUT', body: JSON.stringify(payload) });
    showToast('Đã cập nhật tier rule.');
    await loadTierRulesPage();
  } catch (error) {
    showToast(error.message || 'Không thể lưu tier rule', 'error');
  }
}

function wireTierRulesEvents() {
  document.getElementById('tierRulesTableBody')?.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-action="save-tier"]');
    if (button) saveTierRule(button.dataset.id);
  });
  document.getElementById('resetTierDemoBtn')?.addEventListener('click', async () => {
    writeDemo('tierRules', DEFAULT_TIER_RULES);
    showToast('Đã reset tier demo.');
    await loadTierRulesPage();
  });
}

function clearPromotionForm() {
  document.getElementById('promotionId').value = '';
  document.getElementById('promotionFormTitle').textContent = 'Thêm khuyến mãi';
  document.getElementById('promotionName').value = '';
  document.getElementById('promotionCode').value = '';
  document.getElementById('promotionType').value = 'PERCENT';
  document.getElementById('promotionValue').value = 10;
  document.getElementById('promotionStart').value = new Date().toISOString().slice(0, 10);
  const end = new Date(); end.setDate(end.getDate() + 30);
  document.getElementById('promotionEnd').value = end.toISOString().slice(0, 10);
  document.getElementById('promotionLimit').value = 100;
  document.getElementById('promotionStatus').value = 'ACTIVE';
  document.getElementById('promotionDescription').value = '';
}

function promotionPayloadFromForm() {
  return {
    name: document.getElementById('promotionName').value.trim(),
    code: document.getElementById('promotionCode').value.trim().toUpperCase(),
    discount_type: document.getElementById('promotionType').value,
    discount_value: Number(document.getElementById('promotionValue').value || 0),
    start_date: document.getElementById('promotionStart').value,
    end_date: document.getElementById('promotionEnd').value,
    usage_limit: Number(document.getElementById('promotionLimit').value || 0),
    status: document.getElementById('promotionStatus').value,
    description: document.getElementById('promotionDescription').value.trim()
  };
}

function renderPromotions() {
  const list = document.getElementById('promotionList');
  if (!list) return;
  const query = (document.getElementById('promotionSearch')?.value || '').trim().toLowerCase();
  const status = document.getElementById('promotionStatusFilter')?.value || 'ALL';
  const filtered = state.promotions.filter((promo) => {
    const haystack = [promo.name, promo.code, promo.description].join(' ').toLowerCase();
    return (!query || haystack.includes(query)) && (status === 'ALL' || promo.status === status);
  });

  if (!filtered.length) {
    list.innerHTML = '<div class="empty">Không có promotion phù hợp.</div>';
    return;
  }

  list.innerHTML = filtered.map((promo) => `
    <article class="promo-card">
      <div class="config-item" style="padding:0;border:none;background:transparent;">
        <span><h3>${escapeHtml(promo.name)}</h3><span class="sub">Code: <strong>${escapeHtml(promo.code)}</strong> • ${escapeHtml(promo.discount_type)} ${escapeHtml(promo.discount_value)}</span></span>
        <span class="badge ${promo.status === 'ACTIVE' ? 'done' : 'pending'}">${escapeHtml(promo.status)}</span>
      </div>
      <div class="sub">${escapeHtml(promo.description || 'Không có mô tả.')}</div>
      <div class="small">Thời gian: ${escapeHtml(promo.start_date)} → ${escapeHtml(promo.end_date)} • Đã dùng: ${Number(promo.used_count || 0)}/${Number(promo.usage_limit || 0)}</div>
      <div class="row-actions">
        <button class="btn small" data-action="edit-promotion" data-id="${escapeHtml(promo.id)}">Sửa</button>
        <button class="btn small danger" data-action="delete-promotion" data-id="${escapeHtml(promo.id)}">Xóa</button>
      </div>
    </article>`).join('');
}

async function loadPromotionsPage() {
  state.promotions = await getPromotions();
  renderPromotions();
  clearPromotionForm();
}

function editPromotion(id) {
  const promo = state.promotions.find((item) => String(item.id) === String(id));
  if (!promo) return;
  document.getElementById('promotionId').value = promo.id;
  document.getElementById('promotionFormTitle').textContent = `Sửa khuyến mãi: ${promo.name}`;
  document.getElementById('promotionName').value = promo.name || '';
  document.getElementById('promotionCode').value = promo.code || '';
  document.getElementById('promotionType').value = promo.discount_type || 'PERCENT';
  document.getElementById('promotionValue').value = promo.discount_value || 0;
  document.getElementById('promotionStart').value = promo.start_date || toInputDate();
  document.getElementById('promotionEnd').value = promo.end_date || toInputDate();
  document.getElementById('promotionLimit').value = promo.usage_limit || 0;
  document.getElementById('promotionStatus').value = promo.status || 'ACTIVE';
  document.getElementById('promotionDescription').value = promo.description || '';
}

async function savePromotion(event) {
  event.preventDefault();
  const id = document.getElementById('promotionId').value;
  const payload = promotionPayloadFromForm();
  if (!payload.name || !payload.code) return showToast('Vui lòng nhập tên và mã khuyến mãi.', 'error');

  if (isDemoMode()) {
    const promotions = readDemo('promotions', DEFAULT_PROMOTIONS);
    if (id) {
      const updated = promotions.map((item) => String(item.id) === String(id) ? { ...item, ...payload } : item);
      writeDemo('promotions', updated);
    } else {
      promotions.unshift({ id: Date.now(), used_count: 0, ...payload });
      writeDemo('promotions', promotions);
    }
    showToast('Đã lưu promotion demo.');
    await loadPromotionsPage();
    return;
  }

  try {
    if (id) {
      await requestAdmin(`/promotions/${encodeURIComponent(id)}`, { method: 'PUT', body: JSON.stringify(payload) });
    } else {
      await requestAdmin('/promotions', { method: 'POST', body: JSON.stringify(payload) });
    }
    showToast('Đã lưu promotion.');
    await loadPromotionsPage();
  } catch (error) {
    showToast(error.message || 'Không thể lưu promotion', 'error');
  }
}

async function deletePromotion(id) {
  if (!confirm('Bạn có chắc muốn xóa promotion này?')) return;
  if (isDemoMode()) {
    const promotions = readDemo('promotions', DEFAULT_PROMOTIONS).filter((item) => String(item.id) !== String(id));
    writeDemo('promotions', promotions);
    showToast('Đã xóa promotion demo.');
    await loadPromotionsPage();
    return;
  }
  try {
    await requestAdmin(`/promotions/${encodeURIComponent(id)}`, { method: 'DELETE' });
    showToast('Đã xóa promotion.');
    await loadPromotionsPage();
  } catch (error) {
    showToast(error.message || 'Không thể xóa promotion', 'error');
  }
}

function wirePromotionEvents() {
  document.getElementById('promotionForm')?.addEventListener('submit', savePromotion);
  document.getElementById('cancelPromotionEditBtn')?.addEventListener('click', clearPromotionForm);
  ['promotionSearch', 'promotionStatusFilter'].forEach((id) => document.getElementById(id)?.addEventListener('input', renderPromotions));
  document.getElementById('promotionList')?.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-action]');
    if (!button) return;
    if (button.dataset.action === 'edit-promotion') editPromotion(button.dataset.id);
    if (button.dataset.action === 'delete-promotion') deletePromotion(button.dataset.id);
  });
}

function buildDemoQueue(dateValue) {
  const bookings = readDemo('bookings', DEFAULT_BOOKINGS);
  const wantedDate = dateValue || new Date().toISOString().slice(0, 10);
  return bookings
    .filter((item) => toInputDate(item.scheduled_at || item.date) === wantedDate)
    .map((item) => {
      const tier = getBookingTier(item);
      const score = Number(item.priority_score || tierPriority(tier) * 100 + Math.max(0, 24 - new Date(item.scheduled_at).getHours()));
      return { ...item, customer_tier: tier, priority_score: score };
    })
    .sort((a, b) => Number(b.priority_score || 0) - Number(a.priority_score || 0));
}

async function getQueue(dateValue) {
  try {
    const result = await requestAdmin(`/bookings/queue?date=${encodeURIComponent(dateValue)}`);
    const data = normalizeApiResponse(result);
    if (Array.isArray(data)) return data;
    throw new Error('Dữ liệu queue không hợp lệ');
  } catch (_) {
    return buildDemoQueue(dateValue);
  }
}

function renderQueue(items) {
  const tbody = document.getElementById('queueTableBody');
  if (!tbody) return;
  const tier = document.getElementById('queueTierFilter')?.value || 'ALL';
  const status = document.getElementById('queueStatusFilter')?.value || 'ALL';
  const filtered = items.filter((item) => {
    return (tier === 'ALL' || getBookingTier(item) === tier) && (status === 'ALL' || String(item.status || '').toUpperCase() === status);
  });

  if (!filtered.length) return renderEmpty(tbody, 8, 'Không có booking trong queue theo bộ lọc.');

  tbody.innerHTML = filtered.map((item, index) => {
    const tierName = getBookingTier(item);
    return `
      <tr>
        <td><strong>${index + 1}</strong></td>
        <td>${escapeHtml(item.booking_code || item.id)}</td>
        <td>${escapeHtml(getCustomerName(item))}</td>
        <td><span class="badge ${tierClass(tierName)}">${escapeHtml(tierName)}</span></td>
        <td>${escapeHtml(getVehicleName(item))}</td>
        <td>${escapeHtml(formatDateTime(item.scheduled_at || item.date))}</td>
        <td><strong>${Number(item.priority_score || tierPriority(tierName) * 100)}</strong></td>
        <td><span class="badge ${statusClass(item.status)}">${formatStatus(item.status)}</span></td>
      </tr>`;
  }).join('');
}

async function loadQueuePage() {
  const input = document.getElementById('queueDate');
  if (input && !input.value) input.value = new Date().toISOString().slice(0, 10);
  const status = document.getElementById('queueStatus');
  if (status) status.textContent = 'Đang tải priority queue...';
  state.queue = await getQueue(input?.value);
  renderQueue(state.queue);
  if (status) status.textContent = isDemoMode() ? 'Demo mode — queue được tính từ booking demo.' : 'Priority queue đã sẵn sàng.';
}

function wireQueueEvents() {
  ['queueDate', 'queueTierFilter', 'queueStatusFilter'].forEach((id) => {
    document.getElementById(id)?.addEventListener('input', () => id === 'queueDate' ? loadQueuePage() : renderQueue(state.queue || []));
  });
}

function fallbackAnalytics() {
  const bookings = readDemo('bookings', DEFAULT_BOOKINGS);
  const completed = bookings.filter((item) => String(item.status || '').toUpperCase() === 'COMPLETED');
  const tierMap = {};
  const revenueMap = {};
  const hourMap = {};
  const customerMap = {};

  bookings.forEach((item) => {
    const tier = getBookingTier(item);
    tierMap[tier] = (tierMap[tier] || 0) + 1;
    const date = toInputDate(item.scheduled_at || item.date);
    revenueMap[date] = (revenueMap[date] || 0) + Number(item.service_amount || 0);
    const hour = new Date(item.scheduled_at || item.date).getHours();
    if (!Number.isNaN(hour)) hourMap[`${hour}:00`] = (hourMap[`${hour}:00`] || 0) + 1;
    const name = getCustomerName(item);
    customerMap[name] = customerMap[name] || { customer_name: name, tier, booking_count: 0, total_spend: 0 };
    customerMap[name].booking_count += 1;
    customerMap[name].total_spend += Number(item.service_amount || 0);
  });

  const uniqueCustomers = Object.keys(customerMap).length;
  const returningCustomers = Object.values(customerMap).filter((item) => item.booking_count > 1).length;

  return {
    summary: {
      total_bookings: bookings.length,
      total_revenue: completed.reduce((sum, item) => sum + Number(item.service_amount || 0), 0),
      total_customers: uniqueCustomers,
      retention_rate: uniqueCustomers ? Math.round(returningCustomers / uniqueCustomers * 100) : 0
    },
    tierDistribution: Object.entries(tierMap).map(([tier, count]) => ({ tier, count })),
    revenueByDay: Object.entries(revenueMap).sort().map(([date, revenue]) => ({ date, revenue })),
    bookingByHour: Object.entries(hourMap).sort((a, b) => parseInt(a[0]) - parseInt(b[0])).map(([hour, count]) => ({ hour, count })),
    topCustomers: Object.values(customerMap).sort((a, b) => b.total_spend - a.total_spend).slice(0, 5)
  };
}

async function getAnalytics() {
  try {
    const [summaryRes, tierRes, hourRes] = await Promise.all([
      requestAdmin('/analytics/summary'),
      requestAdmin('/analytics/tier-distribution'),
      requestAdmin('/analytics/booking-by-hour')
    ]);
    const fallback = fallbackAnalytics();
    return {
      summary: normalizeApiResponse(summaryRes) || fallback.summary,
      tierDistribution: normalizeApiResponse(tierRes) || fallback.tierDistribution,
      bookingByHour: normalizeApiResponse(hourRes) || fallback.bookingByHour,
      revenueByDay: (normalizeApiResponse(summaryRes)?.revenue_by_day) || fallback.revenueByDay,
      topCustomers: (normalizeApiResponse(summaryRes)?.top_customers) || fallback.topCustomers
    };
  } catch (_) {
    return fallbackAnalytics();
  }
}

function drawChart(id, config) {
  const canvas = document.getElementById(id);
  if (!canvas || typeof Chart === 'undefined') return;
  if (state.charts[id]) state.charts[id].destroy();
  state.charts[id] = new Chart(canvas, config);
}

function renderAnalytics(data) {
  document.getElementById('analyticsTotalBookings').textContent = Number(data.summary.total_bookings || 0);
  document.getElementById('analyticsRevenue').textContent = formatMoney(data.summary.total_revenue || 0);
  document.getElementById('analyticsCustomers').textContent = Number(data.summary.total_customers || 0);
  document.getElementById('analyticsRetention').textContent = `${Number(data.summary.retention_rate || 0)}%`;

  drawChart('tierDistributionChart', {
    type: 'pie',
    data: { labels: data.tierDistribution.map((x) => x.tier || x.name), datasets: [{ data: data.tierDistribution.map((x) => x.count || x.total) }] },
    options: { responsive: true, maintainAspectRatio: false }
  });

  drawChart('revenueChart', {
    type: 'bar',
    data: { labels: data.revenueByDay.map((x) => x.date || x.day), datasets: [{ label: 'Doanh thu', data: data.revenueByDay.map((x) => x.revenue || x.total_revenue || 0) }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
  });

  drawChart('bookingHourChart', {
    type: 'bar',
    data: { labels: data.bookingByHour.map((x) => x.hour), datasets: [{ label: 'Booking', data: data.bookingByHour.map((x) => x.count || x.total || 0) }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
  });

  const tbody = document.getElementById('topCustomersTableBody');
  if (tbody) {
    const rows = data.topCustomers || [];
    if (!rows.length) return renderEmpty(tbody, 4, 'Chưa có dữ liệu khách hàng.');
    tbody.innerHTML = rows.map((item) => `
      <tr>
        <td>${escapeHtml(item.customer_name || item.name || 'Khách hàng')}</td>
        <td><span class="badge ${tierClass(item.tier)}">${escapeHtml(normalizeTier(item.tier))}</span></td>
        <td>${Number(item.booking_count || item.visits || 0)}</td>
        <td>${formatMoney(item.total_spend || item.total_revenue || 0)}</td>
      </tr>`).join('');
  }
}

async function loadAnalyticsPage() {
  const status = document.getElementById('analyticsStatus');
  if (status) status.textContent = 'Đang tải dữ liệu analytics...';
  const data = await getAnalytics();
  renderAnalytics(data);
  if (status) status.textContent = isDemoMode() ? 'Demo mode — analytics tính từ booking demo.' : 'Analytics đã sẵn sàng.';
}

function setupLogin() {
  if (new URLSearchParams(window.location.search).has('logout')) {
    localStorage.removeItem('adminToken');
    localStorage.removeItem('adminUser');
  }

  const messageBox = document.getElementById('messageBox');
  const showMessage = (text, type = 'error') => {
    messageBox.textContent = text;
    messageBox.style.display = 'block';
    messageBox.style.background = type === 'success' ? 'rgba(74,222,128,0.12)' : 'rgba(248,113,113,0.12)';
    messageBox.style.borderColor = type === 'success' ? 'rgba(74,222,128,0.25)' : 'rgba(248,113,113,0.25)';
    messageBox.style.color = type === 'success' ? '#bbf7d0' : '#fecaca';
  };

  document.getElementById('loginForm')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    showMessage('Đang đăng nhập...', 'success');

    try {
      const response = await fetch(`${ADMIN_API}/auth/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password })
      });
      const result = await response.json().catch(() => ({}));
      const payload = result && typeof result === 'object' && 'data' in result ? result.data : result;
      const token = payload && (payload.access_token || payload.token || payload.data?.access_token);

      if (response.ok && token) {
        localStorage.setItem('adminToken', token);
        localStorage.setItem('adminUser', JSON.stringify(payload.user || { username }));
        showMessage('Đăng nhập thành công. Đang chuyển đến dashboard...', 'success');
        window.location.href = 'dashboard.php';
        return;
      }

      if (username === 'admin' && password === 'admin123') {
        localStorage.setItem('adminToken', DEMO_TOKEN);
        localStorage.setItem('adminUser', JSON.stringify({ username: 'admin', role: 'demo' }));
        showMessage('Backend chưa sẵn — đã bật chế độ demo cho admin.', 'success');
        setTimeout(() => window.location.href = 'dashboard.php', 350);
        return;
      }

      showMessage(result.message || 'Đăng nhập thất bại', 'error');
    } catch (error) {
      if (username === 'admin' && password === 'admin123') {
        localStorage.setItem('adminToken', DEMO_TOKEN);
        localStorage.setItem('adminUser', JSON.stringify({ username: 'admin', role: 'demo' }));
        showMessage('Không kết nối được backend — đã bật chế độ demo.', 'success');
        setTimeout(() => window.location.href = 'dashboard.php', 350);
        return;
      }
      showMessage('Không thể kết nối API admin. Nếu chỉ muốn demo, dùng admin / admin123.', 'error');
    }
  });
}

function wireGlobalEvents() {
  document.querySelectorAll('[data-logout]').forEach((button) => button.addEventListener('click', logout));
  document.getElementById('refreshBtn')?.addEventListener('click', () => {
    if (PAGE === 'dashboard') loadDashboard();
    if (PAGE === 'tier-rules') loadTierRulesPage();
    if (PAGE === 'promotions') loadPromotionsPage();
    if (PAGE === 'queue') loadQueuePage();
    if (PAGE === 'analytics') loadAnalyticsPage();
  });
}

window.addEventListener('DOMContentLoaded', () => {
  requireAuth();
  setActiveNav();
  wireGlobalEvents();

  if (PAGE === 'login') setupLogin();
  if (PAGE === 'dashboard') { wireDashboardEvents(); loadDashboard(); }
  if (PAGE === 'tier-rules') { wireTierRulesEvents(); loadTierRulesPage(); }
  if (PAGE === 'promotions') { wirePromotionEvents(); loadPromotionsPage(); }
  if (PAGE === 'queue') { wireQueueEvents(); loadQueuePage(); }
  if (PAGE === 'analytics') loadAnalyticsPage();
});
