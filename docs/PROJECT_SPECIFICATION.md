# AUTOWASH PRO — ĐẶC TẢ DỰ ÁN CHÍNH THỨC

> **Mã đề tài:** SU26SWP01  
> **Tên tiếng Việt:** Hệ thống quản lý rửa xe tự động thông minh với đặt lịch trước và chương trình khách hàng thân thiết  
> **Tên tiếng Anh:** Smart Automated Car Wash Management System with Advance Booking & Loyalty Program  
> **Phiên bản đặc tả:** 2.5 — Slice 13 LPR Attempt Alignment
> **Múi giờ nghiệp vụ:** `Asia/Ho_Chi_Minh`  
> **Trạng thái:** Nguồn sự thật kỹ thuật dùng cho nhóm phát triển và AI Agent
> **Nguồn đã audit tại Slice 00:** `SE_SWP_TopicList_SU26.xlsx`, dòng đề tài `SU26SWP01`; không phát hiện Q&A đã có câu trả lời trong repository tại thời điểm audit.

---

## 0. CÁCH SỬ DỤNG TÀI LIỆU

Tài liệu này là **nguồn sự thật duy nhất ở cấp dự án** cho việc phân tích, thiết kế, lập trình, kiểm thử và chuẩn bị bảo vệ AutoWash Pro.

Thứ tự ưu tiên khi có mâu thuẫn:

1. Yêu cầu chính thức trong đề tài `SU26SWP01`.
2. Các quyết định được giảng viên xác nhận bằng văn bản.
3. Tài liệu đặc tả này.
4. Tài liệu thiết kế chi tiết, issue, task và code.
5. Suy đoán của thành viên hoặc AI Agent.

AI Agent không được tự thêm tính năng chỉ vì “sản phẩm thực tế thường có”. Mọi tính năng mới phải được ghi vào `docs/CHANGELOG_REQUIREMENTS.md`, nêu rõ lý do và được nhóm chấp nhận.

### 0.1. Quy ước mức độ ưu tiên

- **MUST:** Bắt buộc để đáp ứng đề tài và bảo vệ đồ án.
- **SHOULD:** Nên có để tăng chất lượng, điểm số hoặc khả năng giải thích.
- **COULD:** Chỉ làm khi các yêu cầu MUST đã hoàn thành và kiểm thử ổn định.
- **OUT:** Ngoài phạm vi, không triển khai trong phiên bản đồ án.

### 0.2. Nguyên tắc trung thực

- Regex kiểm tra chuỗi biển số **không được gọi là LPR**.
- Dữ liệu giả lập phải được ghi rõ là synthetic data.
- Chức năng chưa hoàn chỉnh không được mô tả là “đã triển khai”.
- Không dùng giao diện để giả lập nghiệp vụ chưa tồn tại ở backend.
- Không tuyên bố hệ thống “AI-powered” nếu chưa có mô hình, dịch vụ hoặc pipeline có thể kiểm thử.

---

# 1. BỐI CẢNH VÀ MỤC TIÊU

AutoWash Pro là hệ thống web quản lý dịch vụ chăm sóc phương tiện cho xe máy, ô tô con, xe tải và xe khách, hỗ trợ:

- Khách hàng đăng ký tài khoản gắn với số điện thoại và phương tiện.
- Đặt lịch trước theo giới hạn của hạng thành viên.
- Quản lý lịch rửa, lịch sử sử dụng dịch vụ và trạng thái phục vụ.
- Theo dõi điểm, chi tiêu, số lần sử dụng dịch vụ.
- Tự động nâng hoặc hạ hạng theo kỳ đánh giá tháng.
- Đổi điểm thành giảm giá, lượt rửa miễn phí hoặc dịch vụ bổ sung.
- Hết hạn điểm sau 12 tháng.
- Cấu hình tier, point rate, perk và promotion.
- Thu thập log phù hợp để phục vụ phần nghiên cứu RBL.
- Tích hợp nhận diện biển số ở mức phù hợp với năng lực và thời gian của nhóm.

## 1.1. Mục tiêu đồ án

1. Xây dựng được một luồng nghiệp vụ hoàn chỉnh từ đăng ký → thêm xe → đặt lịch → hoàn thành dịch vụ → cộng điểm → xét hạng → đổi thưởng.
2. Tất cả giá tiền, điểm, quyền lợi và điều kiện đặt lịch phải được tính ở backend.
3. Các quy tắc nghiệp vụ đặc trưng phải có automated test.
4. Hệ thống có dữ liệu demo ổn định để bảo vệ.
5. Dữ liệu hành vi có thể xuất ra phục vụ phân tích nghiên cứu.
6. Nhóm có thể giải thích được kiến trúc, transaction, security và các giới hạn.

## 1.2. Tiêu chí thành công

Hệ thống được xem là hoàn thiện trong phạm vi nộp đồ án khi:

- Có thể chạy từ môi trường sạch bằng hướng dẫn trong README.
- Khách hàng hoàn thành được toàn bộ customer journey.
- Admin vận hành được booking, loyalty, tier và promotion.
- Không có lỗi nghiêm trọng trong các test case MUST.
- Không cộng điểm hai lần cho cùng một booking.
- Không cho đặt vượt booking window hoặc slot đã đầy.
- Không cho customer truy cập chức năng admin.
- Redeem không làm giảm chỉ số xét hạng.
- Monthly review chạy lại không gây xét hạng/reset lặp.
- Điểm hết hạn được truy vết theo từng giao dịch.
- Có ít nhất một file xuất dữ liệu nghiên cứu dạng CSV.

---

# 2. PHẠM VI

## 2.1. MUST — Phạm vi bắt buộc

- Authentication bằng số điện thoại và mật khẩu (`AUTH-01`, `AUTH-02`, `AUTH-04`).
- Phân quyền `customer`, `admin` (`AUTH-03`, `NFR-15`).
- Quản lý bốn nhóm phương tiện cấu hình được và chuẩn hóa biển số (`VEH-01..04`, `LPR-01`).
- Danh mục dịch vụ và giá/thời lượng theo loại phương tiện (`CAT-01..02`, `ADM-02`).
- Quản lý khung giờ và sức chứa (`SLOT-01..02`, `ADM-03`).
- Booking theo hạng thành viên, nhiều dịch vụ và giữ capacity xuyên các slot chồng lấn (`BKG-01..03`, `BKG-07`).
- Quản lý trạng thái booking và lịch sử rửa xe (`BKG-04..06`, `REP-01`).
- Loyalty ledger: earn, redeem, expire, adjust (`LOY-01..04`, `ADM-06`).
- Point expiry sau 12 tháng (`LOY-04`).
- Tier Member, Silver, Gold, Platinum và monthly review (`TIER-01..04`, `ADM-01`).
- Reward redemption (`RWD-01..04`, `ADM-04`).
- Promotion nhắm theo tier, dịch vụ hoặc loại phương tiện và auto-apply hợp lệ khi checkout (`PRO-01..05`, `ADM-05`).
- Dashboard cơ bản cho customer và admin (`REP-01..02`).
- Research event log và export CSV (`RBL-01..04`, `NFR-24`).
- LPR provider adapter an toàn với mock/external boundary và manual fallback (`LPR-02`, `NFR-16`).
- Seed data và automated test cho nghiệp vụ trọng yếu (`NFR-07`, `NFR-08`, `NFR-25`).

## 2.2. SHOULD — Nên triển khai

- Email thông báo bằng adapter, có chế độ log mail khi demo.
- Audit log cho thao tác admin quan trọng.
- CLI command cho expire points và monthly review.
- Báo cáo doanh thu, booking, tier distribution và reward usage.
- Docker Compose cho PHP/Apache và MySQL.
- CSRF, rate limit đăng nhập mức đơn giản, session hardening.
- Tài liệu API/route và sơ đồ ERD.

## 2.3. COULD — Mở rộng

- AI personalization/recommendation.
- Biểu đồ dự đoán tier progression.
- Hàng chờ vận hành trực tiếp tại cửa hàng.
- Notification real-time.
- Multi-branch.
- Tích hợp SMS thật.
- Mô hình LPR do nhóm tự huấn luyện.

## 2.4. OUT — Ngoài phạm vi

Theo yêu cầu đề tài, nhóm **không triển khai**:

