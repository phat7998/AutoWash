# PHÂN CÔNG CÔNG VIỆC TUẦN 6-10 — CAR WASH LOYALTY

> Dựa trên file `ke_hoach_nhom.txt` và hiện trạng codebase (đã có Yii2 backend, customer API, frontend cơ bản).

---

## TUẦN 6: Hoàn thiện Loyalty Engine + Admin Config

### TV1 (Backend) — Loyalty Engine
- [ ] Hoàn thiện **Redeem flow**: tạo `customer/controllers/RedeemController.php` — actionRedeem() tạo PointTransaction loại REDEEM, trừ điểm, kiểm tra `canRedeem()`
- [ ] Hoàn thiện **next tier progress** trong `customer/controllers/LoyaltyController.php` — tính % hoàn thành lên tier kế tiếp
- [ ] Thêm API `GET /api/loyalty/next-tier` trả về tier hiện tại + tier kế + cần bao nhiêu spend/visit nữa
- [ ] Sửa `console/controllers/LoyaltyController.php` — actionReviewTiers + actionExpirePoints chạy ổn định (có log output)

### TV2 (Customer Frontend) — Hiển thị Loyalty
- [ ] Tạo `customer-frontend/redeem.php` — trang đổi điểm: hiển thị danh sách phần thưởng (discount, free wash), form nhập số điểm muốn đổi
- [ ] Sửa `customer-frontend/dashboard.php`:
  - [ ] Hiển thị **progress bar** lên tier tiếp theo (dùng API next-tier)
  - [ ] Hiển thị điểm sắp hết hạn (expiring points)
- [ ] Tạo `customer-frontend/rewards.php` — lịch sử đổi thưởng

### TV3 (Admin Frontend) — Admin Config
- [ ] Tạo `admin-frontend/admin/tier_rules.php` — giao diện quản lý Tier Rules (xem, sửa min_spend, min_visits, booking_window_days, priority)
- [ ] Tạo `admin-frontend/admin/promotions.php` — giao diện quản lý Promotion (thêm, sửa, xóa)
- [ ] Sửa `admin-frontend/admin/dashboard.php` — thêm section quản lý config (link tới tier_rules, promotions)

### TV4 (Data) — Seed Data + Fake Data Script
- [ ] Hoàn thiện `console/controllers/SeedController.php` — seed 50-100 users, vehicles, bookings, loyalty_accounts với tier khác nhau
- [ ] Tạo `scripts/generate_fake_data.php` — script PHP chạy độc lập, sinh 2000-5000 booking records với behavioral data (thời gian đặt, hủy, tần suất, etc.)

### TV5 (Báo cáo) — Tài liệu kiến trúc
- [ ] Viết document **Kiến trúc hệ thống** (mô tả các module, database schema, API endpoints)
- [ ] Viết document **Hướng dẫn cài đặt & chạy project**
- [ ] Chụp ảnh màn hình các trang đã hoàn thiện

---

## TUẦN 7: Priority Queue + Promotion + Redeem hoàn chỉnh

### TV1 (Backend) — Priority Queue
- [ ] Tạo `common/services/QueueService.php`:
  - [ ] Hàm `calculatePriority(Booking)` — dựa vào tier (Platinum=4, Gold=3, Silver=2, Member=1) + thời gian đặt
  - [ ] Hàm `getQueue(DateTime)` — trả danh sách booking sắp xếp theo priority
- [ ] Thêm API `GET /api/bookings/queue?date=YYYY-MM-DD` — admin xem priority queue
- [ ] Cập nhật `BookingController` — thêm trường priority_score khi tạo booking

### TV2 (Customer Frontend) — Promotion & Redeem hoàn chỉnh
- [ ] Tích hợp **Promotion** vào trang chủ: hiển thị khuyến mãi đang active từ API (gọi `GET /promotions/active`)
- [ ] Hoàn thiện `redeem.php` — gọi API redeem thật, hiển thị kết quả
- [ ] Thêm thông báo **"Bạn được ưu tiên"** trên booking form nếu tier cao (Gold/Platinum)

### TV3 (Admin Frontend) — Admin Priority Queue
- [ ] Tạo `admin-frontend/admin/queue.php` — giao diện xem priority queue theo ngày, filter theo tier
- [ ] Cập nhật sidebar dashboard — thêm menu "Priority Queue"

