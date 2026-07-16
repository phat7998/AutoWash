# AUTOWASH PRO — KẾ HOẠCH LÀM VIỆC VỚI AI AGENT VÀ BỘ PROMPT THEO TỪNG SLICE

> **Mục đích:** Biến đặc tả AutoWash Pro thành một chuỗi nhiệm vụ nhỏ, có thể kiểm thử, commit và bàn giao giữa nhiều phiên AI Agent.  
> **Tài liệu bắt buộc đọc trước:** `docs/PROJECT_SPECIFICATION.md` hoặc file `huong_dan_hoan_chinh.md`.  
> **Tổng cấu trúc:** 6 giai đoạn, Slice 00–15 và Mini-Slice 00B.  
> **Nguyên tắc:** Mỗi lần chỉ giao đúng một slice; không yêu cầu Agent “làm toàn bộ dự án”.
> **Cập nhật 00B Closure Patch:** File này ghi nhận các decision DEC-005/017/031..033 và bắt buộc đọc `DESIGN_SYSTEM.md` trước UI. Khi prompt slice cũ khác baseline mới hơn, `PROJECT_SPECIFICATION.md`, `DECISIONS.md`, `ERD.md` và `ROADMAP.md` được ưu tiên.

---

# 1. TỔNG QUAN QUY TRÌNH

## Giai đoạn 0 — Khóa yêu cầu và thiết kế

- **Slice 00:** Audit yêu cầu, traceability, ERD, test plan.
- **Mini-Slice 00B:** Khóa kiến trúc/data/research/NFR và tạo Design System; chưa viết code.

## Giai đoạn 1 — Xây móng kỹ thuật

- **Slice 01:** Khởi tạo repository, Composer, environment, Docker và coding standards.
- **Slice 02:** Database foundation, migration, seed, PDO.
- **Slice 03:** Front Controller, Router, Request/Response, View, error handling, CSRF/session.

## Giai đoạn 2 — Luồng customer cốt lõi

- **Slice 04:** Authentication và role authorization.
- **Slice 05:** Vehicle management và license plate validation.
- **Slice 06:** Service catalog, wash slots và admin capacity.
- **Slice 07:** Booking creation, pricing, booking window và concurrency.
- **Slice 08:** Booking lifecycle, completion và wash history.

## Giai đoạn 3 — Loyalty engine

- **Slice 09:** Loyalty ledger, earn point và point history.
- **Slice 10:** Reward redemption và point expiry.
- **Slice 11:** Monthly tier review, upgrade/downgrade và idempotency.
- **Slice 12:** Tier perks, promotions và admin configuration.

## Giai đoạn 4 — Tích hợp đề tài và nghiên cứu

- **Slice 13:** LPR adapter, upload ảnh và manual fallback.
- **Slice 14:** Research event log, synthetic data, CSV export và dashboard.

## Giai đoạn 5 — Hoàn thiện để chấm và bảo vệ

- **Slice 15:** Security review, test tổng, demo seed, tài liệu, rehearsal và release.

> Slice 15 là slice tích hợp cuối. Mini-Slice 00B là checkpoint tài liệu bổ sung giữa Slice 00 và 01; tổng cộng **17 lượt giao việc**, trong đó 00 và 00B chưa viết code sản phẩm.

---

# 2. NGUYÊN TẮC LÀM VIỆC VỚI AI AGENT

## 2.1. Một phiên Agent chỉ có một mục tiêu

Không dùng:

```text
Hãy xây dựng toàn bộ website AutoWash Pro.
```

Dùng:

```text
Chỉ thực hiện Slice 07 theo tài liệu. Không mở rộng sang Loyalty hoặc Promotion.
```

## 2.2. Mọi phiên phải bắt đầu bằng đọc trạng thái

Agent phải đọc:

- `docs/PROJECT_SPECIFICATION.md`
- `docs/REQUIREMENT_TRACEABILITY.md`
- `docs/IMPLEMENTATION_STATUS.md`
- `docs/DECISIONS.md`
- `docs/ASSUMPTIONS.md`
- `docs/ROADMAP.md`
- `README.md`
- `git status`
- `git log --oneline -10`

Nếu file chưa tồn tại ở Slice 00/01 thì tạo nó.

Trước khi tạo hoặc sửa bất kỳ giao diện nào, Agent bắt buộc đọc đầy đủ `docs/DESIGN_SYSTEM.md`.

## 2.3. Mọi phiên phải kết thúc bằng bàn giao

Agent phải cập nhật `docs/IMPLEMENTATION_STATUS.md`:

```markdown
## Slice XX — Tên slice

- Requirements:
- Hoàn thành:
- Chưa hoàn thành:
- File thay đổi:
- Migration:
- Test đã chạy:
- Kết quả:
- Quyết định:
- Rủi ro còn lại:
- Lệnh chạy tiếp:
- Commit đề xuất:
```

## 2.4. Không để Agent tự báo “Done”

Slice chỉ được nhóm chấp nhận khi:

- Test thật đã chạy.
- Nhóm mở được UI/route chính.
- Không có TODO ở luồng MUST.
- Traceability được cập nhật.
- Git diff nằm trong phạm vi.
- Có commit checkpoint.

## 2.5. Commit theo slice

Tên branch gợi ý:

```text
feature/slice-07-booking
```

Commit gợi ý:

```text
feat(BKG): implement tier-based booking flow [Slice 07]
test(BKG): add booking boundary and capacity tests
docs(BKG): update traceability and demo steps
```

Commit phải là tiếng Việt
Không để Agent gom 10 chức năng vào một commit.

---

# 3. PROMPT KHUNG DÙNG CHUNG

Dán khối này trước prompt riêng của mỗi slice, hoặc lưu thành `AGENTS.md`.