- Thanh toán trực tuyến.
- Quản lý hoàn tiền.
- Ví điện tử hoặc cổng thanh toán.
- Hệ thống kế toán.
- Quản lý kho hóa chất/phụ tùng đầy đủ.
- Microservices, Kafka, Kubernetes hoặc hạ tầng phân tán.
- Mobile app native.
- Facial recognition.

---

# 3. ACTOR VÀ QUYỀN

## 3.1. Guest

- Xem trang giới thiệu, dịch vụ và bảng quyền lợi tier.
- Đăng ký.
- Đăng nhập.

## 3.2. Customer

- Quản lý hồ sơ cá nhân.
- Thêm, sửa, xóa phương tiện nếu không vi phạm ràng buộc lịch sử.
- Xem dịch vụ và slot khả dụng.
- Tạo hoặc hủy booking theo quy tắc.
- Xem booking/wash history.
- Xem tier hiện tại, điểm khả dụng, điểm sắp hết hạn và lịch sử điểm.
- Đổi reward.
- Nhận và sử dụng promotion/perk hợp lệ.
- Upload ảnh biển số nếu module LPR được bật.

## 3.3. Admin

- Tất cả hành động admin phải qua middleware/guard.
- Quản lý dịch vụ, giá và trạng thái hoạt động.
- Quản lý slot/capacity/ngày nghỉ.
- Xác nhận, hoàn thành hoặc hủy booking.
- Cấu hình tier rule, point rate và perk.
- Quản lý reward.
- Tạo promotion và chọn tier mục tiêu.
- Chạy hoặc xem kết quả monthly review.
- Chạy point expiry.
- Xem báo cáo và xuất dữ liệu nghiên cứu.
- Điều chỉnh điểm bằng `adjust_credit` hoặc `adjust_debit`, bắt buộc có lý do và audit log.

---

# 4. THUẬT NGỮ

- **Booking window:** Số ngày tối đa khách được đặt trước tính từ ngày hiện tại.
- **Priority access:** Quyền tiếp cận khung giờ sớm hơn dựa trên tier; không phải cơ chế thay đổi booking đã được xác nhận.
- **Point balance:** Tổng điểm còn khả dụng.
- **Loyalty ledger:** Sổ giao dịch điểm, là nguồn truy vết chính.
- **Tier metrics:** Tổng chi tiêu và số lượt hoàn thành trong kỳ tháng; không bị giảm khi redeem.
- **Reward:** Vật phẩm/quyền lợi đổi bằng điểm.
- **Perk:** Quyền lợi tự động gắn với tier.
- **Promotion:** Chương trình khuyến mãi có thời gian, điều kiện và tier mục tiêu.
- **Capacity unit:** Đơn vị sức chứa cấu hình theo loại phương tiện hoặc override theo cặp dịch vụ–loại phương tiện.
- **Completed booking:** Booking đã phục vụ xong; là thời điểm ghi nhận doanh thu, visit và điểm.
- **LPR:** Nhận diện ký tự biển số từ ảnh; khác với regex validation.
- **Synthetic data:** Dữ liệu mô phỏng có kiểm soát dùng cho nghiên cứu.

---

# 5. QUYẾT ĐỊNH CÔNG NGHỆ VÀ KIẾN TRÚC

## 5.1. Công nghệ

- **PHP:** PHP 8.2 trở lên.
- **Loại dự án:** Modern PHP thuần, không dùng application framework như Laravel, Symfony, Yii.
- **Dependency manager:** Composer.
- **Autoload:** PSR-4 qua Composer.
- **Database:** MySQL 8.
- **Database access:** PDO, prepared statement bắt buộc.
- **Web server:** Apache hoặc PHP built-in server trong môi trường dev.
- **Frontend:** HTML5, CSS3, JavaScript thuần; có thể dùng Bootstrap nếu nhóm thống nhất.
- **Testing:** PHPUnit hoặc công cụ tương đương phù hợp PHP thuần.
- **Environment:** Dotenv + `.env`; không commit secret thật.
- **Container:** Docker Compose là SHOULD.
- **Timezone:** `Asia/Ho_Chi_Minh` ở PHP và MySQL session.

Composer được phép dùng cho autoload, Dotenv, testing, email nếu thật sự cần và thư viện hỗ trợ nhỏ. Không dùng framework để ẩn toàn bộ kiến trúc; không tự xây lại thư viện tiêu chuẩn nếu không phục vụ trực tiếp mục tiêu học tập hoặc yêu cầu chấm điểm.

## 5.2. Kiến trúc

Áp dụng:

- Front Controller.
- Router.
- Controller.
- Middleware/Guard.
- Service.
- Repository.
- View.
- DTO/Request object ở nơi cần thiết.
- Domain exception.
- CLI command cho tác vụ định kỳ.

### Trách nhiệm lớp

**Controller**
- Nhận request.
- Gọi validation/request mapper.
- Gọi Service.
- Chọn response/view/redirect.
- Không chứa SQL.
- Không chứa công thức điểm hoặc điều kiện tier.

**Service**
- Chứa business logic và transaction boundary.
- Phối hợp nhiều repository.
- Ném domain exception có ý nghĩa.

**Repository**
- Chứa SQL và ánh xạ dữ liệu.
- Không quyết định nghiệp vụ.
- Không đọc trực tiếp `$_SESSION`, `$_POST`.

**View**
- Chỉ hiển thị dữ liệu đã chuẩn bị.
- Escape output mặc định.
- Không SQL, không cập nhật database.

**Middleware**
- Authentication.
- Role authorization.
- CSRF cho request thay đổi dữ liệu.

## 5.3. Cấu trúc thư mục

```text
auto_wash_pro/
├── app/
│   ├── Controllers/
│   ├── Core/
│   │   ├── Database.php
│   │   ├── Router.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── View.php
│   │   └── Session.php
│   ├── DTO/
│   ├── Exceptions/
│   ├── Middleware/
│   ├── Repositories/
│   ├── Services/
│   ├── Support/
│   └── Validation/
├── bootstrap/
│   └── app.php
├── config/
│   ├── app.php
│   ├── database.php
│   └── loyalty.php
├── database/
│   ├── migrations/
│   ├── seeds/
│   └── schema.sql
├── docs/
│   ├── PROJECT_SPECIFICATION.md
│   ├── REQUIREMENT_TRACEABILITY.md
│   ├── ERD.md
│   ├── TEST_PLAN.md
│   ├── DESIGN_SYSTEM.md
│   ├── DEMO_SCRIPT.md
│   ├── ASSUMPTIONS.md
│   ├── ADR/
│   └── CHANGELOG_REQUIREMENTS.md
├── public/
│   ├── index.php
│   ├── .htaccess
│   └── assets/
├── resources/
│   └── views/
│       ├── layouts/
│       ├── auth/
│       ├── customer/
│       └── admin/
├── routes/
│   ├── web.php
│   └── cli.php
├── scripts/
│   ├── monthly-review.php
│   ├── expire-points.php
│   └── export-research-data.php
├── storage/
│   ├── logs/
│   └── uploads/
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Feature/
├── .env.example
├── .gitignore
├── composer.json
├── docker-compose.yml
├── phpunit.xml
└── README.md
```

## 5.4. Database connection

`Database.php` có thể dùng lazy Singleton để thống nhất một PDO instance trong một request. Không mô tả Singleton là giải pháp chịu tải; mục tiêu là quản lý cấu hình kết nối tập trung.

PDO bắt buộc:

- `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`
- `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`
- `PDO::ATTR_EMULATE_PREPARES => false`
- Charset `utf8mb4`.

---

# 6. MÔ HÌNH DỮ LIỆU

Tất cả bảng dùng InnoDB, `utf8mb4`, khóa chính số nguyên, `created_at`, `updated_at` khi phù hợp.

## 6.1. `tiers`

- `id`
- `code` unique: `MEMBER`, `SILVER`, `GOLD`, `PLATINUM`
- `name`
- `rank_order` unique
- `booking_window_days`
- `min_monthly_spend`
- `min_monthly_visits`
- `point_rate` decimal
- `is_active`
- timestamps

Quy tắc seed:

| Tier | Spending tối thiểu/tháng | Completed visits tối thiểu/tháng | Booking window | Point rate |
|---|---:|---:|---:|---:|
| Member | 0 VND | 0 | 7 ngày | 1.00 |
| Silver | 300.000 VND | 2 | 10 ngày | 1.10 |
| Gold | 800.000 VND | 5 | 12 ngày | 1.25 |
| Platinum | 1.500.000 VND | 8 | 14 ngày | 1.50 |

Ngưỡng spend/visits và point rate là dữ liệu cấu hình, không hard-code trong Service.

## 6.2. `users`

- `id`
- `phone` unique
- `full_name`
- `email` nullable
- `password_hash`
- `role`: customer/admin
- `current_tier_id`
- `monthly_spend`
- `monthly_visits`
- `point_balance` — số dư cache để đọc nhanh
- `status`
- `last_login_at`
- timestamps

`point_balance` phải luôn khớp với ledger. Mọi thay đổi điểm phải qua LoyaltyService.

## 6.3. `vehicle_types`

- `id`
- `code` unique: `motorbike`, `car`, `truck`, `bus`
- `display_name`
- `default_duration_minutes`
- `default_capacity_units`
- `is_active`
- timestamps

Seed ban đầu:

| Code | Tên | Thời lượng mặc định | Capacity units |
|---|---|---:|---:|
| motorbike | Xe máy | 20 phút | 1 |
| car | Ô tô con | 40 phút | 2 |
| truck | Xe tải | 90 phút | 4 |
| bus | Xe khách | 120 phút | 5 |

Không dùng ENUM cố định cho loại phương tiện. Không xóa cứng loại đang được tham chiếu; chuyển inactive. Chưa phân loại chi tiết tải trọng, kích thước, container, số chỗ hoặc limousine trong phạm vi nộp đồ án.

## 6.4. `vehicles`

- `id`
- `user_id`
- `vehicle_type_id`
- `normalized_plate` unique
- `display_plate`
- `brand` nullable
- `model` nullable
- `notes` nullable
- `is_active`
- timestamps

Không xóa cứng xe đã có booking history; chuyển `is_active = false`. `normalized_plate` được uppercase và loại bỏ khoảng trắng, dấu gạch ngang, dấu chấm trước khi kiểm tra unique; `display_plate` lưu dạng trình bày đã xác nhận hoặc được dựng lại để hiển thị.

## 6.5. `services`

- `id`
- `code` unique
- `name`
- `description`
- `is_active`
- timestamps

## 6.6. `service_vehicle_prices`

- `id`
- `service_id`
- `vehicle_type_id`
- `price` nullable khi không hỗ trợ
- `duration_minutes` nullable khi không hỗ trợ
- `capacity_units_override` nullable; nếu null dùng `vehicle_types.default_capacity_units`
- `is_supported`
- `is_active`
- timestamps
- unique `(service_id, vehicle_type_id)`

Khi `is_supported = true`, price và duration phải lớn hơn 0. Backend từ chối rõ ràng nếu loại phương tiện inactive, cặp giá không tồn tại/inactive hoặc dịch vụ không hỗ trợ loại đó.

## 6.7. `wash_slots`

- `id`
- `slot_date`
- `start_time`
- `end_time`
- `capacity_units`
- `status`: open/closed
- timestamps
- unique `(slot_date, start_time, end_time)`

`used_capacity_units` được tính từ tổng reservation của booking giữ chỗ (`pending`, `confirmed`) trên từng slot; không lưu counter có thể lệch. Điều kiện giữ chỗ cho mọi slot bị chồng lấn:

```text
used_capacity_units + requested_capacity_units <= slot_capacity_units
```

`cancelled` không chiếm capacity. Backend khóa các slot chồng lấn theo thứ tự xác định và tạo toàn bộ reservation trong cùng transaction; nếu một slot không đủ capacity thì rollback toàn bộ.

## 6.8. `bookings`

- `id`
- `booking_code` unique
- `user_id`
- `vehicle_id`
- `start_slot_id`
- `booking_duration_minutes`
- `booking_capacity_units`
- `status`: pending/confirmed/completed/cancelled/no_show
- `subtotal`
- `perk_discount`
- `promotion_discount`
- `reward_discount`
- `final_price`
- `promotion_id` nullable
- `completed_at` nullable
- `cancelled_at` nullable
- `cancellation_reason` nullable
- `loyalty_processed_at` nullable
- timestamps

Ràng buộc nghiệp vụ ngăn một vehicle có nhiều booking active với khoảng thời gian chồng lấn.

`booking_duration_minutes` bằng tổng `duration_minutes` của mọi dịch vụ đã chọn. `booking_capacity_units` bằng giá trị lớn nhất giữa `vehicle_types.default_capacity_units` và `capacity_units` áp dụng của từng cặp service–vehicle type; không cộng capacity units giữa các dịch vụ. Cả hai được backend tính từ cấu hình/snapshot, không nhận từ frontend làm nguồn tin cậy.

## 6.8.1. `booking_slot_reservations`

- `id`
- `booking_id`
- `wash_slot_id`
- `capacity_units_reserved`
- timestamps
- unique `(booking_id, wash_slot_id)`

Mỗi booking giữ cùng `booking_capacity_units` trên mọi slot có khoảng thời gian chồng lấn với `[booking_start, booking_start + booking_duration_minutes)`. Reservation được tạo atomically cùng booking; cancellation giải phóng capacity bằng trạng thái booking nhưng có thể giữ row snapshot để audit. Request thất bại không được để reservation mồ côi.

## 6.9. `booking_items`

- `id`
- `booking_id`
- `service_id`
- `service_vehicle_price_id`
- `service_name_snapshot`
- `vehicle_type_code_snapshot`
- `unit_price_snapshot`
- `duration_minutes_snapshot`
- `capacity_units_snapshot`
- `quantity`
- `line_total`

Dùng snapshot để lịch sử không đổi khi admin cập nhật giá dịch vụ.

## 6.10. `loyalty_transactions`

Đây là nguồn truy vết điểm.

- `id`
- `user_id`
- `type`: `earn`/`adjust_credit`/`redeem`/`expire`/`adjust_debit`
- `points_delta`: số dương cho credit, số âm cho debit; không tạo credit transaction 0 điểm
- `remaining_points`: bắt buộc cho credit lot (`earn`, `adjust_credit`), null cho debit
- `source_type`
- `source_id`
- `source_transaction_id` nullable — self-FK khi adjustment sửa một giao dịch trước đó
- `description`
- `earned_at` nullable
- `expires_at` nullable
- `created_by` nullable
- timestamps
- unique phù hợp để chống xử lý lặp, ví dụ `(type, source_type, source_id)`

`adjust_credit` mặc định không hết hạn; `adjust_debit` phải phân bổ FEFO giống redeem. `reversal` chưa được
thêm vì hiện không có yêu cầu hoàn tác booking đã completed; nếu phát sinh phải có decision riêng.

## 6.11. `loyalty_allocations`

- `id`
- `debit_transaction_id` — FK tới transaction `redeem`/`expire`/`adjust_debit`
- `credit_transaction_id` — FK tới credit lot `earn`/`adjust_credit`
- `allocated_points`
- `allocated_at`
- unique `(debit_transaction_id, credit_transaction_id)`

Allocation truy vết FEFO cho mọi debit. Tổng allocation của debit phải bằng trị tuyệt đối `points_delta`;
tổng allocation khỏi credit lot không vượt credit ban đầu. Đây là thay đổi được nhóm phê duyệt để giải quyết
blocker Slice 10, không phải mở rộng tính năng ngoài roadmap.

## 6.12. `rewards`

- `id`
- `code` unique
- `name`
- `reward_type`: fixed_discount/percentage_discount/free_service/add_on
- `points_cost`
- `value`
- `max_discount` nullable — chỉ dùng cho reward giảm phần trăm
- `service_id` nullable
- `minimum_tier_id` nullable
- `valid_days_after_redeem`
- `is_active`
- timestamps

Seed đề xuất:

| Reward | Points cost |
|---|---:|
| Giảm 10.000 VND | 100 |
| Giảm 30.000 VND | 250 |
| Miễn phí rửa tiêu chuẩn xe máy | 300 |
| Tặng dịch vụ bổ sung | 400 |
| Giảm 100.000 VND | 700 |

## 6.13. `reward_vehicle_types`

- `reward_id`
- `vehicle_type_id`
- composite primary key