### TV4 (Data) — Mở rộng Synthetic Data
- [ ] Chạy `generate_fake_data.php` — sinh 2000-5000 records behavioral data
- [ ] Xuất dữ liệu ra CSV: `data/bookings.csv`, `data/customers.csv`, `data/transactions.csv`
- [ ] Viết script PHP nhỏ `scripts/export_data.php` — export từ DB ra CSV để phục vụ ML

### TV5 (Báo cáo) — Video tiến độ tuần 7
- [ ] Quay video ngắn (3-5 phút) demo các tính năng đã làm được đến tuần 7
- [ ] Cập nhật báo cáo tiến độ

---

## TUẦN 8: Phân tích dữ liệu & Thống kê

### TV1 (Backend) — Hỗ trợ TV4
- [ ] Viết API phục vụ data analysis: `GET /api/analytics/summary` (tổng quan: tổng booking, doanh thu, phân bố tier)
- [ ] Viết API: `GET /api/analytics/tier-distribution`, `GET /api/analytics/booking-by-hour`, `GET /api/analytics/retention`

### TV2 (Customer Frontend) — Dashboard nâng cao
- [ ] Thêm biểu đồ đơn giản trên dashboard khách hàng (dùng Chart.js hoặc canvas tự vẽ):
  - [ ] Lịch sử điểm theo thời gian
  - [ ] Số lần rửa xe theo tháng
  - [ ] So sánh với tier kế tiếp

### TV3 (Admin Frontend) — Admin Analytics Dashboard
- [ ] Tạo `admin-frontend/admin/analytics.php` — giao diện xem thống kê:
  - [ ] Biểu đồ phân bố tier (pie chart)
  - [ ] Doanh thu theo ngày/tuần/tháng (bar chart)
  - [ ] Top khách hàng thân thiết

### TV4 (Data & ML) — Phân tích thống kê
- [ ] Cài đặt Python + thư viện: pandas, numpy, matplotlib, scikit-learn (nếu chưa có)
- [ ] Tạo `scripts/analysis/01_exploratory_analysis.py`:
  - [ ] Thống kê mô tả: mean, median, std của spend, visit frequency
  - [ ] Vẽ biểu đồ: tier distribution, spending by tier, booking frequency
- [ ] Tạo `scripts/analysis/02_hypothesis_testing.py`:
  - [ ] Test hypothesis: "Số lần rửa có ảnh hưởng đến tier progression?"
  - [ ] Test hypothesis: "Chi tiêu tích lũy có tương quan với tier?"
  - [ ] Xuất kết quả dạng bảng + biểu đồ

### TV5 (Báo cáo) — Báo cáo phân tích
- [ ] Viết section **Phân tích dữ liệu** trong báo cáo (kèm biểu đồ từ TV4)
- [ ] Giải thích kết quả statistical tests
- [ ] Trả lời Research Question dựa trên dữ liệu

---

## TUẦN 9: Machine Learning & Hoàn thiện

### TV1 (Backend) — Tối ưu & Fix bug
- [ ] Review toàn bộ API, fix lỗi CORS, authentication, error handling
- [ ] Tối ưu query (thêm index nếu cần)
- [ ] Đảm bảo tất cả API trả về format chuẩn qua `ApiResponseFormatter`

### TV2 (Customer Frontend) — Hoàn thiện giao diện
- [ ] Responsive testing — đảm bảo chạy tốt trên mobile
- [ ] Fix UI bugs, validation messages
- [ ] Tối ưu trải nghiệm: loading spinner, error toast, success notification

### TV3 (Admin Frontend) — Hoàn thiện Admin
- [ ] Responsive admin dashboard
- [ ] Thêm phân trang, search, filter cho booking list
- [ ] Tối ưu UI/UX

### TV4 (Data & ML) — Machine Learning
- [ ] Tạo `scripts/ml/03_logistic_regression.py`:
  - [ ] Target variable: tier_upgraded (có lên tier hay không)
  - [ ] Features: wash_count, total_spend, avg_booking_interval, cancel_rate, promo_usage
  - [ ] Đánh giá: accuracy, precision, recall, confusion matrix