```text
Bạn là Senior PHP Engineer làm việc trong repository AutoWash Pro.

Nguồn sự thật:
1. docs/PROJECT_SPECIFICATION.md
2. docs/REQUIREMENT_TRACEABILITY.md
3. docs/DECISIONS.md
4. docs/IMPLEMENTATION_STATUS.md
5. docs/ASSUMPTIONS.md
6. docs/ROADMAP.md
7. docs/DESIGN_SYSTEM.md trước mọi UI
8. Code và test hiện có

Quy tắc bắt buộc:
- Web Tiếng Việt
- Modern PHP 8.2+, không dùng application framework.
- Tuân thủ Front Controller + Controller + Service + Repository + View.
- Controller không chứa SQL hoặc business formula.
- Repository không chứa business decision.
- Mọi input frontend phải được validate lại ở backend.
- Prepared statement, CSRF, session authorization và output escaping bắt buộc.
- Không hard-code giá, tier, point rate, perk hoặc promotion nếu chúng thuộc database/config.
- Không thay đổi schema hoặc business rule ngoài slice mà không ghi ADR/DECISIONS.
- Không tạo TODO/placeholder trong luồng MUST.
- Không tuyên bố test pass nếu chưa chạy.
- Chỉ sửa file liên quan trực tiếp đến slice.
- Không làm trước chức năng của slice sau.
- Nếu phát hiện lỗi ngoài phạm vi, ghi vào docs/IMPLEMENTATION_STATUS.md mục “Rủi ro/Backlog”, không tự sửa lan man.
- Commit hay cmt trong code đều là tiếng Việt

Quy trình trước khi code:
1. Đọc tài liệu và git status/log.
2. Tóm tắt trạng thái hiện tại.
3. Nêu Requirement ID của slice.
4. Nêu acceptance criteria.
5. Liệt kê file dự kiến tạo/sửa.
6. Nêu migration, transaction, validation, authorization và test cần có.
7. Kiểm tra xem slice có phụ thuộc chưa hoàn thành hay không.

Quy trình sau khi code:
1. Chạy formatter/lint nếu có.
2. Chạy test mới.
3. Chạy toàn bộ regression test liên quan.
4. Tự review git diff.
5. Kiểm tra không có secret, debug dump, TODO hoặc code chết.
6. Cập nhật traceability, implementation status và README nếu cần.
7. Báo cáo chính xác:
   - File thay đổi
   - Migration
   - Test và kết quả thật
   - Cách demo
   - Hạn chế còn lại
   - Commit message đề xuất

Hãy dừng sau khi hoàn tất đúng slice được giao.
```

## 3.1. Baseline Mini-Slice 00B áp dụng cho mọi prompt phía dưới

- Pure Modern PHP/MySQL/PDO/Front Controller/Controller–Service–Repository/Composer PSR-4/Dotenv/PHPUnit; không full-stack framework.
- Bốn loại xe qua `vehicle_types`; không ENUM.
- Giá/thời lượng/capacity theo `service_vehicle_prices`.
- Slot dùng capacity units; multi-service cộng duration, lấy max capacity và giữ trên mọi slot chồng lấn bằng reservations atomically.
- Loyalty dùng `loyalty_transactions` + `loyalty_allocations`, FEFO và expiry 12 calendar months có clamp/boundary thống nhất.
- Biển số dân sự Việt Nam thông dụng được normalize/validate trong shared service theo DEC-031.
- Adjustment âm vượt available points bị từ chối, không clamp; reason/ledger/locking bắt buộc.
- Tier seed/rate và công thức point theo `DECISIONS.md`.
- Cancellation customer cutoff 2 giờ.
- Promotion/reward kiểm tra service/vehicle type ở backend.
- LPR dùng `LprProviderInterface` + mock + manual fallback.
- Synthetic minimum 2.000; performance target theo NFR-02.
- Survey/ML/paper là external academic deliverable pending lecturer confirmation, non-blocking cho Slice 01; checkpoint trước Slice 14.
- Sản phẩm được gọi là “phạm vi hoàn thiện để nộp đồ án”, không phải prototype/MVP sơ sài.

Nếu chi tiết prompt slice cũ bên dưới mâu thuẫn danh sách này, phải dùng baseline 00B và ghi cập nhật trong status/traceability.

---

# 4. SLICE 00 — AUDIT YÊU CẦU VÀ THIẾT KẾ

## Mục tiêu

Biến đề tài và đặc tả thành tài liệu kỹ thuật có thể truy vết. Chưa viết code sản phẩm.

## Đầu ra

- `docs/PROJECT_SPECIFICATION.md`
- `docs/REQUIREMENT_TRACEABILITY.md`
- `docs/ERD.md`
- `docs/TEST_PLAN.md`
- `docs/DECISIONS.md`
- `docs/ASSUMPTIONS.md`
- `docs/IMPLEMENTATION_STATUS.md`
- `docs/ROADMAP.md`

## Prompt

```text
Thực hiện Slice 00 — Requirements Audit and Technical Design.

CHƯA VIẾT CODE SẢN PHẨM.

Đọc:
- File đặc tả AutoWash Pro.
- File đề tài SU26SWP01 nếu có trong repository.
- Mọi ghi chú/Q&A hiện có.

Nhiệm vụ:
1. Chuyển đặc tả vào docs/PROJECT_SPECIFICATION.md, giữ nguyên ý nghĩa.
2. Tạo Requirement Traceability Matrix cho toàn bộ AUTH, VEH, CAT, SLOT, BKG, LOY, TIER, RWD, PRO, ADM, REP, LPR, RBL và NFR.
3. Tạo ERD Mermaid chứa entity, khóa chính, khóa ngoại và cardinality.
4. Tạo TEST_PLAN phân loại unit, integration, feature, security, demo.
5. Tạo DECISIONS ghi các quyết định đã chốt:
   - một chi nhánh
   - priority bằng booking window
   - spend AND visits
   - completed mới cộng loyalty
   - point expiry 12 tháng
   - FEFO
   - không online payment/refund
   - LPR có manual fallback
6. Tạo ASSUMPTIONS và danh sách câu hỏi cần hỏi giảng viên.
7. Tạo ROADMAP theo Slice 01–15.
8. Tạo IMPLEMENTATION_STATUS ban đầu.
9. Kiểm tra requirement nào chưa có acceptance criteria và bổ sung theo đặc tả, không tự phát minh tính năng.
10. Đưa ra danh sách migration dự kiến theo thứ tự dependency, nhưng chưa tạo migration.

Acceptance:
- Mọi MUST requirement có ID.
- Mỗi requirement có ít nhất một test dự kiến.
- ERD không thiếu loyalty transaction, expiry, tier history, promotion và research log.
- Các yêu cầu optional/out-of-scope được đánh dấu rõ.
- Không có file PHP sản phẩm nào được tạo.

Kết thúc bằng bản tóm tắt các điểm nhóm cần review trước Slice 01.
```