Bảng rỗng nghĩa là reward không giới hạn theo loại phương tiện; nếu có dòng thì backend chỉ cho các loại được liên kết.

## 6.14. `reward_redemptions`

- `id`
- `user_id`
- `reward_id`
- `booking_id` nullable
- `points_spent`
- `status`: available/used/expired/cancelled
- `redeemed_at`
- `expires_at`
- `used_at` nullable
- timestamps

Redeem và trừ điểm phải nằm trong cùng transaction.

## 6.15. `tier_perks`

- `id`
- `tier_id`
- `perk_type`: percentage_discount/fixed_discount/free_add_on
- `value`
- `service_id` nullable
- `is_active`
- timestamps

Phạm vi nộp đồ án chỉ áp dụng tối đa một perk tự động có lợi nhất cho booking.

## 6.16. `promotions`

- `id`
- `code` unique
- `name`
- `description`
- `discount_type`: percentage/fixed
- `discount_value`
- `max_discount` nullable
- `minimum_order_value`
- `start_at`
- `end_at`
- `usage_limit` nullable
- `per_user_limit` nullable
- `is_active`
- timestamps

## 6.17. `promotion_tiers`

- `promotion_id`
- `tier_id`
- composite primary key

Promotion “Silver+” được biểu diễn bằng các tier Silver, Gold, Platinum. Không dựa vào tên chuỗi để so sánh.

## 6.18. `promotion_services`

- `promotion_id`
- `service_id`
- composite primary key

## 6.19. `promotion_vehicle_types`

- `promotion_id`
- `vehicle_type_id`
- composite primary key

Danh sách liên kết rỗng nghĩa là không giới hạn theo dimension tương ứng. Eligibility luôn kiểm tra ở backend.

## 6.20. `promotion_usages`

- `id`
- `promotion_id`
- `user_id`
- `booking_id`
- `discount_amount`
- `used_at`
- unique `(promotion_id, booking_id)`

## 6.21. `tier_histories`

- `id`
- `user_id`
- `old_tier_id`
- `new_tier_id`
- `review_period`
- `monthly_spend_snapshot`
- `monthly_visits_snapshot`
- `reason`
- `created_at`
- unique `(user_id, review_period)`

## 6.22. `monthly_review_runs`

- `id`
- `review_period` unique, dạng `YYYY-MM`
- `status`: running/completed/failed
- `started_at`
- `completed_at`
- `processed_users`
- `error_message` nullable

Bảng này bảo đảm idempotency.

## 6.23. `research_event_logs`

- `id`
- `anonymous_user_key`
- `event_type`
- `event_time`
- `tier_code`
- `vehicle_type_code` nullable
- `service_code` nullable
- `booking_lead_days` nullable
- `order_value` nullable
- `points_earned` nullable
- `points_redeemed` nullable
- `used_reward` boolean
- `used_promotion` boolean
- `cancellation_status` nullable
- `tier_before_code` nullable
- `tier_after_code` nullable
- `data_source`: synthetic/survey/system
- `metadata_json` nullable

Không xuất full name, phone, email hoặc biển số vào dataset nghiên cứu.

## 6.24. `lpr_attempts` — MUST cho Slice 13

- `id`
- `user_id` nullable
- `image_path`
- `provider`
- `recognized_text`
- `normalized_text`
- `confidence` nullable
- `status`: success/failed/manual_override
- timestamps

---

# 7. YÊU CẦU CHỨC NĂNG VÀ ACCEPTANCE CRITERIA

## 7.1. Authentication — AUTH (MUST)

### AUTH-01 Đăng ký

- Phone hợp lệ và unique.
- Password tối thiểu 8 ký tự.
- Hash bằng `password_hash(..., PASSWORD_BCRYPT)`.
- Không lưu mật khẩu thuần.
- Role từ form luôn bị bỏ qua; tài khoản tự đăng ký là customer.

### AUTH-02 Đăng nhập

- Dùng `password_verify`.
- Regenerate session ID sau đăng nhập.
- Thông báo lỗi không tiết lộ riêng tài khoản hay mật khẩu sai.
- Tài khoản disabled không đăng nhập được.

### AUTH-03 Phân quyền

- Customer truy cập route admin nhận 403 hoặc redirect an toàn.
- Không chỉ ẩn nút trên UI; backend guard bắt buộc.

### AUTH-04 Logout

- Hủy session và cookie phiên phù hợp.
- POST + CSRF được ưu tiên.

## 7.2. Vehicle — VEH (MUST)

### VEH-01 Chuẩn hóa biển số

- Hỗ trợ biển số dân sự Việt Nam thông dụng cho motorbike, car, truck và bus.
- Uppercase, loại bỏ mọi khoảng trắng, dấu chấm và dấu gạch ngang để tạo `normalized_plate`.
- Pattern tổng quát sau chuẩn hóa: hai chữ số mã địa phương, một hoặc hai chữ cái, bốn hoặc năm chữ số.
- Lưu hoặc dựng lại `display_plate`; uniqueness kiểm tra trên `normalized_plate`.
- Validator nằm trong component/service dùng chung, có thể mở rộng tập trung; không rải regex ở controller/view.
- Backend validation là nguồn quyết định.
- Regex frontend chỉ hỗ trợ trải nghiệm.
- Ngoài phạm vi: biển quân đội, ngoại giao, nước ngoài, xe chuyên dùng đặc biệt, biển tạm và định dạng hiếm chưa có trong test dataset; trả lỗi rõ ràng hoặc chuyển manual review nếu domain triển khai.

### VEH-02 Trùng biển số

- Duplicate trả lỗi nghiệp vụ dễ hiểu.
- Bắt unique constraint exception; không làm ứng dụng sập.
- Không được “chiếm” biển số đã thuộc customer khác.

### VEH-03 Quyền sở hữu

- Customer chỉ xem/sửa/đặt bằng vehicle của mình.
- Request sửa `vehicle_id` thủ công phải bị từ chối.

### VEH-04 Loại phương tiện cấu hình được

- Hỗ trợ seed `motorbike`, `car`, `truck`, `bus` qua bảng `vehicle_types`, không dùng ENUM.
- Customer không tạo xe bằng loại không tồn tại hoặc inactive.
- Không xóa vật lý loại đang được vehicle, price hoặc lịch sử booking tham chiếu.

## 7.3. Service và Slot — CAT/SLOT (MUST)

### CAT-01 Danh mục dịch vụ

- Chỉ dịch vụ và cặp giá active, supported, đúng vehicle type được đặt.
- Giá booking lấy từ database ở thời điểm checkout.
- Client không được quyết định giá.

### CAT-02 Giá và thời lượng theo loại phương tiện

- Giá, thời lượng và capacity override được tải từ cặp `service_id + vehicle_type_id`.
- Mỗi cặp chỉ có một cấu hình hiện hành; price/duration phải lớn hơn 0 khi supported.
- Thiếu cặp giá hoặc `is_supported = false` trả lỗi nghiệp vụ rõ ràng ở backend.
- Thay đổi cấu hình không làm thay đổi booking item snapshot cũ.

### SLOT-01 Khả dụng

- Slot closed hoặc ngày quá khứ không đặt được.
- Slot không đủ capacity units không đặt được.
- `capacity_units` phải lớn hơn 0.
- Backend tính `booking_capacity_units = max(vehicle default, từng service override)`; không cộng units và không tin client.
- Booking kéo dài qua nhiều slot chỉ hợp lệ khi mọi slot chồng lấn đều đủ cùng mức capacity.

### SLOT-02 Tranh chấp slot

Khi hai request cùng lấy phần capacity cuối:

- Kiểm tra/lock mọi slot chồng lấn và insert booking/reservations trong transaction.
- Dùng locking phù hợp (`SELECT ... FOR UPDATE`) hoặc cơ chế tương đương.
- Chỉ một request thành công.
- Tổng capacity giữ chỗ không vượt `wash_slots.capacity_units`.
- Request còn lại nhận lỗi “slot vừa hết chỗ”.
- Một slot ở giữa không đủ capacity làm cả booking thất bại; rollback không để reservation rác.

## 7.4. Booking — BKG (MUST)

### BKG-01 Booking window theo tier

```text
lead_days = booking_date - current_date
```

- `lead_days < 0`: từ chối.
- `lead_days > booking_window_days`: từ chối.
- Bằng đúng giới hạn: cho phép.
- Dùng ngày ở timezone Asia/Ho_Chi_Minh.

