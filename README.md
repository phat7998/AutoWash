# AutoWash Pro

Smart Automated Car Wash Management System with Advance Booking & Loyalty Program.

## 1. Đề tài

AutoWash Pro là hệ thống quản lý rửa xe máy thông minh, tập trung vào:

- Đăng ký, đăng nhập và quản lý khách hàng theo số điện thoại/biển số xe.
- Đặt lịch rửa xe trước theo hạng thành viên.
- Loyalty engine: tích điểm, đổi điểm, tự động nâng/hạ tier.
- Tier Member, Silver, Gold, Platinum với quyền đặt lịch trước khác nhau.
- Priority queue: khách tier cao được ưu tiên trong hàng đợi.
- Promotion cho từng nhóm tier.
- Sinh dữ liệu hành vi, phân tích thống kê và ML để trả lời research question.

Research Question:

> What factors most influence customer loyalty tier progression in smart service ecosystems?

## 2. Công nghệ

- PHP 8.2+
- Yii2 Advanced style structure
- MySQL/MariaDB
- Frontend thuần PHP, HTML, CSS, JavaScript
- Python cho phân tích dữ liệu và machine learning
- pandas, numpy, matplotlib, scikit-learn

## 3. Cấu trúc chính

| Thư mục | Vai trò |
|---|---|
| `common/models` | Entity dùng chung: Customer, Vehicle, Booking, LoyaltyAccount, TierRule, Promotion |
| `common/services` | Logic nghiệp vụ dùng chung, đặc biệt `LoyaltyService` |
| `customer` | Customer REST API |
| `backend/modules/api` | Admin REST API |
| `customer-frontend` | Giao diện khách hàng |
| `admin-frontend` | Giao diện admin nhẹ để demo |
| `console/migrations` | Migration tạo database |
| `console/controllers` | Seed data và loyalty scheduled jobs |
| `scripts` | Sinh fake data, export CSV, phân tích dữ liệu, ML |
| `data` | Dataset CSV và kết quả phân tích/ML |

## 4. Chức năng đã có

### Customer

- Đăng ký tài khoản khách hàng.
- Tự tạo loyalty account hạng Member.
- Tự tạo vehicle khi nhập biển số lúc đăng ký.
- Đăng nhập bằng số điện thoại và mật khẩu.
- Xem thông tin loyalty: điểm, tier, tổng chi tiêu, số lần rửa.
- Xem tiến độ lên tier kế tiếp.
- Đặt lịch theo booking window của tier hiện tại:
  - Member: 7 ngày
  - Silver: 10 ngày
  - Gold: 12 ngày
  - Platinum: 14 ngày
- Xem lịch sử booking.
- Redeem điểm: điểm đổi phải là bội số của 10 và không vượt quá số dư.

### Admin

- Xem danh sách booking.
- Hoàn thành booking để cộng điểm loyalty.
- Xóa booking.
- Xem tier rules.
- Xem promotions.
- Priority queue theo ngày: booking được sắp theo tier cao trước, sau đó theo thời gian đặt.
- Analytics API:
  - Tổng quan khách hàng, booking, doanh thu, điểm.
  - Phân bố tier.
  - Booking theo giờ.
  - Retention rate.

### Data & ML

- Sinh synthetic behavioral dataset.
- Export:
  - `data/bookings.csv`
  - `data/customers.csv`
  - `data/transactions.csv`
- EDA:
  - Tier distribution
  - Spending by tier
  - Booking frequency
  - Cancel rate by tier
- Hypothesis testing:
  - Wash count ảnh hưởng đến tier.
  - Lifetime spend tương quan với tier.
- ML:
  - Logistic Regression
  - Random Forest
  - Feature Importance

## 5. Cài đặt local

### 5.1. Chuẩn bị

Cần có:

- PHP 8.2+
- Composer
- MySQL/MariaDB
- Python 3.10+ nếu chạy phần data/ML

### 5.2. Cấu hình database Yii2

Sửa thông tin DB trong:

- `common/config/main-local.php`

Ví dụ:

```php
'db' => [
    'class' => yii\db\Connection::class,
    'dsn' => 'mysql:host=localhost;dbname=autowash',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
],
```

Tạo database:

```sql
CREATE DATABASE autowash CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5.3. Cài dependency

Nếu máy chưa có `vendor`:

```bash
composer install
```

### 5.4. Chạy migration và seed

```bash
php yii migrate
php yii seed/fresh
```

Kiểm tra dữ liệu:

```bash
php yii seed/status
```

### 5.5. Chạy scheduled jobs loyalty

Review tier:

```bash
php yii loyalty/review-tiers
```

Expire points:

```bash
php yii loyalty/expire-points
```

## 6. Chạy demo frontend

Đặt repo trong web root, ví dụ với XAMPP:

```text
htdocs/AutoWash
```

Customer frontend:

```text
http://localhost/AutoWash/customer-frontend/index.php
```

Admin frontend:

```text
http://localhost/AutoWash/admin-frontend/admin/login.php
```

Customer API:

```text
http://localhost/AutoWash/customer/web
```

Admin API:

```text
http://localhost/AutoWash/backend/web
```

## 7. API demo quan trọng

### Customer

| Method | Endpoint | Chức năng |
|---|---|---|
| `POST` | `/auth/register` | Đăng ký khách hàng |
| `POST` | `/auth/login` | Đăng nhập |
| `GET` | `/auth/profile` | Hồ sơ khách hàng |
| `GET` | `/loyalty/balance` | Điểm và tier hiện tại |
| `GET` | `/loyalty/next-tier` | Tiến độ lên tier |
| `POST` | `/loyalty/redeem` | Đổi điểm |
| `GET` | `/bookings` | Lịch sử booking |
| `POST` | `/bookings` | Tạo booking |
| `GET` | `/vehicles` | Danh sách xe |

### Admin

| Method | Endpoint | Chức năng |
|---|---|---|
| `POST` | `/auth/login` | Đăng nhập admin |
| `GET` | `/bookings` | Danh sách booking |
| `POST` | `/bookings/complete?id=1` | Hoàn thành booking và cộng điểm |
| `GET` | `/bookings/queue?date=2026-07-11` | Priority queue |
| `GET` | `/tier-rules` | Danh sách tier rules |
| `GET` | `/promotions` | Danh sách promotion |
| `GET` | `/analytics/summary` | Tổng quan hệ thống |
| `GET` | `/analytics/tier-distribution` | Phân bố tier |
| `GET` | `/analytics/booking-by-hour` | Booking theo giờ |
| `GET` | `/analytics/retention` | Retention rate |

## 8. Chạy data và ML

Các script dùng biến môi trường để kết nối DB:

```bash
export AUTOWASH_DB_HOST=localhost
export AUTOWASH_DB_NAME=autowash
export AUTOWASH_DB_USER=root
export AUTOWASH_DB_PASS=
```

Sinh thêm dữ liệu fake:

```bash
php scripts/generate_fake_data.php --records=3000 --fresh
```

Export CSV:

```bash
php scripts/export_data.php
```

Chạy phân tích:

```bash
python scripts/analysis/01_exploratory_analysis.py
python scripts/analysis/02_hypothesis_testing.py
python scripts/ml/03_logistic_regression.py
python scripts/ml/04_random_forest.py
python scripts/ml/05_feature_importance.py
```

Kết quả nằm trong:

- `data/results_summary.md`
- `data/results/*.csv`
- `data/results/*.png`

## 9. Kết quả ML hiện tại

Theo `data/results_summary.md`:

- Lifetime spend là yếu tố ảnh hưởng mạnh nhất đến tier progression.
- Wash count đứng thứ hai.
- Cancel rate và average service amount có ảnh hưởng nhỏ hơn.
- Promotion usage trong dataset hiện tại chưa thể hiện tác động rõ.

Top factors:

| Rank | Yếu tố | Importance |
|---|---|---|
| 1 | Tổng chi tiêu tích lũy | 0.6070 |
| 2 | Số lần rửa xe | 0.2936 |
| 3 | Tỉ lệ hủy đặt lịch | 0.0529 |
| 4 | Chi tiêu trung bình/lần | 0.0465 |
| 5 | Tỉ lệ sử dụng khuyến mãi | 0.0000 |

## 10. Kịch bản demo ngắn

1. Mở customer frontend.
2. Đăng ký khách hàng mới, nhập biển số xe.
3. Đăng nhập.
4. Vào dashboard xem tier Member, điểm 0, booking window 7 ngày.
5. Tạo booking trong giới hạn ngày.
6. Mở admin frontend.
7. Đăng nhập admin.
8. Bấm hoàn tất booking.
9. Quay lại customer dashboard, thấy điểm tăng, wash count tăng.
10. Chạy `php yii loyalty/review-tiers` để demo auto tier review.
11. Mở `data/results_summary.md` và biểu đồ trong `data/results` để trình bày phần Data/ML.

## 11. Phần chưa làm hoặc giới hạn

- Không tích hợp online payment và refund theo giới hạn đề tài.
- AI personalization chỉ dừng ở phân tích dữ liệu/ML độc lập, chưa nhúng real-time vào booking flow.
- Dataset hiện tại chủ yếu là synthetic data, cần nói rõ trong báo cáo.
- Admin frontend là bản demo nhẹ, API backend mới là phần chính.