## Kiểm tra thủ công

- So sánh traceability với đề tài.
- Kiểm tra không biến optional AI thành MUST.
- Kiểm tra online payment/refund nằm OUT.

---

# 5. SLICE 01 — REPOSITORY VÀ MÔI TRƯỜNG

## Requirement liên quan

- NFR code quality.
- Cấu trúc thư mục.
- Khả năng chạy từ môi trường sạch.

## Prompt

```text
Thực hiện Slice 01 — Project Bootstrap and Development Environment.

Chỉ xây nền repository, chưa triển khai database nghiệp vụ hoặc chức năng người dùng.

Nhiệm vụ:
1. Khởi tạo composer.json:
   - PHP >= 8.2
   - PSR-4 App\\ -> app/
   - autoload-dev Tests\\ -> tests/
   - scripts test và lint nếu phù hợp
2. Cài dependency tối thiểu:
   - dotenv
   - PHPUnit dev
   Không thêm framework.
3. Tạo đúng cấu trúc thư mục trong đặc tả.
4. Tạo .gitignore và .env.example, không tạo secret thật.
5. Tạo config app/database/loyalty ở dạng đọc env.
6. Tạo Docker Compose tối thiểu cho web + MySQL nếu môi trường dự án dùng Docker.
7. Tạo README:
   - yêu cầu hệ thống
   - cách cài
   - lệnh chạy
   - lệnh test
8. Tạo PSR-12 coding standard hoặc lệnh lint đơn giản.
9. Tạo một smoke test xác nhận Composer autoload hoạt động.
10. Cập nhật docs/IMPLEMENTATION_STATUS.md.

Không làm:
- Router.
- Login.
- Migration nghiệp vụ.
- UI.

Acceptance:
- composer install thành công.
- Smoke test pass.
- .env không bị track.
- Cấu trúc thư mục đúng.
- docker compose config hợp lệ nếu Docker được dùng.
```

## Checkpoint

```bash
composer install
composer dump-autoload
vendor/bin/phpunit
git status
```

---

# 6. SLICE 02 — DATABASE FOUNDATION

## Requirement liên quan

- Toàn bộ data model.
- PDO.
- Migration/seed repeatable.

## Prompt

```text
Thực hiện Slice 02 — Database Foundation, Migrations, Seeds and PDO.

Phạm vi:
- Database connector.
- Migration runner.
- Schema theo ERD.
- Seed cấu hình và demo cơ bản.
Chưa làm Controller/View nghiệp vụ.

Nhiệm vụ:
1. Implement app/Core/Database.php:
   - lazy singleton PDO
   - ERRMODE_EXCEPTION
   - FETCH_ASSOC
   - EMULATE_PREPARES false
   - utf8mb4
   - timezone phù hợp
2. Tạo migration runner có bảng migration history.
3. Tạo migrations theo dependency cho:
   tiers, users, vehicle_types, vehicles, services, service_vehicle_prices,
   wash_slots, bookings, booking_items, loyalty_transactions, loyalty_allocations,
   rewards, reward_vehicle_types, reward_redemptions, tier_perks,
   promotions, promotion_tiers, promotion_services, promotion_vehicle_types,
   promotion_usages, tier_histories,
   monthly_review_runs, research_event_logs và bảng audit/lpr nếu nằm trong kế hoạch.
4. Thêm foreign key, unique constraint và index cần thiết.
5. Dùng DECIMAL cho tiền.
6. Tạo seed:
   - 4 tiers với threshold/window/rate theo DEC-019
   - 4 vehicle types với duration/capacity theo DEC-015
   - app settings/loyalty config cần thiết
   - dịch vụ mẫu
   - slot mẫu
7. Tạo database reset/seed command an toàn cho dev.
8. Tạo integration test:
   - kết nối DB
   - migrate từ database trống
   - seed không lỗi
   - các unique constraint quan trọng
9. Cập nhật ERD nếu implementation khác tài liệu; khác biệt phải được giải thích.
10. Cập nhật traceability và implementation status.

Đặc biệt:
- Không lưu point expiry chỉ bằng users.point_balance.
- Có unique/idempotency constraints cho loyalty earn, tier history, promotion usage.
- Không ON DELETE CASCADE làm mất lịch sử tài chính/loyalty quan trọng.
- Nếu cần soft-delete, dùng is_active/status.

Acceptance:
- Có thể reset database sạch và migrate lại.
- Seed chạy lặp theo cơ chế được thiết kế mà không tạo dữ liệu rác.
- Test DB pass.
- Không có SQL trong Controller vì Controller chưa được tạo.
```

## Checkpoint

```bash
php database/migrate.php
php database/seed.php --demo
vendor/bin/phpunit tests/Integration/Database
```

---

# 7. SLICE 03 — HTTP CORE, ROUTER VÀ SECURITY FOUNDATION

## Requirement liên quan

- Front Controller.
- CSRF/session.
- Error handling.
- View escaping.

## Prompt