### BKG-02 Priority access

Phạm vi nộp đồ án định nghĩa priority access bằng booking window:

- Member mở trước tối đa 7 ngày.
- Silver 10 ngày.
- Gold 12 ngày.
- Platinum 14 ngày.

Không hủy hoặc chen ngang booking hợp lệ của hạng thấp. Priority access là quyền tiếp cận khung giờ sớm hơn dựa trên tier, không phải runtime priority queue.

### BKG-03 Tính giá

```text
subtotal = tổng booking_items
final_price = max(0, subtotal - perk_discount - promotion_discount - reward_discount)
```

- Tất cả tính ở backend.
- Không discount stacking tùy ý.
- Phạm vi nộp đồ án: chọn một perk tốt nhất, một promotion tốt nhất và tối đa một reward.
- Log cách tính để giải thích khi demo.

### BKG-04 Vòng đời trạng thái

Chuyển trạng thái hợp lệ:

```text
pending   -> confirmed | cancelled
confirmed -> completed | cancelled | no_show
completed -> không đổi
cancelled -> không đổi
no_show   -> không đổi
```

Mọi chuyển trạng thái khác bị từ chối.

### BKG-05 Hủy booking

- Chỉ booking pending/confirmed mới hủy được.
- Customer chỉ hủy booking của mình.
- Customer được tự hủy khi thời điểm hủy cách `wash_slot.start_time` ít nhất 2 giờ; đúng mốc 2 giờ được phép.
- Dưới 2 giờ customer bị từ chối; admin có thể xử lý ngoại lệ và phải ghi lý do/audit phù hợp.
- Booking completed không hủy.
- Booking cancelled không hủy lại.
- Hủy giải phóng capacity units và không tạo loyalty earn.
- Nếu reward đã gắn nhưng booking chưa completed, chính sách hoàn reward phải nhất quán:
  - Phạm vi nộp đồ án: reward redemption trở lại `available` nếu còn hạn.
  - Điểm không cộng/trừ ngoài giao dịch đã có.
- Không phạt tiền hoặc tự động trừ điểm khi hủy.

### BKG-06 Hoàn thành booking

Trong một DB transaction:

1. Lock booking.
2. Xác nhận booking chưa completed.
3. Cập nhật trạng thái completed.
4. Ghi `completed_at`.
5. Tăng `monthly_spend`.
6. Tăng `monthly_visits` đúng 1.
7. Tính và ghi earn points.
8. Đánh dấu `loyalty_processed_at`.
9. Ghi promotion usage/reward used nếu có.
10. Ghi research event.

Gửi lại request không được cộng lại điểm hoặc visits.

### BKG-07 Booking nhiều dịch vụ và nhiều slot

```text
booking_duration_minutes = sum(duration_minutes của mọi service–vehicle type đã chọn)
booking_capacity_units = max(vehicle_type.default_capacity_units, mọi service capacity override áp dụng)
```

- Không cộng capacity units của nhiều dịch vụ.
- Backend tải duration/capacity từ cấu hình; bỏ qua giá trị client gửi.
- Khoảng booking giữ `booking_capacity_units` trên toàn bộ wash slot chồng lấn.
- Kiểm tra và tạo reservation trong một transaction chống race condition; thiếu capacity ở bất kỳ slot nào thì rollback toàn bộ.

## 7.5. Loyalty — LOY (MUST)

### LOY-01 Công thức cộng điểm

Cấu hình:

- `point_unit_amount`, seed `10.000 VND`.
- `tier.point_rate` decimal theo tier.

```text
base_points = floor(final_price / 10.000)
earned_points = floor(base_points * tier_point_rate)
```

- Tính theo tier tại thời điểm booking completed.
- `final_price = 0` có thể nhận 0 điểm; booking vẫn được đánh dấu đã xử lý loyalty nhưng không tạo credit lot 0 điểm.
- Chỉ completed booking được cộng.
- Ví dụ `250.000 VND` ở rate `1.25`: base 25, earn 31.

### LOY-02 Sổ giao dịch

- Không cập nhật `point_balance` trực tiếp từ Controller.
- Mọi thay đổi qua LoyaltyService.
- Update balance và insert ledger trong cùng transaction.
- Balance không âm.
- `point_balance` chỉ là cache/tổng hợp; ledger và allocations là nguồn lịch sử.
- Sau mọi mutation: `point_balance = ledger net = tổng remaining_points của credit lot`.
- Mọi debit phải có allocation đầy đủ; không được chỉ giảm cache.

### LOY-03 Redeem

- Chỉ trừ point balance.
- Không thay đổi monthly spend hoặc monthly visits.
- Dùng credit lot gần hết hạn trước; lot không hết hạn dùng sau cùng theo FIFO.
- Mỗi phần điểm debit ghi allocation tới credit lot nguồn.
- Không đủ điểm thì toàn bộ transaction rollback.
- Reward tier requirement phải được kiểm tra ở backend.

### LOY-04 Point expiry

- Mỗi lô earn hết hạn sau đúng 12 tháng lịch: `expires_at = earned_at + 12 calendar months`, không dùng 365 ngày.
- Dùng calendar-month clamp: nếu ngày tương ứng không có trong tháng đích, dùng ngày hợp lệ cuối cùng; ví dụ `29/02/2024 -> 28/02/2025`.
- Điểm được xem là hết hạn khi `current_time >= expires_at`; toàn bộ tính toán dùng timezone hệ thống `Asia/Ho_Chi_Minh`.
- CLI command `loyalty:expire-points` chạy hằng ngày.
- Chỉ expire credit lot `earn` có `remaining_points > 0`; `adjust_credit` mặc định không hết hạn.
- Tạo transaction `expire`.
- Ghi allocation từ expire transaction tới credit lot; không expire điểm đã redeem hoặc adjust debit đã dùng.
- Chạy lại an toàn, không transaction/allocation trùng và không làm balance âm.
- Hiển thị tổng điểm sẽ hết hạn trong 30 ngày tới.

## 7.6. Tier review — TIER (MUST)

### TIER-01 Kỳ xét

- Xét theo tháng lịch.
- Job của tháng mới xét dữ liệu tháng vừa kết thúc.
- `review_period` duy nhất.

### TIER-02 Điều kiện

Customer đạt một tier khi:

```text
monthly_spend >= tier.min_monthly_spend
AND
monthly_visits >= tier.min_monthly_visits
```

Chọn tier cao nhất thỏa cả hai điều kiện. Nếu không đạt tier nào khác, về Member.

Seed ngưỡng/rate: Member `0/0/1.00`, Silver `300.000/2/1.10`, Gold `800.000/5/1.25`, Platinum `1.500.000/8/1.50`. Tất cả lưu DB và admin cấu hình được, không hard-code trong Service.

### TIER-03 Upgrade/downgrade

- Có thể tăng hoặc giảm nhiều bậc trong một lần review.
- Ghi `tier_histories`.
- Reset `monthly_spend = 0`, `monthly_visits = 0` sau khi snapshot thành công.
- Điểm khả dụng không bị reset.

### TIER-04 Idempotency

- Nếu `monthly_review_runs.review_period` đã completed, job từ chối chạy lại trừ chế độ admin đặc biệt có audit.
- Nếu job lỗi giữa chừng, transaction theo từng user và trạng thái run phải giúp chạy tiếp an toàn.
- Mỗi user chỉ có một history cho một period.

## 7.7. Reward — RWD (MUST)

### RWD-01 Điều kiện hiển thị và đổi reward

- Reward phải active, chưa hết hạn và customer đạt minimum tier.
- Backend kiểm tra lại eligibility; không tin dữ liệu từ client.

### RWD-02 Đổi reward nguyên tử

- Redeem tạo `reward_redemptions` và trừ điểm trong cùng transaction.
- Không đủ điểm hoặc lỗi ở bất kỳ bước nào phải rollback toàn bộ.

### RWD-03 Áp dụng đúng loại reward

- Free service chỉ áp dụng đúng service được cấu hình.
- Reward giới hạn vehicle type chỉ áp dụng cho loại được liên kết; reward rửa xe máy không áp dụng cho bus.
- Percentage discount tuân theo max discount nếu admin cấu hình.

### RWD-04 Quyền sở hữu và sử dụng một lần

