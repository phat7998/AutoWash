# TV3 Admin Frontend — Tuần 6 đến Tuần 10

Gói này tiếp tục từ phần TV3 tuần 5 và hoàn thành các đầu việc Admin Frontend từ tuần 6 đến hết tuần 10.

## Cấu trúc cần copy vào project

```text
admin-frontend/
├── admin/
│   ├── login.php
│   ├── dashboard.php
│   ├── tier_rules.php
│   ├── promotions.php
│   ├── queue.php
│   └── analytics.php
└── assets/
    ├── css/
    │   └── admin.css
    └── js/
        └── admin.js
```

## Đã làm theo từng tuần

### Tuần 6 — Admin Config
- `admin/tier_rules.php`: xem và sửa `min_spend`, `min_visits`, `booking_window_days`, `priority`, trạng thái active.
- `admin/promotions.php`: thêm, sửa, xóa promotion; có tìm kiếm và lọc trạng thái.
- `admin/dashboard.php`: thêm section quản lý config, link tới Tier Rules và Promotions.

### Tuần 7 — Priority Queue
- `admin/queue.php`: xem priority queue theo ngày.
- Có filter theo tier và trạng thái.
- Dashboard/sidebar đã có menu `Priority Queue`.

### Tuần 8 — Analytics Dashboard
- `admin/analytics.php`: dùng Chart.js CDN để vẽ:
  - Pie chart phân bố tier.
  - Bar chart doanh thu theo ngày.
  - Bar chart booking theo giờ.
  - Bảng top khách hàng thân thiết.

### Tuần 9 — Hoàn thiện Admin
- Dashboard responsive.
- Booking list có search, filter theo trạng thái/tier và phân trang.
- UI/UX có toast, nút reload, trạng thái API/demo rõ ràng.

### Tuần 10 — Test admin flow
Admin có thể test đủ flow:
1. Đăng nhập admin.
2. Xem booking.
3. Hoàn tất hoặc xóa booking.
4. Sửa tier rules.
5. Thêm/sửa/xóa promotion.
6. Xem priority queue.
7. Xem analytics.

## Tài khoản demo

Nếu backend chưa chạy, dùng:

```text
username: admin
password: admin123
```

Khi dùng tài khoản demo, dữ liệu sẽ lưu bằng `localStorage`, nên vẫn có thể quay video demo mà không cần backend.

## API đang gọi

File `assets/js/admin.js` đang gọi các endpoint sau nếu có token backend thật:

```text
POST   /auth/login
GET    /bookings
POST   /bookings/complete?id={id}
DELETE /bookings/{id}
GET    /tier-rules
PUT    /tier-rules/{id}
GET    /promotions
POST   /promotions
PUT    /promotions/{id}
DELETE /promotions/{id}
GET    /bookings/queue?date=YYYY-MM-DD
GET    /analytics/summary
GET    /analytics/tier-distribution
GET    /analytics/booking-by-hour
```

Nếu API thật chưa đúng tên endpoint, chỉ cần sửa trong `assets/js/admin.js` tại các hàm `requestAdmin(...)` tương ứng.

## Ghi chú để nộp

- Đây chỉ là phần **TV3 Admin Frontend**, không đụng file backend/TV1/TV2/TV4.
- Có thể copy đè `admin/dashboard.php`, `admin/login.php`, `assets/js/admin.js` cũ bằng bản trong gói này.
- Nếu nhóm đã có CSS riêng thì vẫn nên giữ `assets/css/admin.css` để các trang mới chạy đúng giao diện.