```text
Thực hiện Slice 03 — Front Controller, Router and Web Security Foundation.

Chỉ triển khai hạ tầng HTTP; chưa làm login hoặc business module.

Nhiệm vụ:
1. Tạo public/index.php làm Front Controller duy nhất.
2. Tạo public/.htaccess cho clean URL, bảo vệ file nhạy cảm.
3. Tạo bootstrap/app.php.
4. Implement:
   - Request
   - Response
   - Router
   - RouteNotFound handling
   - View renderer
   - redirect helper
   - flash message
   - Session wrapper
   - CSRF token service/middleware
   - HTML escape helper
   - Error handler và logger
5. Tạo routes/web.php với:
   - GET / health/home
   - GET /health trả trạng thái tối thiểu, không lộ secret
6. Implement Post/Redirect/Get mẫu.
7. Tạo 403, 404, 419/CSRF và 500 views.
8. Tạo test:
   - route matching
   - method mismatch
   - CSRF valid/invalid
   - escaping
   - session flash
9. Không xây AuthController.
10. Cập nhật docs.

Acceptance:
- Mọi request đi qua public/index.php.
- File .env/app không truy cập trực tiếp qua web.
- POST không có CSRF bị từ chối.
- Error production không lộ stack trace.
- Test core pass.
```

---

# 8. SLICE 04 — AUTHENTICATION VÀ AUTHORIZATION

## Requirement

- AUTH-01 đến AUTH-04.
- ADM authorization foundation.

## Prompt

```text
Thực hiện Slice 04 — Authentication and Role Authorization.

Nhiệm vụ:
1. Tạo UserRepository chỉ chứa query user.
2. Tạo AuthService xử lý register/login/logout.
3. Tạo AuthController mỏng.
4. Tạo login/register views.
5. Tạo middleware:
   - Authenticated
   - Guest
   - Role/admin
6. Register:
   - validate phone, name, password
   - unique phone
   - role luôn customer
   - password_hash BCRYPT
7. Login:
   - password_verify
   - generic error
   - regenerate session ID
   - disabled user bị từ chối
8. Logout qua POST + CSRF.
9. Tạo customer dashboard và admin dashboard placeholder có route thật nhưng chưa có số liệu.
10. Test:
   - register hợp lệ
   - duplicate phone
   - weak password
   - login đúng/sai
   - session fixation prevention
   - customer không vào admin
   - guest không vào customer
11. Seed một admin và customer demo.
12. Cập nhật traceability/status/README demo account.

Không làm:
- Vehicle.
- Booking.
- Loyalty.

Acceptance:
- Không có role escalation qua request.
- Backend guard hoạt động kể cả gọi URL trực tiếp.
- Password không xuất hiện trong log/DB dạng thuần.
```

---

# 9. SLICE 05 — VEHICLE MANAGEMENT

## Requirement

- VEH-01 đến VEH-03.
- LPR baseline manual.

## Prompt

```text
Thực hiện Slice 05 — Vehicle Management and Plate Validation.

Nhiệm vụ:
1. Tạo VehicleRepository, VehicleService, VehicleController.
2. Tạo normalize license plate helper có unit test.
3. Shared validator/service uppercase và bỏ space/`-`/`.`; validate biển dân sự VN thông dụng theo pattern tập trung `2 số + 1–2 chữ + 4–5 số` và dùng `vehicle_type_id` từ bảng cấu hình.
4. Lưu/dựng `display_plate`; unique bằng `normalized_plate`.
5. CRUD customer vehicle:
   - list
   - create
   - edit
   - deactivate
6. Không xóa cứng vehicle có lịch sử.
7. Bắt duplicate DB exception và trả domain error dễ hiểu.
8. Kiểm tra ownership trong Service/Repository query.
9. UI có frontend validation nhưng backend là nguồn quyết định.
10. Test:
   - normalize
   - duplicate
   - invalid plate
   - lowercase/separator normalize
   - common civilian fixture cho bốn vehicle types
   - out-of-scope plate trả lỗi rõ ràng/manual review nếu có
   - customer A không xem/sửa xe B
   - deactivate vehicle
11. Seed xe cho 4 customer.
12. Cập nhật docs.

Không gọi regex validation là LPR.
Không làm upload ảnh ở slice này.

Acceptance:
- Duplicate plate không làm app crash.
- Thay vehicle_id trong request không vượt quyền.
- Dữ liệu normalized unique.
```

---

# 10. SLICE 06 — SERVICE CATALOG VÀ WASH SLOT

## Requirement

- CAT-01.
- SLOT-01.
- Admin service/slot management.

## Prompt

```text
Thực hiện Slice 06 — Service Catalog and Wash Slot Capacity.

Nhiệm vụ:
1. Implement ServiceRepository/ServiceService/AdminServiceController.
2. Customer xem dịch vụ active có cặp `service_vehicle_prices` supported/active đúng vehicle type.
3. Admin CRUD/inactivate dịch vụ.
4. Implement WashSlotRepository/WashSlotService/AdminSlotController.
5. Admin tạo slot theo ngày, giờ, `capacity_units` và trạng thái.
6. Không cho:
   - capacity <= 0
   - end_time <= start_time
   - duplicate slot
   - slot ngày quá khứ
7. Customer xem slot khả dụng; response chứa remaining capacity units tính từ booking active.
8. Giá tiền lấy từ DB và format đúng VND.
9. Snapshot chưa thực hiện cho đến Slice 07.
10. Test service visibility, slot validation, capacity calculation.
11. Seed slot trống, gần đầy, đầy, closed.
12. Cập nhật docs.

Không làm create booking trong slice này.
```

---

# 11. SLICE 07 — BOOKING CREATION VÀ PRICING

## Requirement

- BKG-01, BKG-02, BKG-03.
- BKG-07.
- SLOT-02.
- PRO/RWD chưa áp dụng thật nếu các module chưa tồn tại; chỉ thiết kế extension point.

## Prompt