- Reward redemption dùng một lần.
- Customer không được xem hoặc dùng reward redemption của người khác.

## 7.8. Promotion và Perk — PRO (MUST)

### PRO-01 Target theo tier

- Promotion có danh sách tier được phép.
- Silver+ = Silver, Gold, Platinum.
- Kiểm tra theo tier ID/code ở backend.

### PRO-02 Hiệu lực

Promotion chỉ hợp lệ khi:

- Active.
- Trong thời gian.
- Đúng tier.
- Đủ minimum order.
- Chưa vượt total usage.
- Chưa vượt per-user usage.

### PRO-03 Auto apply

- Service tính tất cả promotion hợp lệ.
- Chọn promotion tạo discount lớn nhất.
- Nếu bằng nhau, chọn promotion có ngày kết thúc sớm hơn.
- Không cộng dồn nhiều promotion trong phạm vi nộp đồ án.

### PRO-04 Perk

- Perk theo tier được áp dụng tự động.
- Chọn một perk có lợi nhất.
- Lưu snapshot số tiền giảm vào booking.

### PRO-05 Phạm vi dịch vụ và loại phương tiện

- Promotion có thể giới hạn theo service và/hoặc vehicle type bằng cấu hình quan hệ.
- Backend từ chối promotion không đúng service/type; không chỉ ẩn lựa chọn ở frontend.
- Danh sách liên kết rỗng nghĩa là không giới hạn theo dimension đó.

## 7.9. Admin — ADM (MUST, trừ khi ghi rõ)

### ADM-01 Quản lý tier rule

- Admin có thể cấu hình booking window, ngưỡng spend/visits, point rate và perk.
- Không cho `rank_order` trùng; booking window, ngưỡng và point rate không âm.
- Tier đang được dùng không được xóa cứng; có thể chuyển inactive.

### ADM-02 Quản lý danh mục dịch vụ

- Admin có thể tạo, sửa và chuyển trạng thái active/inactive cho dịch vụ và cấu hình giá/thời lượng theo loại phương tiện.
- Không cho price/duration không hợp lệ hoặc hai cấu hình cho cùng cặp service–vehicle type.
- Thay đổi giá không làm thay đổi `booking_items` đã snapshot.

### ADM-03 Quản lý slot và capacity

- Admin có thể tạo/đóng slot và cấu hình `capacity_units > 0`.
- Từ chối slot trùng, `end_time <= start_time` hoặc slot ở ngày quá khứ.

### ADM-04 Quản lý reward

- Admin có thể tạo, sửa và chuyển reward active/inactive.
- Cấu hình points cost, loại reward, giá trị, tier/service/vehicle type liên quan phải hợp lệ.

### ADM-05 Quản lý promotion

- Admin có thể tạo, sửa và chuyển promotion active/inactive, cấu hình thời gian, điều kiện, giới hạn và target tier/service/vehicle type.
- Promotion Silver+ được lưu bằng quan hệ tới Silver, Gold và Platinum.

### ADM-06 Điều chỉnh điểm

- Admin điều chỉnh dương bằng `adjust_credit`, âm bằng `adjust_debit`; reason là bắt buộc.
- `adjust_credit` tạo credit lot không hết hạn mặc định; `adjust_debit` phân bổ FEFO vào credit lot.
- Adjustment âm chỉ hợp lệ khi `available_points + adjustment_points >= 0`; vượt số dư bị từ chối toàn bộ, không clamp về 0.
- Balance và ledger được cập nhật atomically trong transaction có locking, không để hai request đồng thời làm balance âm.
- Không cập nhật trực tiếp cached balance khi không có ledger entry/allocation phù hợp; ledger, credit lot và cache phải reconcile được.
- Adjustment sửa giao dịch trước có thể tham chiếu `source_transaction_id`.
- Thao tác phải có audit log chứa actor và lý do, không chứa secret.

### ADM-07 Bảo toàn lịch sử

- Không sửa lịch sử giá đã snapshot hoặc xóa cứng dữ liệu tài chính/loyalty quan trọng.

### ADM-08 Nhật ký cấu hình (SHOULD)

- Thao tác thay tier rule, giá dịch vụ, promotion và chạy lại monthly review đặc biệt có audit log hoặc application log.

## 7.10. Dashboard và report — REP (MUST)

### REP-01 Customer dashboard

- Hiển thị tier, point balance, điểm sắp hết hạn, booking gần nhất, wash history và reward khả dụng của đúng customer đang đăng nhập.
- Empty state được hiển thị khi chưa có booking, history hoặc reward.

### REP-02 Admin dashboard và báo cáo cơ bản

- Hiển thị booking hôm nay theo trạng thái, slot utilization, phân bố tier, điểm earn/redeem/expire và promotion usage.
- Doanh thu chỉ tính từ booking `completed` và dùng `final_price` đã lưu.
- Chỉ admin được truy cập dữ liệu tổng hợp vận hành.

## 7.11. LPR — LPR

### LPR-01 Baseline bắt buộc (MUST)

- Nhập biển số thủ công.
- Normalize + validate.
- Không gọi đây là LPR.

### LPR-02 Tích hợp nhận diện ảnh qua provider (MUST)

Thiết kế adapter:

```php
interface LprProviderInterface
{
    public function recognize(string $imagePath): RecognitionResult;
}
```

Có thể triển khai:

- `MockLprProvider` cho test/demo offline.
- `ExternalLprProvider` trong tương lai.

Luồng:

1. Upload ảnh an toàn.
2. Kiểm tra MIME và kích thước.
3. Gọi recognizer.
4. Hiển thị biển số dự đoán và confidence.
5. Người dùng xác nhận/sửa.
6. Normalize + validate ở backend.
7. Ghi lpr_attempt.

Hệ thống phải có manual fallback.

## 7.12. Research/RBL — RBL

### RBL-01 Câu hỏi nghiên cứu (MUST)

“What factors most influence customer loyalty tier progression in smart service ecosystems?”

### RBL-02 Dữ liệu cần thu thập (MUST)

- Thời gian booking.
- Booking lead days.
- Giá trị đơn hàng.
- Điểm earn/redeem.
- Monthly spend.
- Monthly visits.
- Reward usage.
- Vehicle type.
- Service type.
- Tier trước/sau.
- Promotion usage.
- Cancellation/no-show.
- Upgrade/downgrade.
- Tần suất quay lại.

- Event log/export phải có schema xác định và biểu diễn được các trường trên trực tiếp hoặc qua snapshot/metadata có data dictionary.

### RBL-03 Privacy (MUST)

Dataset export không chứa:

- Full name.
- Phone.
- Email.
- Password/hash.
- Biển số.
- IP thô.

Dùng anonymous key không thể suy ngược trực tiếp trong file export.

### RBL-04 Synthetic data (MUST)

- Script sinh synthetic data phải có seed để tái lập.
- Phân biệt cột `data_source = synthetic|survey|system`.
- Không trộn dữ liệu thật và giả mà không đánh dấu.
- Mục tiêu quy mô do kế hoạch nghiên cứu quyết định; hệ thống phải hỗ trợ export.
- Synthetic dataset sinh được tối thiểu 2.000 records và bao phủ bốn loại phương tiện.

### RBL-05 Survey, ML và paper (OPTIONAL/SHOULD, deliverable nghiên cứu riêng)

**Deferred bonus work — Non-blocking.** Checkpoint Q-020 trước Slice 14 xác nhận survey thật, ML model,
kiểm định chuyên sâu và paper/conference-format report không bắt buộc để hoàn thành sản phẩm chính hoặc release.
Các hạng mục này chỉ triển khai để lấy điểm cộng nếu còn thời gian.

Hệ thống vẫn phải hỗ trợ research event log, CSV ẩn danh, synthetic dataset, descriptive analytics/dashboard
và dữ liệu đủ làm evidence cho báo cáo. Không tự tạo kết quả survey, accuracy, p-value, hypothesis result,
kết luận nghiên cứu, nguồn dataset ngoài hoặc tuyên bố paper hoàn thành. Survey/ML không trực tiếp quyết định
tier và không thay rule-based loyalty engine.

---

# 8. BẢO MẬT

Các yêu cầu bắt buộc trong mục này được định danh và có acceptance criteria tại `NFR-11..20`.

## 8.1. Bắt buộc