- [ ] Tạo `scripts/ml/04_random_forest.py`:
  - [ ] Feature importance analysis
  - [ ] So sánh với logistic regression
- [ ] Tạo `scripts/ml/05_feature_importance.py`:
  - [ ] Xác định top 5 yếu tố ảnh hưởng đến tier progression
  - [ ] Visualize dạng bar chart
- [ ] Kết luận: trả lời RQ "What factors most influence customer loyalty tier progression?"

### TV5 (Báo cáo) — Báo cáo ML
- [ ] Viết section **Machine Learning** trong báo cáo
- [ ] Chụp kết quả: confusion matrix, feature importance chart, ROC curve
- [ ] Kết luận và khuyến nghị dựa trên kết quả ML

---

## TUẦN 10: Báo cáo & Demo

### TV1 (Backend)
- [ ] Hỗ trợ các thành viên fix bug phát sinh
- [ ] Chạy migration và seed data để demo mượt mà
- [ ] Kiểm tra toàn bộ flow: register → đặt lịch → earn points → upgrade tier → redeem → admin quản lý

### TV2 (Customer Frontend)
- [ ] Chạy thử toàn bộ customer flow, fix lỗi UI
- [ ] Đảm bảo tất cả trang hiển thị đẹp, không lỗi console

### TV3 (Admin Frontend)
- [ ] Chạy thử toàn bộ admin flow, fix lỗi
- [ ] Đảm bảo admin có thể: xem booking, xem queue, config tier/promo, xem analytics

### TV4 (Data & ML)
- [ ] Tổng hợp tất cả kết quả phân tích + ML vào file `data/results_summary.md`
- [ ] Xuất biểu đồ cuối cùng (high-res) để đưa vào báo cáo
- [ ] Chuẩn bị slide nếu cần thuyết trình

### TV5 (Báo cáo & Demo) — **Quan trọng nhất tuần này**
- [ ] Viết **báo cáo đồ án hoàn chỉnh** gồm:
  1. Giới thiệu đề tài
  2. Kiến trúc hệ thống
  3. Công nghệ sử dụng
  4. Tính năng đã làm
  5. Phân tích dữ liệu & Kết quả ML
  6. Kết luận & Hướng phát triển
  7. Tài liệu tham khảo
- [ ] Quay **video demo** (5-7 phút) — quay màn hình chạy qua tất cả tính năng
- [ ] Nộp đồ án

---

## TỔNG KẾT KHỐI LƯỢNG CÔNG VIỆC THEO THÀNH VIÊN

| TV | Vai trò | Tuần 6 | Tuần 7 | Tuần 8 | Tuần 9 | Tuần 10 |
|---|---|---|---|---|---|---|
| **TV1** | Backend | Redeem + Next-tier API + Console | Priority Queue + Queue API | Analytics API | Fix bug + Tối ưu | Hỗ trợ final test |
| **TV2** | Customer FE | Redeem page + Dashboard nâng cao | Promotion tích hợp + Redeem hoàn chỉnh | Dashboard biểu đồ | Responsive + Fix UI | Test customer flow |
| **TV3** | Admin FE | Tier rules UI + Promotions UI | Queue UI | Analytics Dashboard | Search/filter + Responsive | Test admin flow |
| **TV4** | Data & ML | Seed + Fake data script | Export CSV | EDA + Hypothesis testing | ML models (LR + RF) | Tổng hợp kết quả |
| **TV5** | Báo cáo | Kiến trúc + Hướng dẫn | Video tuần 7 | Báo cáo phân tích | Báo cáo ML | Báo cáo cuối + Video demo |

---

## LƯU Ý

- **TV4 cần cài Python + pandas, numpy, matplotlib, scikit-learn vào tuần 8** (hoặc sớm hơn)
- **TV1 + TV4**: ML phần BE chạy Python độc lập, không phải tích hợp vào Yii2 — chỉ cần export CSV từ DB rồi xử lý bằng Python scripts
- **TV2 + TV3**: Có thể dùng Chart.js cho biểu đồ (CDN), không cần cài thêm gì
- **Giao tiếp**: Dùng Zalo như đã thống nhất — mỗi ngày commit ít nhất 1 lần
- **Git**: Pull trước khi push, không sửa file của người khác