```text
Thực hiện Slice 07 — Booking Creation, Tier Window, Pricing and Capacity Concurrency.

Đây là slice nghiệp vụ trọng yếu.

Nhiệm vụ:
1. Tạo BookingRepository, BookingService, BookingController.
2. Tạo DTO/validator cho create booking.
3. Customer chọn:
   - vehicle của mình
   - wash slot
   - một hoặc nhiều service phù hợp vehicle type
4. Tính booking lead_days bằng timezone Asia/Ho_Chi_Minh.
5. Tải booking_window_days từ tier trong DB.
6. Boundary:
   - quá khứ: reject
   - vượt window: reject
   - bằng đúng window: accept
7. Tính subtotal server-side từ service price.
8. Tạo booking_items snapshot.
9. Tạo booking code duy nhất.
10. Backend tính `booking_duration_minutes` bằng tổng duration của các item và `booking_capacity_units` bằng max giữa vehicle default và mọi service override; không tin duration/capacity client.
11. Xác định toàn bộ slot chồng lấn với khoảng booking.
12. Trong transaction:
    - lock mọi slot chồng lấn theo thứ tự ổn định bằng `SELECT ... FOR UPDATE` hoặc tương đương
    - tính reservations của booking pending/confirmed trên từng slot
    - kiểm tra tất cả slot đủ cùng `booking_capacity_units`
    - kiểm tra duplicate active booking của vehicle trong khoảng chồng lấn
    - insert booking/items/booking_slot_reservations atomically
13. Chưa áp promotion/reward thật; đặt discount = 0 và tạo PriceCalculator có extension rõ.
14. Dùng Post/Redirect/Get.
15. Test:
    - 4 tier window boundary
    - vehicle ownership
    - service/vehicle mismatch
    - tamper giá từ client
    - slot full
    - duplicate vehicle/slot
    - hai request tranh slot cuối ở mức integration phù hợp
    - hai service cộng tổng duration nhưng lấy max capacity, không cộng units
    - capacity không thấp hơn vehicle default
    - giữ đủ mọi slot overlap; slot giữa đầy làm rollback toàn bộ
    - request lỗi không để reservation rác
16. Ghi research event booking_created nếu research log schema đã có, nhưng không làm dashboard.
17. Cập nhật traceability/status/demo.

Acceptance:
- Chỉ một request lấy được chỗ cuối.
- Giá client gửi lên không ảnh hưởng final_price.
- Booking/items/reservations được rollback cùng nhau khi lỗi.
- Không làm Loyalty trong slice này.
```

---

# 12. SLICE 08 — BOOKING LIFECYCLE VÀ COMPLETION

## Requirement

- BKG-04, BKG-05, BKG-06.
- Wash history.

## Prompt

```text
Thực hiện Slice 08 — Booking Lifecycle, Completion and Wash History.

Nhiệm vụ:
1. Định nghĩa state transition ở BookingService, không rải trong Controller.
2. Customer:
   - xem booking list/detail
   - hủy pending/confirmed của mình
3. Admin:
   - confirm
   - complete
   - cancel
   - mark no_show
4. Invalid transition trả domain error.
5. Cancel phải giải phóng capacity một cách tự nhiên vì cancelled không được đếm.
6. Complete booking:
   - lock booking
   - chỉ xử lý nếu confirmed
   - set completed/completed_at
   - tạo hook/process marker cho loyalty
7. Vì Slice 09 mới làm loyalty:
   - tạo interface/transaction orchestration phù hợp
   - không giả cộng điểm
   - booking completion phải sẵn sàng tích hợp mà không phá kiến trúc
8. Wash history lấy completed bookings và item snapshots.
9. Test:
   - transition matrix
   - duplicate complete request
   - customer hủy booking người khác
   - completed không hủy
   - cancelled slot capacity
10. Cập nhật docs.

Quan trọng:
Nếu chưa có LoyaltyService, không cập nhật point_balance hoặc monthly metrics trực tiếp trong Controller.
```

> Khi Slice 09 được triển khai, test completion phải được mở rộng thành một transaction hoàn chỉnh.

---

# 13. SLICE 09 — LOYALTY LEDGER VÀ EARN POINT

## Requirement

- LOY-01, LOY-02.
- BKG-06 tích hợp hoàn chỉnh.

## Prompt

```text
Thực hiện Slice 09 — Loyalty Ledger, Earn Points and Completion Integration.

Nhiệm vụ:
1. Tạo LoyaltyTransactionRepository và LoyaltyService.
2. Implement công thức:
   floor(floor(final_price / 10.000) * tier.point_rate)
3. Không cập nhật point_balance ngoài LoyaltyService.
4. Tích hợp vào complete booking trong cùng transaction:
   - lock booking/user
   - xác nhận chưa loyalty_processed
   - complete booking
   - tăng monthly_spend
   - tăng monthly_visits 1
   - insert earn transaction
   - tăng point_balance
   - set loyalty_processed_at
   - research event
5. Unique constraint ngăn earn hai lần từ cùng booking.
6. Customer xem:
   - point balance
   - transaction history
   - điểm sắp hết hạn 30 ngày
7. Reconcile command/test:
   - tính balance theo ledger
   - so với users.point_balance
8. Implement admin adjustment: reason bắt buộc; mọi mutation có ledger/audit; negative vượt available points bị reject (không clamp), optional source transaction reference; locking chống concurrent overspending.
9. Test:
   - công thức
   - final_price 0
   - tier rate
   - complete hai lần
   - rollback nếu loyalty insert lỗi
   - spend/visits tăng đúng
   - ledger/balance consistency
   - positive/negative/equal/over-balance adjustment
   - missing reason và concurrent negative adjustments
10. Refactor Slice 08 completion nếu cần, không phá transition.
11. Cập nhật docs/demo.

Acceptance:
- Completion và loyalty atomically.
- Không có trạng thái completed nhưng loyalty thiếu do lỗi giữa chừng.
- Không cộng hai lần.
```

---

# 14. SLICE 10 — REWARD REDEMPTION VÀ POINT EXPIRY

## Requirement

- LOY-03, LOY-04.
- RWD.

## Prompt