- Prepared statement cho mọi query có input.
- Escape output HTML bằng helper thống nhất.
- CSRF token cho POST/PUT/PATCH/DELETE.
- Session cookie `HttpOnly`, `SameSite=Lax`; `Secure` khi HTTPS.
- Regenerate session ID sau login.
- Authorization ở backend.
- Validate file upload MIME, size, random filename; ngoài public root nếu có thể.
- Không commit `.env`.
- Không log password, session token hoặc secret.
- Giới hạn số lần đăng nhập đơn giản theo session/IP băm nếu triển khai.
- Generic error cho production, chi tiết trong log.

## 8.2. Validation

Frontend validation chỉ hỗ trợ UX. Backend luôn kiểm tra lại:

- Type.
- Required.
- Length.
- Format.
- Ownership.
- Status.
- Business rule.
- Database constraint.

## 8.3. Giá và điểm

Client không được gửi giá cuối cùng như nguồn tin cậy. Backend tải lại:

- Service price.
- Tier.
- Point rate.
- Reward.
- Promotion.
- Perk.

---

# 9. TRANSACTION, CONCURRENCY VÀ IDEMPOTENCY

Các yêu cầu bắt buộc trong mục này được định danh tại `NFR-21..23`.

Phải có transaction cho:

- Tạo booking và giữ slot.
- Hoàn thành booking và cộng loyalty.
- Redeem reward và trừ điểm.
- Expire một lô điểm.
- Monthly review một user.
- Apply promotion usage khi hoàn thành.

Idempotency key hoặc unique constraint cần cho:

- Loyalty earn từ booking.
- Promotion usage từ booking.
- Tier history theo user/period.
- Expire transaction theo earning lot.
- Reward usage.

Không thực hiện network call dài bên trong transaction DB. Email/notification được gửi sau commit hoặc ghi queue/log đơn giản.

---

# 10. XỬ LÝ LỖI VÀ THÔNG BÁO

Dùng domain exception, ví dụ:

- `AuthenticationException`
- `AuthorizationException`
- `ValidationException`
- `VehicleOwnershipException`
- `DuplicateLicensePlateException`
- `BookingWindowExceededException`
- `SlotFullException`
- `InvalidBookingTransitionException`
- `InsufficientPointsException`
- `RewardNotEligibleException`
- `PromotionNotEligibleException`
- `MonthlyReviewAlreadyCompletedException`

Thông báo cho người dùng phải dễ hiểu; log giữ thông tin kỹ thuật và correlation ID.

POST thành công dùng Post/Redirect/Get để tránh gửi lại form khi refresh.

---

# 11. TESTING

## 11.1. Unit test MUST

- Công thức earned points.
- Booking window boundary.
- Price calculator.
- Promotion eligibility.
- Best promotion selection.
- Tier qualification.
- Reward eligibility.
- Plate normalization.

## 11.2. Integration test MUST

- Duplicate plate.
- Slot capacity race ở mức có thể mô phỏng.
- Create booking transaction.
- Complete booking chỉ cộng một lần.
- Redeem FEFO.
- Expire points.
- Monthly review idempotency.
- Customer không truy cập admin.
- Customer không dùng vehicle/reward của người khác.

## 11.3. Feature/demo test MUST

- Customer journey hoàn chỉnh.
- Admin journey hoàn chỉnh.
- Hủy booking giải phóng slot.
- Silver+ promotion.
- Điểm sắp hết hạn.
- Upgrade và downgrade.
- Export research CSV.

## 11.4. Test data

Seed demo cố định:

- 1 admin.
- 4 customer tương ứng 4 tier.
- Customer có điểm sắp hết hạn.
- Customer đủ nâng tier.
- Customer sẽ hạ tier.
- Vehicle bao phủ motorbike, car, truck và bus.
- Giá/thời lượng dịch vụ theo bốn loại phương tiện, gồm cặp không hỗ trợ.
- Slot trống, gần đầy, vừa đủ, thiếu capacity unit và đóng.
- Reward đủ các loại.
- Promotion Silver+.
- Booking ở nhiều trạng thái.

---

# 12. DỮ LIỆU DEMO VÀ KỊCH BẢN BẢO VỆ

Cung cấp lệnh tương đương:

```bash
composer install
cp .env.example .env
docker compose up -d
php database/migrate.php
php database/seed.php --demo
vendor/bin/phpunit
```

README phải ghi tài khoản demo, nhưng chỉ dùng mật khẩu demo không phải secret thật.

`docs/DEMO_SCRIPT.md` phải có:

1. Reset dữ liệu.
2. Login customer Member.
3. Thử đặt vượt 7 ngày và nhận lỗi.
4. Login Silver/Gold và đặt được xa hơn.
5. Thử slot cuối bằng hai request.
6. Admin hoàn thành booking.
7. Customer thấy point tăng.
8. Redeem reward và chứng minh metrics xét hạng không giảm.
9. Chạy monthly review.
10. Chạy point expiry.
11. Tạo promotion Silver+.
12. Export CSV nghiên cứu.

---

# 13. LOGGING VÀ AUDIT

Application log:

- Request ID.
- Error.
- Login failure.
- Booking transition.
- Loyalty mutation.
- Monthly job.
- LPR failure.

Audit tối thiểu:

- Admin adjust point.
- Admin thay tier rule.
- Admin thay service price.
- Admin tạo/sửa promotion.
- Admin chạy lại monthly review đặc biệt.

Không log dữ liệu nhạy cảm.

---

# 14. NON-FUNCTIONAL REQUIREMENTS — NFR

Tất cả yêu cầu dưới đây là **MUST**, trừ khi ghi rõ.

### NFR-01 Responsive cơ bản

- Các màn hình chính dùng được ở desktop và mobile cơ bản, không che mất thao tác chính.

### NFR-02 Hiệu năng với dữ liệu demo

- Môi trường đồ án phải kiểm thử với tối thiểu 10.000 booking và 20 virtual concurrent users.
- P95 dưới 1 giây cho login, xem dịch vụ, xem slot và booking history.
- P95 dưới 2 giây cho tạo booking, redeem reward và admin report.
- Error rate dưới 1%; không tính latency external LPR provider.
- Đây là mục tiêu kiểm thử học thuật, không phải SLA thương mại; không tuyên bố tải lớn hơn khi chưa có evidence.

### NFR-03 Trạng thái giao diện

- Luồng chính có empty state và error state dễ hiểu.

### NFR-04 Chuẩn mã nguồn

- Code tuân PSR-12 và chạy được lệnh lint/format được nhóm chọn.

### NFR-05 Phân tầng

- Không có SQL trong Controller/View; không đặt business formula trong Controller; không copy-paste business logic giữa các Controller.

### NFR-06 Hoàn thiện luồng MUST

- Không còn TODO, placeholder hoặc code giả trong luồng MUST khi nộp.

### NFR-07 Truy vết

- Mỗi requirement MUST ánh xạ tới ít nhất một automated test hoặc demo step có evidence.

### NFR-08 Khả năng tái lập dữ liệu

- Có schema/migration, seed và cơ chế reset/backup/export phù hợp để dựng lại môi trường demo.

### NFR-09 Múi giờ

- PHP, MySQL session và thời gian hiển thị dùng `Asia/Ho_Chi_Minh`.

### NFR-10 Tiền tệ chính xác

- Tiền lưu bằng `DECIMAL`; không dùng float cho lưu trữ hoặc tính toán tiền.

### NFR-11 Truy cập dữ liệu an toàn

- Mọi query có input dùng PDO prepared statement thật (`ATTR_EMULATE_PREPARES=false`) và charset `utf8mb4`.

### NFR-12 Chống XSS

- Output HTML được escape mặc định bằng helper thống nhất; chỉ render raw với dữ liệu đã được kiểm soát rõ ràng.

### NFR-13 CSRF

- POST/PUT/PATCH/DELETE yêu cầu CSRF token hợp lệ.

### NFR-14 Session

- Cookie phiên dùng `HttpOnly`, `SameSite=Lax`, `Secure` khi HTTPS; session ID được regenerate sau login và logout hủy phiên phù hợp.

### NFR-15 Authorization và ownership

- Authentication, role và ownership được kiểm tra ở backend cho mọi route/resource bảo vệ.

### NFR-16 Upload an toàn

