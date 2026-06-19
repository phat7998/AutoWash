const STORAGE_KEY = 'autowash_admin_bookings';

const seedBookings = [
  { id: 'BK-1001', customer: 'Nguyễn An', vehicle: 'Sedan', package: 'Rửa nhanh', status: 'pending', points: 120 },
  { id: 'BK-1002', customer: 'Trần Minh', vehicle: 'SUV', package: 'Rửa cao cấp', status: 'washing', points: 240 },
  { id: 'BK-1003', customer: 'Lê Hòa', vehicle: 'Xe bán tải', package: 'Rửa + bảo dưỡng', status: 'done', points: 300 },
  { id: 'BK-1004', customer: 'Phạm Thu', vehicle: 'City', package: 'Rửa vip', status: 'pending', points: 180 }
];

function readLocalBookings() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

function saveLocalBookings(data) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
}

function getInitialBookings() {
  const local = readLocalBookings();
  if (local && local.length) return local;
  saveLocalBookings(seedBookings);
  return seedBookings;
}

async function callApi(url, options = {}) {
  try {
    const response = await fetch(url, {
      headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
      ...options
    });

    if (!response.ok) throw new Error('API unavailable');
    return await response.json();
  } catch (error) {
    return null;
  }
}

function statusLabel(status) {
  const map = {
    pending: 'Đang chờ',
    washing: 'Đang rửa',
    done: 'Hoàn tất',
    cancelled: 'Đã hủy'
  };
  return map[status] || status;
}

function statusClass(status) {
  return status || 'pending';
}

function renderStats(bookings) {
  const total = bookings.length;
  const pending = bookings.filter(b => b.status === 'pending').length;
  const done = bookings.filter(b => b.status === 'done').length;
  const points = bookings.reduce((sum, b) => sum + (Number(b.points) || 0), 0);

  document.getElementById('totalBookings').textContent = total;
  document.getElementById('pendingCount').textContent = pending;
  document.getElementById('doneCount').textContent = done;
  document.getElementById('pointsCount').textContent = points;
}

function renderBookings(bookings) {
  const tbody = document.getElementById('bookingTableBody');
  if (!tbody) return;

  tbody.innerHTML = bookings.map(item => `
    <tr>
      <td>${item.id}</td>
      <td>${item.customer}</td>
      <td>${item.vehicle}</td>
      <td>${item.package}</td>
      <td><span class="badge ${statusClass(item.status)}">${statusLabel(item.status)}</span></td>
      <td class="actions">
        <button data-action="washing" data-id="${item.id}">Đang rửa</button>
        <button data-action="done" data-id="${item.id}">Hoàn tất</button>
        <button class="delete" data-action="cancelled" data-id="${item.id}">Hủy</button>
      </td>
    </tr>
  `).join('');
}

function updateBooking(id, status) {
  const bookings = getInitialBookings();
  const target = bookings.find(item => item.id === id);
  if (!target) return;

  target.status = status;
  saveLocalBookings(bookings);
  renderAll();
  document.getElementById('statusNote').textContent = `Đã cập nhật ${id} sang trạng thái: ${statusLabel(status)}.`;
}

function renderAll() {
  const bookings = getInitialBookings();
  renderStats(bookings);
  renderBookings(bookings);
}

async function loadDashboard() {
  const apiStatus = document.getElementById('apiStatus');
  const statusNote = document.getElementById('statusNote');

  apiStatus.textContent = 'Đang thử kết nối API...';
  statusNote.textContent = 'Đang kiểm tra backend thật. Nếu không có API, hệ thống sẽ dùng dữ liệu demo tại local.';

  const apiData = await callApi('../api/admin_get_bookings.php');
  if (apiData && Array.isArray(apiData)) {
    saveLocalBookings(apiData);
    apiStatus.textContent = 'API kết nối thành công';
    statusNote.textContent = 'Dữ liệu đang lấy từ API backend. Bạn có thể dùng dữ liệu này cho giao diện admin thật.';
    renderAll();
    return;
  }

  apiStatus.textContent = 'API chưa sẵn sàng, dùng dữ liệu demo';
  statusNote.textContent = 'Frontend admin đang hoạt động với dữ liệu mock để bạn test giao diện trước khi backend hoàn tất.';
  renderAll();
}

window.addEventListener('DOMContentLoaded', () => {
  loadDashboard();

  document.getElementById('refreshBtn')?.addEventListener('click', () => {
    loadDashboard();
  });

  document.getElementById('bookingTableBody')?.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-action]');
    if (!button) return;

    const id = button.getAttribute('data-id');
    const action = button.getAttribute('data-action');

    if (action === 'cancelled') {
      if (!confirm(`Bạn muốn hủy đơn ${id}?`)) return;
    }

    updateBooking(id, action);
  });
});