```text
Thực hiện Slice 10 — Rewards, Redemption, FEFO and Point Expiry.

Nhiệm vụ:
1. Implement RewardRepository, RewardService, RewardController.
2. Admin CRUD/inactivate reward.
3. Customer xem reward đủ điều kiện theo tier.
4. Redeem atomically:
   - lock user và các earning lots
   - kiểm tra reward active/tier/points
   - trừ theo FEFO: expires_at sớm nhất trước
   - update remaining_points của earning lots
   - insert redeem transaction
   - giảm point_balance
   - tạo reward_redemption available
5. Không thay đổi monthly_spend/monthly_visits.
6. Implement expire-points CLI:
   - lô hết hạn và còn remaining_points
   - insert expire transaction
   - giảm balance
   - idempotent
   - `expires_at = earned_at + 12 calendar months` với clamp ngày cuối tháng
   - expire khi `current_time >= expires_at` trong timezone hệ thống, không dùng 365 ngày
7. Hiển thị điểm sắp hết hạn.
8. Chính sách reward redemption expiry.
9. Test:
   - đủ/thiếu điểm
   - FEFO
   - concurrent redeem không âm balance
   - redeem không giảm tier metrics
   - expire một phần
   - job chạy lại
   - leap-day, cuối tháng, ngay trước và đúng `expires_at`
   - reward tier restriction
10. Cập nhật docs.

Chưa áp reward vào booking checkout; phần đó hoàn thiện ở Slice 12 cùng pricing integration.
```

---

# 15. SLICE 11 — MONTHLY TIER REVIEW

## Requirement

- TIER-01 đến TIER-04.

## Prompt

```text
Thực hiện Slice 11 — Monthly Tier Review and Tier History.

Nhiệm vụ:
1. Implement TierRepository, TierReviewService và CLI monthly-review.
2. Kỳ review dạng YYYY-MM, xét tháng vừa kết thúc.
3. Qualification:
   spend >= min_spend AND visits >= min_visits.
4. Chọn tier rank cao nhất đủ điều kiện.
5. Upgrade/downgrade nhiều bậc.
6. Với mỗi user:
   - snapshot old/new tier, spend, visits
   - insert tier_history unique user/period
   - update current tier
   - reset monthly metrics
7. Quản lý monthly_review_runs:
   - running/completed/failed
   - review_period unique
   - completed thì không chạy lại bình thường
8. Xử lý failure an toàn; mô tả chiến lược transaction theo user hoặc toàn batch.
9. Admin xem kết quả run.
10. Test:
    - boundary threshold
    - AND semantics
    - highest qualified
    - upgrade/downgrade
    - point balance không reset
    - run lặp
    - history unique
    - failure recovery ở mức khả thi
11. Seed customer đủ các kịch bản.
12. Cập nhật docs/demo.

Không hard-code threshold.
```

---

# 16. SLICE 12 — PROMOTION, TIER PERK VÀ CHECKOUT INTEGRATION

## Requirement

- PRO-01 đến PRO-04.
- Admin configure tier/point rate/perk/promotion.
- Auto-apply perks at checkout.
- Reward usage.

## Prompt

```text
Thực hiện Slice 12 — Tier Configuration, Perks, Promotions and Checkout Integration.

Nhiệm vụ:
1. Admin quản lý tier rule:
   - booking window
   - spend/visit threshold
   - point rate
   - validation không âm, rank unique
2. Admin CRUD tier perk.
3. Admin CRUD promotion:
   - thời gian
   - type/value/max
   - min order
   - usage limit/per-user
   - target tiers
4. Promotion Silver+ phải map tới Silver, Gold, Platinum.
5. Implement PromotionService và PriceCalculator:
   - tải subtotal server-side
   - chọn một perk tốt nhất
   - chọn một promotion hợp lệ tốt nhất
   - cho customer chọn tối đa một reward redemption available
   - final_price không âm
6. Tích hợp vào create booking hoặc bước confirm checkout:
   - lưu discount snapshots
   - lưu promotion_id/reward relation
7. Khi booking completed:
   - promotion usage được ghi một lần
   - reward chuyển used một lần
8. Khi booking cancelled trước completion:
   - reward trở lại available nếu còn hạn
   - không ghi promotion usage
9. Test:
   - Silver+ targeting
   - thời gian boundary
   - min order
   - usage limit
   - best promotion
   - perk
   - reward ownership/type
   - không stacking nhiều promotion
   - client tamper discount
   - complete/cancel idempotency
10. Cập nhật docs/demo.

Acceptance:
- Backend giải thích được từng dòng discount.
- Không dùng promotion/reward của user khác.
- Các counter không vượt limit khi concurrent request ở mức test phù hợp.
```

---

# 17. SLICE 13 — LPR ADAPTER VÀ MANUAL FALLBACK

## Requirement

- LPR-01, LPR-02.

## Prompt

```text
Thực hiện Slice 13 — License Plate Recognition Adapter and Safe Upload.

Nhiệm vụ:
1. Giữ manual plate input là luồng bắt buộc, hoạt động độc lập.
2. Tạo:
   - LprProviderInterface
   - RecognitionResult
   - MockLprProvider
   - ExternalLprProvider nếu endpoint/model có sẵn
3. Upload:
   - allowlist MIME
   - giới hạn size
   - random filename
   - không thực thi file
   - lưu ngoài public hoặc phục vụ qua controller an toàn
4. Flow:
   upload -> recognize -> show predicted text/confidence -> user confirm/edit
   -> normalize -> backend validate -> save vehicle.
5. Ghi lpr_attempt.
6. Timeout/failure recognizer không làm mất manual flow.
7. Không gọi mock là mô hình thật trong UI/docs.
8. Test:
   - invalid MIME/size
   - recognizer success/failure/timeout
   - manual override
   - normalize sau recognition
   - authorization ảnh/log
9. README hướng dẫn bật mock hoặc real adapter.
10. Cập nhật traceability và giới hạn.

Nếu chưa có mô hình thật, hoàn thành bằng adapter + mock + manual fallback và ghi trung thực.
```

---

# 18. SLICE 14 — RESEARCH DATA, SYNTHETIC GENERATOR VÀ DASHBOARD

## Requirement

- RBL-01 đến RBL-05.
- REP.