- Kiểm tra allowlist MIME, size, tên file ngẫu nhiên, ngăn thực thi và ưu tiên lưu ngoài public root.

### NFR-17 Secret và logging

- Không commit `.env`; không log password, session token, secret hoặc PII không cần thiết.

### NFR-18 Xử lý lỗi production

- Response production không lộ stack trace/secret; chi tiết kỹ thuật được ghi log kèm correlation/request ID.

### NFR-19 Validation backend

- Backend kiểm tra type, required, length, format, ownership, status, business rule và database constraint; frontend validation chỉ hỗ trợ UX.

### NFR-20 Chống giả mạo giá và điểm

- Backend tải lại service price, tier, point rate, reward, promotion và perk; không dùng giá/điểm cuối do client cung cấp làm nguồn tin cậy.

### NFR-21 Transaction

- Tạo booking, complete + loyalty, redeem reward, expire point lot, monthly review user và promotion usage dùng transaction boundary phù hợp.

### NFR-22 Concurrency và idempotency

- Unique constraint/locking ngăn loyalty earn, expiry, promotion/reward usage và tier history bị xử lý lặp; slot/redeem không vượt capacity hoặc âm balance khi cạnh tranh.

### NFR-23 Network call

- Không giữ DB transaction trong khi thực hiện network call dài; notification được gửi sau commit hoặc qua log/queue đơn giản.

### NFR-24 Privacy research

- CSV nghiên cứu không chứa PII nêu tại RBL-03 và phân biệt rõ nguồn dữ liệu.

### NFR-25 Khởi chạy từ môi trường sạch

- README cung cấp đủ bước cài dependency, cấu hình, migrate, seed, chạy test và chạy ứng dụng từ môi trường sạch.

### NFR-26 Nền tảng kỹ thuật

- Dùng PHP 8.2+, Modern PHP thuần không application framework, Composer PSR-4, MySQL 8, PDO và PHPUnit theo quyết định kiến trúc.

---

# 15. REQUIREMENT TRACEABILITY

Tạo `docs/REQUIREMENT_TRACEABILITY.md` với cột:

| Requirement ID | Mô tả | Nguồn | Priority | Module | Code | Test | Demo | Status |
|---|---|---|---|---|---|---|---|---|

Quy tắc:

- Không đánh dấu Done nếu chưa có test hoặc demo evidence.
- Mọi commit/PR nên ghi Requirement ID.
- Nếu thay đổi requirement, cập nhật changelog trước khi code.

---

# 16. DEFINITION OF DONE CHO MỖI SLICE

Một slice chỉ Done khi:

- Acceptance criteria đã được ghi.
- Migration/seed đã cập nhật nếu cần.
- Repository, Service, Controller và View đúng trách nhiệm.
- Backend validation đầy đủ.
- Authorization và CSRF được kiểm tra.
- Unit/integration test liên quan pass.
- Không phá test cũ.
- Có dữ liệu demo.
- README/docs cập nhật.
- Traceability matrix cập nhật.
- Agent liệt kê file thay đổi và lệnh test đã chạy.
- Không còn placeholder ở luồng chính.

---

# 17. QUY TẮC DÀNH CHO AI AGENT

AI Agent phải:

1. Đọc tài liệu này trước mọi thay đổi lớn.
2. Chỉ làm đúng slice được giao.
3. Trước khi code, nêu requirement ID, acceptance criteria và file dự kiến sửa.
4. Không thay schema âm thầm.
5. Không thêm framework.
6. Không đổi business rule vì “best practice” nếu chưa cập nhật đặc tả.
7. Không tin dữ liệu frontend.
8. Không hard-code tier, price, point rate, promotion.
9. Không tạo code giả hoặc TODO rồi báo hoàn thành.
10. Phải chạy test và đưa kết quả thật.
11. Nếu không chạy được test, nói rõ nguyên nhân; không tuyên bố pass.
12. Tự review diff trước khi kết thúc.
13. Cập nhật traceability sau mỗi slice.
14. Ưu tiên code dễ giải thích hơn mẫu kiến trúc quá phức tạp.
15. Không làm ngoài phạm vi slice.
16. Đọc `docs/DESIGN_SYSTEM.md` trước khi tạo hoặc sửa UI; không tự tạo token/component khác baseline.

---

# 18. CÁC QUYẾT ĐỊNH ĐÃ CHỐT

1. Hệ thống một chi nhánh trong phạm vi nộp đồ án.
2. Pure Modern PHP, MySQL, PDO, Composer PSR-4, Dotenv và PHPUnit; không application framework.
3. Hỗ trợ bốn loại phương tiện cấu hình: motorbike, car, truck, bus; không ENUM.
4. Giá/thời lượng/capacity phụ thuộc service + vehicle type.
5. Slot dùng capacity units và giữ chỗ trong transaction.
6. Priority access là tier booking window, không thay đổi booking đã xác nhận.
7. Tier qualification dùng spend **AND** completed visits với seed tại mục 6.1.
8. Booking completed mới ghi nhận spend, visits, points, usage và research event; xử lý idempotent.
9. Công thức điểm là `floor(floor(final_price / 10.000) × point_rate)`.
10. Mọi debit (`redeem`, `expire`, `adjust_debit`) dùng FEFO và generic `loyalty_allocations` vào credit lot.
11. Điểm hết hạn sau đúng 12 tháng lịch.
12. Customer chỉ tự hủy trước/đúng mốc 2 giờ; dưới 2 giờ cần admin ngoại lệ.
13. Một booking dùng tối đa một reward, một promotion và một perk.
14. Không thanh toán online/refund; không penalty tự động khi hủy.
15. LPR dùng adapter + mock/external provider và luôn có manual fallback.
16. Synthetic dataset tối thiểu 2.000 records; export ẩn danh và đánh dấu nguồn.
17. NFR performance dùng 10.000 bookings, 20 VU và ngưỡng P95/error tại NFR-02.
18. Database server là nguồn nhất quán; client không quyết định price, capacity, points hoặc discount.
19. Booking nhiều dịch vụ cộng tổng duration, lấy capacity lớn nhất (không cộng) và giữ capacity trên mọi slot chồng lấn.
20. Expiry dùng calendar-month clamp và boundary `current_time >= expires_at` trong timezone hệ thống.
21. Biển số baseline là biển dân sự Việt Nam thông dụng, chuẩn hóa/validate tập trung và unique theo `normalized_plate`.
22. Adjustment dương tạo non-expiring credit lot; adjustment âm vượt available points bị từ chối, không clamp
    và phải có FEFO allocation; mọi adjustment có reason, ledger, audit và transaction locking.
23. Survey/ML/kiểm định chuyên sâu/paper là OPTIONAL/SHOULD, Deferred bonus work; descriptive analytics là giới hạn Slice 14.

---

# 19. CÂU HỎI CẦN XÁC NHẬN

Các câu hỏi kỹ thuật về booking nhiều dịch vụ, expiry boundary, biển số và negative adjustment đã được khóa tại DEC-017, DEC-005, DEC-031 và DEC-032.

Q-020 đã được checkpoint trước Slice 14 xác nhận:

1. Survey thật có bắt buộc không?
2. ML model có bắt buộc không?
3. Paper hoặc conference-format report có bắt buộc không?
4. Các deliverable này có được chấm riêng ngoài website không?
5. Quy mô dataset chính xác là bao nhiêu?

Kết quả: các deliverable trên không bắt buộc cho sản phẩm chính; chỉ là điểm cộng nếu còn thời gian,
không chặn Slice 14 hoặc release. Synthetic acceptance vẫn tối thiểu 2.000 record.

Quyết định được ghi tại DEC-034 và `docs/CHANGELOG_REQUIREMENTS.md`.

---

# 20. LỆNH KHỞI ĐẦU CHO AI AGENT

Không yêu cầu Agent tạo toàn bộ hệ thống một lần.

Lệnh đầu tiên:

```text
Đọc toàn bộ PROJECT_SPECIFICATION.md. Chưa viết code.
Hãy tạo Requirement Traceability Matrix, ERD dạng Mermaid, danh sách migration,
acceptance test và kế hoạch vertical slice. Chỉ ra mọi điểm còn mơ hồ.
Không tự thêm tính năng và không sửa đặc tả.
```

Sau khi nhóm review đầu ra này mới bắt đầu Slice 01.