Trước phần Research/RBL chuyên sâu, kiểm tra Q-020 với giảng viên. Trạng thái mặc định là **External academic deliverable — Pending lecturer confirmation** và không hồi tố chặn Slice 01.

## Prompt

```text
Thực hiện Slice 14 — Research Event Logging, Synthetic Data and CSV Export.

Nhiệm vụ:
1. Audit toàn bộ event cần thu:
   booking_created, booking_completed, reward_redeemed,
   points_expired, tier_changed, promotion_used.
2. Implement ResearchEventRepository/Service.
3. Tạo anonymous_user_key; không export PII.
4. Ghi các feature:
   event_time, tier, vehicle_type, booking_lead_days, order_value,
   points earned/redeemed, used_reward, promotion, visit/spend snapshot.
5. Tạo export-research-data CLI:
   - CSV UTF-8
   - schema/version
   - date range
   - source filter
   - không PII
6. Tạo synthetic generator:
   - deterministic seed
   - số lượng configurable, tối thiểu 2.000 records trong acceptance
   - bao phủ motorbike, car, truck, bus
   - data_source=synthetic
   - phân phối hợp lý, ghi assumptions
   - không giả là dữ liệu thật
7. Admin dashboard:
   - bookings today
   - revenue completed
   - tier distribution
   - earn/redeem/expire
   - reward/promotion usage
8. Tạo data dictionary.
9. Test:
   - export không chứa PII
   - CSV columns
   - deterministic synthetic seed
   - event idempotency cho completion
   - dashboard chỉ tính completed revenue
10. Cập nhật docs nghiên cứu và hướng dẫn export.

Không huấn luyện ML trong PHP. Notebook/model là deliverable riêng đọc CSV export.
Không tự tạo survey response, bịa accuracy/hypothesis result/kết luận hoặc tuyên bố paper hoàn thành khi chưa có xác nhận và evidence.
```

---

# 19. SLICE 15 — HARDENING, TEST, DEMO VÀ RELEASE

## Mục tiêu

Biến project “đã có chức năng” thành project có thể chấm, demo và phản biện.

## Prompt

```text
Thực hiện Slice 15 — Final Hardening, Verification and Defense Release.

Không thêm chức năng lớn mới.

Nhiệm vụ:
1. Đối chiếu toàn bộ PROJECT_SPECIFICATION và traceability.
2. Liệt kê requirement MUST chưa Done; ưu tiên sửa blocker.
3. Security review:
   - SQL injection
   - XSS
   - CSRF
   - session fixation
   - broken access control/IDOR
   - upload
   - secret exposure
   - price/point tampering
4. Business review:
   - slot race
   - duplicate completion
   - concurrent redeem
   - monthly idempotency
   - expiry idempotency
   - promotion limits
5. Chạy:
   - unit
   - integration
   - feature
   - full suite
   - performance workload 10.000 booking/20 VU theo NFR-02
6. Tạo demo seed resettable.
7. Tạo docs/DEMO_SCRIPT.md từng bước, kèm tài khoản và expected result.
8. Tạo docs/DEFENSE_QA.md:
   - 30 câu giảng viên có thể hỏi
   - câu trả lời dựa trên code thật
9. Tạo docs/KNOWN_LIMITATIONS.md trung thực.
10. Hoàn thiện README từ môi trường sạch.
11. Loại bỏ debug, dead code, TODO, sample secret.
12. Kiểm tra UI:
   - responsive cơ bản
   - validation/error/empty state
   - link/route hỏng
13. Kiểm tra database reset/migrate/seed.
14. Tạo release checklist và tag đề xuất.
15. Cập nhật traceability: Done phải có code + test + demo evidence.

Báo cáo cuối:
- Tổng test pass/fail/skipped.
- Requirement coverage.
- Lỗi còn lại theo severity.
- Các bước demo.
- Hạn chế.
- Lệnh release.
- Commit/tag đề xuất.

Không được che giấu test fail hoặc dùng mock để báo chức năng production đã hoàn thành.
```

---

# 20. PROMPT REVIEW SAU MỖI SLICE

Dùng một Agent/session khác để review, tránh chính Agent viết code tự chấm mình.

```text
Bạn là giảng viên phản biện và senior code reviewer.

Hãy review Slice [XX] nhưng CHƯA SỬA CODE.

Đọc đặc tả, requirement traceability, implementation status và git diff của slice.

Review theo:
1. Bám requirement.
2. Đúng phân tầng.
3. Business rule.
4. Security.
5. Transaction/concurrency/idempotency.
6. Validation và authorization.
7. Test có thật sự bắt lỗi hay chỉ happy path.
8. Database constraint.
9. Khả năng demo và giải thích.
10. Code thừa/over-engineering.

Phân loại:
- BLOCKER: sai requirement, mất dữ liệu, bảo mật nghiêm trọng, test giả.
- HIGH: nghiệp vụ lỗi hoặc thiếu edge case quan trọng.
- MEDIUM: maintainability/UX/test coverage.
- LOW: style và cải tiến.

Mỗi finding phải có:
- file/dòng hoặc thành phần
- tình huống tái hiện
- hậu quả
- cách sửa tối thiểu
- requirement ID liên quan

Kết thúc bằng verdict:
- ACCEPT
- ACCEPT WITH FIXES
- REJECT

Không đề xuất tính năng ngoài phạm vi.
```

---

# 21. PROMPT SỬA FINDING

```text
Hãy sửa đúng các finding đã được duyệt sau đây của Slice [XX]:

[DÁN FINDING]

Quy tắc:
- Không sửa finding chưa được duyệt.
- Không refactor ngoài phạm vi.
- Thêm regression test tái hiện từng bug trước hoặc cùng lúc sửa.
- Chạy test của slice và full regression liên quan.
- Cập nhật implementation status.
- Báo file thay đổi và kết quả test thật.
```

---

# 22. PROMPT KHI CHUYỂN SANG SESSION/AGENT MỚI

```text
Tiếp quản repository AutoWash Pro từ một session trước.

Trước khi làm bất cứ thay đổi nào:
1. Đọc PROJECT_SPECIFICATION, TRACEABILITY, DECISIONS, IMPLEMENTATION_STATUS, README.
2. Chạy git status và xem 10 commit gần nhất.
3. Xác định slice cuối cùng đã hoàn thành và evidence test.
4. Xác định worktree có thay đổi chưa commit hay không.
5. Chạy smoke test hoặc test của slice gần nhất.
6. Tóm tắt:
   - hệ thống đang làm được gì
   - phần nào chưa làm
   - test hiện trạng
   - rủi ro
   - slice tiếp theo đúng roadmap

Chưa code cho đến khi hoàn tất bản tóm tắt tiếp quản.
Sau đó chỉ thực hiện Slice [XX].
```

---

# 23. PROMPT DEBUG KHI TEST FAIL

```text
Test sau đang fail:

[DÁN LỆNH VÀ LOG]

Hãy debug theo bằng chứng, không đoán.

1. Xác định test đang kiểm tra requirement nào.
2. Tái hiện lỗi bằng lệnh nhỏ nhất.
3. Truy vết luồng Controller -> Service -> Repository -> DB.
4. Xác định root cause, phân biệt:
   - lỗi code
   - lỗi test
   - lỗi fixture/seed
   - lỗi môi trường
   - lỗi đặc tả mơ hồ
5. Đưa ra bản sửa tối thiểu.
6. Thêm/điều chỉnh regression test hợp lý.
7. Chạy lại test fail và regression liên quan.
8. Không tắt test, không xóa assertion và không bắt exception chung để làm test xanh.
9. Báo kết quả thật.
```

---

# 24. PROMPT MÔ PHỎNG GIẢNG VIÊN KIẾM THÊM ĐIỂM

```text
Đóng vai giảng viên chấm đồ án AutoWash Pro sau khi nhóm đã demo happy path.

Hãy tạo một phiên kiểm tra phá hệ thống gồm:
- 10 request sai dữ liệu
- 10 tình huống vượt quyền
- 10 boundary business rule
- 5 concurrency/idempotency
- 5 câu database/architecture
- 5 câu research/RBL
- 5 câu về giới hạn LPR/AI

Với mỗi mục:
- thao tác cụ thể
- kết quả đúng mong đợi
- requirement ID
- test hiện có hoặc test cần thêm
- câu trả lời ngắn khi bảo vệ

Chỉ dựa trên đặc tả và code hiện có; không đòi tính năng ngoài phạm vi.
```

---

# 25. CÁCH PHÂN CÔNG NHIỀU AI AGENT

Không nên cho hai Agent cùng sửa một module đồng thời.

Có thể chia vai:

| Vai | Trách nhiệm |
|---|---|
| Implementer | Thực hiện đúng một slice |
| Reviewer | Review diff, không sửa |
| Tester | Sinh và chạy test phá |
| Documentation Agent | Cập nhật traceability/demo/QA sau khi code đã ổn định |
| Research Agent | Data dictionary, survey, notebook; không sửa business core |

Quy tắc:

- Implementer hoàn tất và commit.
- Reviewer đưa finding.
- Nhóm duyệt finding.
- Implementer/fixer sửa.
- Tester chạy regression.
- Mới merge vào main.

---

# 26. THỨ TỰ ƯU TIÊN KHI THỜI GIAN THIẾU

Không được cắt:

1. Auth/RBAC.
2. Vehicle ownership.
3. Service/slot.
4. Booking window/capacity/pricing.
5. Completion idempotency.
6. Loyalty ledger.
7. Redeem không ảnh hưởng tier metrics.
8. Monthly review.
9. Point expiry.
10. Promotion Silver+.
11. Test và demo seed.
12. Research export.

Có thể hạ xuống mock/adapter hoặc cắt:

1. LPR model thật — giữ manual + adapter/mock.
2. Email/SMS thật.
3. AI personalization.
4. UI animation.
5. Advanced chart.
6. Multi-branch.
7. Real-time.

---

# 27. LỊCH 16 TUẦN GỢI Ý

| Tuần | Slice |
|---|---|
| 1 | 00 |
| 2 | 01–02 |
| 3 | 03–04 |
| 4 | 05 |
| 5 | 06 |
| 6 | 07 |
| 7 | 08 |
| 8 | 09 |
| 9 | 10 |
| 10 | 11 |
| 11 | 12 |
| 12 | 13 |
| 13 | 14 |
| 14 | 15 — bug/security |
| 15 | demo, report, research analysis |
| 16 | rehearsal, freeze, submission |

Mỗi tuần phải có ít nhất một commit/demo checkpoint; không chờ tuần cuối mới tích hợp.

---

# 28. CHECKLIST TRƯỚC KHI GIAO PROMPT CHO AGENT

- [ ] Repository đang clean hoặc biết rõ file chưa commit.
- [ ] Đã checkout đúng branch.
- [ ] Slice trước đã có test evidence.
- [ ] Prompt ghi rõ slice và giới hạn.
- [ ] Đặc tả đã nằm trong repo.
- [ ] Không giao hai slice cùng lúc.
- [ ] Đã chỉ rõ không over-engineering.
- [ ] Agent phải cập nhật handoff.
- [ ] Có reviewer sau implementer.
- [ ] Nhóm tự mở và demo lại, không tin hoàn toàn báo cáo của Agent.

---

# 29. LỆNH NÊN DÙNG NGAY BÂY GIỜ

Sau khi đặt hai tài liệu vào repo:

```text
docs/PROJECT_SPECIFICATION.md
docs/AI_AGENT_EXECUTION_PLAN.md
```

Hãy bắt đầu bằng prompt Slice 00. Không bắt đầu bằng tạo Front Controller hoặc database ngay, vì Slice 00 giúp khóa requirement và phát hiện mâu thuẫn trước khi code.

Sau khi nhóm review Slice 00:

1. Commit tài liệu.
2. Chạy Slice 01.
3. Review.
4. Commit.
5. Tiếp tục lần lượt; không nhảy thẳng đến UI hoặc Loyalty.
