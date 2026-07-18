# AUTO WASH PRO — IMPLEMENTATION STATUS

> Cập nhật: 2026-07-18
> Slice hiện tại: Slice 15 — Complete
>
> Product code: đã có nền repository/database, HTTP/security, authentication/RBAC, quản lý phương tiện,
> danh mục dịch vụ, booking, loyalty generic credit lots, FEFO redemption/expiry, reward management và
> monthly tier review, tier/perk/promotion configuration, checkout benefit integration, LPR adapter/mock,
> research event/CSV/synthetic data, descriptive dashboard và release hardening/evidence.

## Tổng quan

## UX correction — Dedicated Admin Reports Page

- Requirements: củng cố `REP-02`; thay liên kết fragment `/admin#bao-cao` bằng trang riêng
  `GET /admin/bao-cao`, giữ nguyên dashboard `/admin` và toàn bộ KPI tóm tắt hiện có.
- Route mới bắt buộc authenticated + role admin; menu Báo cáo có active state theo path.
- Bộ lọc `from_date`/`to_date` mặc định 30 ngày gần nhất, validate định dạng và thứ tự ngày trước khi gọi
  repository. Mọi truy vấn theo khoảng dùng prepared statement với biên cuối exclusive.
- Báo cáo gồm doanh thu completed lũy kế/hôm nay/trong khoảng; booking theo status/loại xe/dịch vụ; phân bố
  tier hiện tại; điểm cộng/trừ; reward đã dùng và promotion đã áp dụng. Khoảng rỗng render số 0/empty state.
- Migration/transaction: không có; không đổi schema hoặc business rule và không thêm thư viện chart.
- Test: `AdminReportDateRangeValidatorTest`, `ResearchFlowTest`, `AuthAuthorizationTest`; HTTP smoke khóa Admin
  `200`, Customer `403`, Guest `303`, filter hợp lệ và khoảng rỗng an toàn.
- Commit: `feat(admin): add operational reports page`.

## UI/UX Productization Pass — Complete

- Requirements: củng cố NFR-01, NFR-03, REP-01/02 và toàn bộ acceptance UI/route/RBAC/CSRF đã khóa; không
  thay đổi schema, transaction hoặc business rule.
- Guest `/` là landing page sản phẩm với CTA thật, dịch vụ, bốn loại phương tiện, quy trình, thành viên, FAQ
  và footer; không còn nội dung CSRF, Front Controller, PRG hoặc scaffold kỹ thuật.
- Customer/Admin truy cập `/` được redirect `303` lần lượt tới `/tai-khoan` và `/admin`; customer header không
  chứa route quản trị, admin dùng sidebar/drawer responsive có đủ phân hệ.
- Chuẩn hóa nội dung tiếng Việt, CTA, card/KPI/table/form/badge/empty state và confirmation; thay thuật ngữ
  backend trên UI bằng ngôn ngữ vận hành hoặc khách hàng, giữ identifier kỹ thuật ở thông tin phụ khi cần.
- Error renderer nhận context phiên để 403/404/405/419/500 giữ đúng product shell và CTA quay về khu vực an
  toàn; production vẫn không hiển thị stack trace.
- Migration: không có. Logic giá, lịch đặt, điểm, hạng, khuyến mãi, quà tặng, LPR và research không đổi.
- Test/evidence: `HttpCoreTest` khóa landing/CTA/no-scaffold/error status; `AuthAuthorizationTest` khóa root
  redirect, customer không thấy menu admin và admin navigation đủ route; full suite, Docker HTTP smoke và
  review viewport được ghi nhận trong phần verification cuối pass.
- Verification thực tế: Docker PHP 8.2.32/MySQL 8.4 `composer check` pass PHPCS 178 file, PHPUnit 169 test/
  946 assertion, không skip, release audit pass. HTTP smoke trả đúng Guest `200`, health `200`, 404/405,
  Customer/Admin root `303` và toàn bộ route navigation chính `200`. Chromium review landing, dashboard,
  booking form và admin form tại 360/768/1024/1440 không có horizontal overflow; menu mobile và admin drawer
  xuất hiện đúng breakpoint.


| Slice | Trạng thái | Evidence |
|---:|---|---|
| 00 | Complete | Specification audit, 75-requirement RTM baseline, ERD/test/roadmap và static check |
| 00B | Complete | Locked decisions DEC-001..033, Closure Patch, ERD/test trace, Design System và docs synchronization |
| 01 | Complete | Composer install, PSR-4 autoload smoke test, PSR-12 lint, env safety và Docker Compose config |
| 02 | Complete | PDO native prepares, 6 migration/26 bảng nghiệp vụ, seed idempotent, reset an toàn và MySQL 8 integration test |
| 03 | Complete | Front Controller, router, Request/Response, session/CSRF, escaped View, PRG, request ID/error log và HTTP/security regression |
| 04 | Complete | Register/login/logout, BCRYPT, session lifecycle, guest/auth/role middleware, demo account và Auth/RBAC regression |
| 05 | Complete | Vehicle CRUD/deactivate, shared plate validation, active type, duplicate/ownership/IDOR và manual input UI |
| 06 | Complete | Catalog theo loại xe, admin service-price, slot validation, capacity từ active reservations và UI/RBAC/CSRF |
| 07 | Complete | Booking window theo tier, server pricing, multi-service/multi-slot snapshot, transaction/locking và concurrency test hai tiến trình |
| 08 | Complete | State matrix, customer cutoff 2 giờ/ownership, admin lifecycle/audit, capacity release, wash history và completion hook |
| 09 | Complete | Earn formula, ledger/cache, atomic completion, metrics/event, customer history, admin adjustment/audit/concurrency và reconcile |
| 10 | Complete | Generic credit/debit allocation, migration/backfill, reward CRUD/redemption, FEFO, expiry CLI và concurrency |
| 11 | Complete | Review tháng trước, spend AND visits, upgrade/downgrade/hold, history/reset, idempotency và recovery |
| 12 | Complete | Tier/perk/promotion admin, best-benefit pricing, reward use-once và limit concurrency |
| 13 | Complete | Safe upload ngoài public, provider interface/mock, confidence, owner-only image, attempt log và manual fallback |
| 14 | Complete | Sáu event type, CSV privacy allowlist, synthetic deterministic ≥2.000/four types, owner/admin dashboards và data dictionary |
| 15 | Complete | Security/business audit, service-price audit, 10k/20VU performance, clean setup, defense/release docs |

## Bugfix — Customer Loyalty Route Regression

- Nguồn: manual testing sau Slice 14 phát hiện menu Customer và dashboard cùng trỏ `/diem-thuong` nhưng
  production trả 404; `/admin/diem-thuong` vẫn hoạt động đúng.
- Nguyên nhân gốc: commit Slice 14 thêm `DashboardController` bằng nhánh `if/elseif` cho `/tai-khoan`, trong
  khi route `/diem-thuong` nằm chung nhánh `elseif` của `LoyaltyController`. Production inject cả hai factory
  nên chỉ nhánh dashboard chạy; test Slice 09 chỉ inject loyalty factory nên không tái hiện wiring production.
- Sửa route customer loyalty thành đăng ký độc lập khi có loyalty factory; giữ `/tai-khoan` dùng dashboard
  mới và giữ nguyên namespace/admin controller, CSRF, admin role. Không redirect customer sang Admin.
- Regression dùng đồng thời dashboard + loyalty factory như production; khóa guest `303`, customer `200`,
  owner-only khi query/path/POST tampering, customer route read-only, customer/admin RBAC, menu/dashboard link,
  empty state, escaping và dấu/nhãn earn/redeem/expire/adjust.
- Migration: không có; không đổi schema, transaction hoặc business rule.
- Verification: loyalty focused `8 test/94 assertion`; host PHP 8.5 và Docker PHP 8.2 cùng pass PHPCS 178 file,
  PHPUnit `169 test/920 assertion`, không skip, release audit pass. HTTP Apache smoke: guest loyalty `303` về
  login; customer loyalty `200`; customer admin loyalty `403`; admin loyalty `200` và adjustment form còn hoạt động.
- Rủi ro/Backlog: error renderer hiện không nhận session/auth context nên 404 của request đã đăng nhập vẫn
  render guest navigation. Đây là finding độc lập; sửa đúng cần thay contract/wiring `ErrorHandler` trên toàn
  application và test, nên không mở rộng vào bugfix route này. Error page hiện không làm lộ dữ liệu nhạy cảm.
- File thay đổi: `routes/web.php`, `LoyaltyFlowTest`, RTM, Test Plan, Demo Script và file status này.
- Commit đề xuất: `fix(LOY): khôi phục trang điểm thưởng khách hàng`.

## Business-rule correction — Service Group Selection Policy

- Nguồn: manual test và read-only domain audit trước defense phát hiện Standard + Premium có thể được chọn
  đồng thời vì catalog chỉ có service độc lập, UI dùng checkbox và backend chưa có combination invariant.
- Quyết định: DEC-035; `WASH_PACKAGE` chọn đúng một, `ADD_ON` chọn nhiều/không bắt buộc nhưng không đặt độc
  lập. Promotion/reward/perk tiếp tục target service ID, không có group targeting hoặc full group CRUD.
- Migration 010 tạo `service_groups`, thêm `services.service_group_id` FK bắt buộc, backfill bốn service và đưa
  capacity override hiện tại về null. Service ID, booking item/snapshot và research metadata lịch sử giữ nguyên.
- Admin service CRUD bắt buộc active group, list/form/audit hiển thị group/policy. Customer render radio/checkbox
  theo group; backend lock/tải group và validate trước pricing, duration, capacity, benefit, reservation/write.
- Regression khóa Standard/Premium/add-on/POST bypass/no-artifact, capacity default, inactive group,
  promotion/reward/perk theo service ID và legacy booking từng chứa cả hai package vẫn đọc được.
- Verification final: host PHP 8.5 và Docker PHP 8.2/MySQL 8.4 cùng pass PHPCS 178 file, PHPUnit
  168 test/897 assertion không skip và release audit. Fresh `autowash_test` reset/migrate/seed tạo 10 migration,
  2 group, 4 mapping, 0 override. HTTP smoke admin/customer pass; bypass trả 422 và artifact count không đổi.
- Migration in-place trên dữ liệu demo từ 9 lên 10 giữ nguyên booking ID 18 từng có Standard + Premium,
  toàn bộ item price/duration/capacity snapshot và research metadata trước/sau.

## Slice 15 — Final Hardening, Verification and Defense Release

- Requirements: đóng ADM-07/08 và NFR-01..03, NFR-05..11, NFR-15, NFR-17, NFR-19..23, NFR-25;
  tái xác minh toàn bộ functional/NFR MUST đã Done bằng code + test/demo evidence.
- Hoàn thành:
  - Security/layer review SQL injection, XSS, CSRF, session fixation, RBAC/IDOR, upload, secret/log,
    price/point tamper; thêm `release-audit.php` để gate MUST RTM, docs, SQL layer, superglobal, debug và secret.
  - Business review slot race, duplicate completion, redeem/adjust/promotion concurrency, expiry/monthly
    idempotency và transaction/network boundary; không phát hiện blocker/high còn mở.
  - Bổ sung service/price audit cùng transaction với actor/action/before/after/reason; regression xác minh
    create/update/deactivate/activate và snapshot giá, đóng ADM-08 mà không đổi schema/rule.
  - Demo seed thêm một earn lot đã hết hạn để rehearsal expiry lần đầu/lần hai có kết quả quan sát được, vẫn
    giữ lot sắp hết hạn và credit không hết hạn; seed rerun không phục hồi lot đã xử lý.
  - Tạo performance data/workload tái lập: 10.000 completed booking có item/reservation, 20 customer/vehicle/
    credit riêng; HTTP qua Apache cho login/catalog/slot/history/create/redeem/admin report.
  - Hoàn thiện README, demo script, đúng 30 câu defense Q&A, known limitations, performance report và release
    checklist/tag proposal; review responsive foundation/empty/error state và toàn bộ RTM.
- File thay đổi: service Controller–Service–Repository và Catalog/Database tests; demo seed; ba release/
  performance CLI; Composer/ignore; README; DEMO_SCRIPT, DEFENSE_QA, KNOWN_LIMITATIONS,
  PERFORMANCE_REPORT, RELEASE_CHECKLIST, RTM/Test Plan/Roadmap và file status này.
- Migration: không tạo; dùng nguyên 9 migration, không đổi ERD/schema/decision khóa.
- Test/evidence:
  - Baseline host `composer validate --strict` + `composer check`: lint pass; unit/feature pass, database skip khi
    chưa bật MySQL được ghi nhận và không dùng làm final evidence.
  - Docker PHP 8.2.32/MySQL 8.4 full `AUTOWASH_DB_TESTS=1 composer check`: 163 test/833 assertion, không skip;
    PHPCS/release audit pass.
  - Fresh reset/migrate/seed và seed rerun pass; expiry lần đầu 50 điểm/lần hai 0; monthly review lần đầu pass,
    lần hai bị chặn; loyalty reconcile khớp.
  - NFR-02: 10.000 booking/20 VU, 140 measured request, error 0%; P95 login 189,36 ms, service 96,49 ms,
    slot 139,00 ms, history 142,89 ms, create 203,33 ms, redeem 98,31 ms, report 69,17 ms — đều đạt target.
  - HTTP smoke Apache 8081 customer/admin/health và critical routes pass; research synthetic/export privacy
    acceptance pass; `git diff --check` và final scan pass.
- Kết quả: toàn bộ MUST trong RTM Done với evidence; không có blocker/high trong phạm vi release.
- Quyết định: giữ DEC-001..034; không thêm ADR, migration hoặc business rule.
- Known limitations: xem `docs/KNOWN_LIMITATIONS.md`; chính gồm external production LPR, performance local,
  single branch, không payment/refund, special review rerun chưa có và survey/ML/paper deferred bonus.
- Lệnh release: sau khi nhóm review commit, tạo annotated tag đề xuất `v1.0.0-defense`; không push/tag trong
  session Slice 15.
- Commit đề xuất: `chore(NFR): hoàn tất hardening và release bảo vệ [Slice 15]`.

## Slice 14 — Research Data, Synthetic Generator and Dashboards

- Requirements: hoàn tất RBL-01..04, REP-01/02, NFR-24; RBL-05 được checkpoint Q-020 khóa thành
  OPTIONAL/SHOULD, `Deferred bonus work`, non-blocking theo DEC-034.
- Hoàn thành:
  - Giữ `booking_created`/`booking_completed` đã có và nối `reward_redeemed`, `points_expired`,
    `tier_changed`, `promotion_used` vào transaction nghiệp vụ; mọi event dùng `event_key` unique và
    anonymous key, không có FK/user ID trực tiếp trong export.
  - Tạo CSV schema `1.0` với allowlist 22 cột, lọc `from/to/source`, UTF-8 và không xuất raw metadata/PII;
    data dictionary giải thích feature, nguồn, privacy boundary và giới hạn suy diễn.
  - Tạo generator deterministic theo seed; CLI acceptance từ chối count dưới 2.000, dataset thực chạy có
    2.000 record, đủ motorbike/car/truck/bus và `data_source=synthetic`.
  - Customer dashboard tải tier/point, booking gần nhất, wash history và reward đúng owner; admin dashboard
    có booking hôm nay, completed-only revenue, capacity utilization, tier distribution, earn/redeem/expire,
    reward/promotion usage và biểu đồ progress responsive cơ bản.
  - Q-020 đã Resolved trong DEC-034/ASSUMPTIONS/ROADMAP/Specification/RTM; không tạo survey result, ML,
    accuracy, p-value, hypothesis conclusion, paper hoặc external dataset.
- File thay đổi: Research/Dashboard Controller–Service–Repository; hook Booking/Loyalty/Reward/Tier;
  bootstrap/routes; customer/admin dashboard/CSS; hai CLI; unit/integration test; README, data dictionary,
  requirement changelog, Decisions/Assumptions/Roadmap/Specification/RTM/Test Plan và file status này.
- Migration: không tạo. `research_event_logs` và unique `event_key` đã tồn tại đúng schema khóa từ migration
  `006_create_operations_tables`; không thay schema hoặc business rule ngoài Slice 14.
- Test đã chạy:
  - `ResearchDataTest` — pass, 2 tests/11 assertions; deterministic seed, 2.000 record, four types, CSV
    privacy allowlist và UTF-8 row.
  - `ResearchFlowTest` — pass, 3 tests/18 assertions; operational event/anonymity, CSV columns/filter/privacy,
    completed-only revenue và owner scope.
  - Host PHP 8.5/MySQL 8.4 `composer check` — pass, PHPCS 174/174 file; PHPUnit 163 tests/826 assertions,
    không skip.
  - Docker PHP 8.2.32/MySQL 8.4 `composer check` với `AUTOWASH_DB_TESTS=1` — pass, PHPCS 174/174 file;
    PHPUnit 163 tests/826 assertions, không skip.
  - Synthetic acceptance CLI — pass: 2.000 record + header (2.001 dòng), cùng seed cho SHA-256 giống nhau,
    đủ bốn vehicle type; system export CLI chạy được với cả dataset 3 record và empty-state sau test reset,
    privacy header/content scan không có sensitive key.
  - HTTP Apache port 8081 — admin/customer login `303`, `/admin` và `/tai-khoan` đều `200`, có đúng nội dung
    dashboard mới.
- Kết quả: đạt acceptance Slice 14; chỉ thực hiện descriptive analytics và không mở survey/ML/paper/Slice 15.
- Quyết định: thêm DEC-034 để khóa Q-020; giữ DEC-025 minimum 2.000 và DEC-014 privacy/source boundary.
- Known Limitations/Future Enhancements:
  - External production LPR provider chưa có endpoint/model/credential/evidence; không còn blocker vì Slice 13
    đã hoàn thành adapter, mock offline và manual fallback.
  - Survey/ML/kiểm định chuyên sâu/paper là deferred bonus work; dataset hiện không chứng minh quan hệ nhân quả.
  - Performance/security/release hardening thuộc duy nhất Slice 15 và chưa được thực hiện trong session này.
- Lệnh chạy tiếp: sau khi nhóm accept Slice 14, thực hiện duy nhất Slice 15.
- Commit đề xuất: `feat(RBL): hoàn tất dữ liệu nghiên cứu và dashboard [Slice 14]`.

## Slice 13 — License Plate Recognition Adapter and Safe Upload

- Requirements: hoàn tất LPR-02 và NFR-16; giữ nguyên LPR-01; tiếp tục NFR-03, NFR-05, NFR-11..13,
  NFR-15, NFR-17, NFR-19 và NFR-23 trong trust boundary upload/recognition.
- Hoàn thành:
  - Tạo `LprProviderInterface`, typed `RecognitionResult` và `MockLprProvider`; mock offline trả text/confidence
    cấu hình được, được ghi rõ không phải model/OCR production và không có external adapter giả khi repository
    chưa có endpoint/model/secret.
  - Tạo `UploadedFile` + `LprUploadService`: kiểm tra upload error, kích thước thực, MIME bằng `fileinfo`,
    allowlist JPEG/PNG/WebP, tên ngẫu nhiên, quyền file hạn chế và bắt buộc lưu ngoài `public`.
  - Tạo `LprService`/`LprAttemptRepository`; network/provider call diễn ra trước DB write và ngoài transaction;
    success, failure/timeout, low-confidence và manual override đều có outcome rõ, attempt không ghi secret/PII
    ngoài dữ liệu cần thiết của flow.
  - Customer upload từ form thêm xe, xem ảnh qua protected controller route, nhận text/confidence rồi bắt buộc
    xác nhận hoặc sửa; `LicensePlateService`/`VehicleService` vẫn normalize, validate, unique và ownership như
    luồng thủ công. Failure/timeout không làm mất hoặc khóa manual form.
  - Migration `009_create_lpr_attempts` thêm bảng theo ERD với provider, confidence, status check, FK owner và
    index; không sửa schema/business rule ngoài Slice 13.
  - Docker dùng volume riêng cho logs/uploads và entrypoint cấp quyền tối thiểu để Apache ghi runtime mà không
    đưa ảnh vào bind mount/public/Git.
- Chưa hoàn thành tại thời điểm Slice 13: external/production LPR provider hoặc self-trained model;
  research CSV/dashboard Slice 14; hardening/performance/release Slice 15. Q-020 sau đó đã Resolved ở Slice 14.
- File thay đổi: LPR contract/provider/DTO/Service/Repository/exceptions; HTTP Request/VehicleController,
  bootstrap/routes/view/CSS; config/env/Compose entrypoint; migration 009; unit/integration/database test;
  README, Specification, RTM và file status này.
- Migration: `009_create_lpr_attempts`; fresh reset/migrate/seed và migration rerun đã có automated/CLI evidence.
- Test đã chạy:
  - `LprFlowTest` + database foundation — pass, 13 tests/93 assertions; MIME/size/public-path, success,
    low-confidence, failure, timeout, manual override, duplicate, CSRF, owner image/IDOR và migration.
  - Host PHP 8.5/MySQL 8.4 `composer check` — pass, PHPCS 162/162 file; PHPUnit 158 tests/794 assertions,
    không skip.
  - Docker PHP 8.2.32/MySQL 8.4 `composer check` — pass, PHPCS 162/162 file; PHPUnit 158 tests/794 assertions,
    không skip.
  - Fresh Docker reset/migrate/seed — pass; HTTP Apache port 8081: login `303`, multipart recognition `200`,
    protected image `200`, confirm/manual override save `303`.
- Kết quả: đạt toàn bộ acceptance Slice 13; manual input độc lập, upload/ownership an toàn và full regression
  host/container đều pass.
- Quyết định: giữ nguyên DEC-001..033; thêm field `provider` đúng ERD/DEC-024 vào Specification, không đổi
  decision hoặc business rule đã khóa.
- Rủi ro còn lại: mock chỉ chứng minh adapter/flow offline; chất lượng OCR, latency và credential của provider
  thật chưa có evidence và không được tuyên bố production-ready.
- Lệnh chạy tiếp: sau khi accept Slice 13 và checkpoint Q-020, thực hiện duy nhất Slice 14.
- Commit đề xuất: `feat(LPR): hoàn tất adapter nhận diện và upload an toàn [Slice 13]`.

## Slice 12 — Tier Configuration, Perks, Promotions and Checkout Integration

- Requirements: hoàn tất BKG-03, BKG-05, BKG-06, RWD-03, RWD-04, PRO-01..05, ADM-01 và ADM-05;
  tiếp tục ADM-08, NFR-03, NFR-05, NFR-09..13, NFR-15, NFR-17 và NFR-19..23.
- Hoàn thành:
  - Admin CRUD/inactivate tier rule, tier perk và promotion qua Controller–Service–Repository–View; backend
    validate code/rank/ngưỡng/rate, type/value/time/limit và target tier/service/vehicle type; thay đổi config
    ghi audit log cùng transaction.
  - Seed Silver+ map bằng ba quan hệ Silver/Gold/Platinum; target rỗng nghĩa là không giới hạn theo dimension.
  - `PromotionService` tải và khóa config trong transaction booking, kiểm tra active/time/tier/minimum/scope,
    reserve total/per-user limit bằng cả usage đã hoàn thành và booking pending/confirmed.
  - `PriceCalculator` dùng integer cents/decimal string, chọn một perk tốt nhất, một promotion discount lớn
    nhất với tie-break end sớm, và tối đa một reward đúng owner/service/vehicle type; từng discount được cap
    để final không âm và snapshot vào booking.
  - Reward redemption được gắn duy nhất vào booking khi checkout; complete atomically ghi promotion usage,
    chuyển reward `used` và research flags; cancel/no-show trả reward `available` nếu còn hạn hoặc `expired`.
  - Migration `008_add_reward_percentage_cap` thêm `rewards.max_discount` nullable để hoàn tất RWD-03;
    seeder tương thích cả schema legacy trước migration 008 trong test backfill.
  - Concurrency promotion limit dùng locking/current read; regression hai process chứng minh chỉ một booking
    giữ lượt promotion cuối dưới MySQL `REPEATABLE READ`.
- Chưa hoàn thành: LPR/upload Slice 13; research export/dashboard Slice 14; hardening/performance/release Slice
  15; audit service-price và special monthly-review rerun còn trong phần ADM-08 tiếp theo.
- File thay đổi: pricing/booking/promotion/tier/reward Controller–Service–Repository–Validator/DTO; migration
  008; bootstrap/routes/views/layout; seed; unit/integration/concurrency test; README, Spec, ERD, RTM và status.
- Migration: `008_add_reward_percentage_cap`; không tạo entity/association mới và không đổi decision khóa.
- Test đã chạy:
  - `BookingRulesTest` — pass, 9 tests/33 assertions; pricing best/tie/cap/final-zero có evidence.
  - `BookingFlowTest` benefit filters — pass, checkout snapshot, complete usage/research, cancel restore và
    concurrency promotion limit hai process.
  - `PromotionConfigurationFlowTest` — pass, 3 tests/55 assertions; tier/perk/promotion validation, Silver+
    eligibility, boundary time/minimum/scope, reward ownership/type, duplicate rank và audit.
  - Database foundation/backfill — pass, 10 tests/43 assertions; fresh migration 008 và legacy seeder.
  - Host PHP 8.5/MySQL 8.4 `composer check` — pass, PHPCS 149/149 file; PHPUnit 151 tests/729 assertions.
  - Docker PHP 8.2.32/MySQL 8.4 `composer check` — pass, PHPCS 149/149 file; PHPUnit 151 tests/729 assertions.
  - Fresh Docker reset/migrate/seed — pass. HTTP Apache port 8081: admin login `303`, tier page `200`,
    promotion page `200`; customer login `303`, checkout `200` và hiển thị reward selector.
- Kết quả: đạt toàn bộ acceptance Slice 12; không có test skip/fail trong full DB suite.
- Quyết định: giữ DEC-001..033; migration 008 chỉ lấp field cấu hình đã được RWD-03 yêu cầu, không đổi rule.
- Rủi ro còn lại: promotion service target được hiểu là booking đủ điều kiện khi có ít nhất một service mục
  tiêu; discount promotion vẫn tính trên subtotal toàn booking theo mô hình order-level hiện tại.
- Lệnh chạy tiếp: sau khi accept Slice 12, thực hiện duy nhất Slice 13.
- Commit đề xuất: `feat(PRO): hoàn tất quyền lợi và khuyến mãi checkout [Slice 12]`.

## Slice 11 — Monthly Tier Review and Tier History

- Requirements: hoàn tất TIER-01, TIER-02, TIER-03 và TIER-04; tiếp tục NFR-03, NFR-05, NFR-09,
  NFR-11, NFR-12, NFR-15, NFR-17, NFR-19 và NFR-21..23.
- Hoàn thành:
  - Tạo `TierReviewPolicy` chọn tháng lịch vừa kết thúc theo `Asia/Ho_Chi_Minh`; qualification dùng spend
    AND visits và chọn tier active có `rank_order` cao nhất, toàn bộ threshold tải từ database.
  - Tạo `TierRepository` và `TierReviewService`; mỗi customer được lock và xử lý trong transaction riêng:
    snapshot old/new tier + metrics, insert history unique, update current tier và reset metrics atomically;
    `point_balance` không nằm trong câu UPDATE nên được giữ nguyên.
  - Hỗ trợ nâng, hạ nhiều bậc và giữ hạng; reason tiếng Việt lưu cùng history để giải thích kết quả.
  - `monthly_review_runs` có running/completed/failed; advisory lock theo period ngăn hai batch cùng chạy;
    completed run bị chặn. Failed/stale running run có thể resume, bỏ qua user đã có history và cập nhật
    `processed_users` theo tổng history đã commit.
  - Tạo CLI `scripts/monthly-review.php`; mặc định không nhận period từ frontend và xét tháng vừa kết thúc.
  - Admin xem run/history tại `/admin/xet-hang` qua auth + role middleware; view có empty/status/error state,
    responsive table foundation và escaped output.
  - Fresh seed tạo bốn kịch bản nâng nhiều bậc, giữ boundary, hạ nhiều bậc và giữ tier cao nhất; seed chạy
    lại không phục hồi current tier hoặc monthly metrics đã được review.
- Chưa hoàn thành: CRUD tier rule/perk/promotion và checkout integration thuộc Slice 12; không triển khai
  special admin rerun, LPR, research dashboard/export hoặc hardening.
- File thay đổi: Tier Controller/Service/Policy/Repository/exceptions; bootstrap/routes/admin view/layout;
  monthly review CLI; demo seed; unit/integration test; README, RTM và file status này.
- Migration: không có; dùng nguyên `tiers`, `users`, `monthly_review_runs` và `tier_histories` từ migration
  001/006. Không đổi ERD, schema hoặc decision.
- Test đã chạy:
  - `vendor/bin/phpunit tests/Unit/TierRulesTest.php` — pass, 3 tests/7 assertions.
  - MySQL `TierReviewFlowTest` — pass, 3 tests/50 assertions, gồm failure recovery, seed safety và HTTP
    RBAC/XSS.
  - Host MySQL full `composer check` — pass, PHPCS 138/138 file; PHPUnit 143 tests/635 assertions,
    không skip.
  - Docker PHP 8.2.32/MySQL 8.4 full `composer check` — pass, PHPCS 138/138 file; PHPUnit 143
    tests/635 assertions, không skip.
  - Fresh reset/migrate/seed + `monthly-review.php` — pass, xử lý 4 customer cho kỳ `2026-06`; chạy lần
    hai trả exit 1 và thông báo run completed không thể chạy lại; reconcile loyalty vẫn `KHỚP` toàn bộ.
  - HTTP smoke Apache port 8081 — admin login `303`, `/admin/xet-hang` `200`, thấy kỳ `2026-06` và trạng
    thái `Hoàn tất`.
- Kết quả: đạt toàn bộ acceptance Slice 11; full regression host/container, CLI fresh database và HTTP route
  thật đều có evidence thành công.
- Quyết định: giữ nguyên DEC-001..033; transaction theo từng customer để recovery không rollback các user
  đã hoàn tất, còn unique history bảo đảm resume không reset lặp.
- Rủi ro còn lại: schema baseline chỉ lưu một started/completed timestamp cho mỗi period, không lưu từng
  attempt resume; special admin rerun có audit thuộc requirement tương lai và chưa được mở trong Slice 11.
- Lệnh chạy tiếp: sau khi accept Slice 11, thực hiện duy nhất Slice 12.
- Commit đề xuất: `feat(TIER): hoàn tất xét hạng hàng tháng và lịch sử [Slice 11]`.

## Slice 10 — Generic Credit Lots, Reward Redemption and Point Expiry

- Requirements: hoàn tất LOY-02, LOY-03, LOY-04, RWD-01, RWD-02 và ADM-04; phần Slice 10 của RWD-04
  và REP-01; tiếp tục NFR-03, NFR-05, NFR-09, NFR-11..13, NFR-15, NFR-17 và NFR-19..23.
- Hoàn thành:
  - Theo phê duyệt blocker Slice 10, sửa DEC-011/018/032: credit transaction gồm `earn` và
    `adjust_credit`; debit gồm `redeem`, `expire`, `adjust_debit`; mọi debit bắt buộc có generic allocation.
  - Migration `007_generalize_loyalty_credit_lots` đổi `earn_transaction_id`/`points_allocated` thành
    `credit_transaction_id`/`allocated_points`, sửa type/check/FK/index và giữ unique debit+credit.
  - Preflight migration kiểm tra ledger/cache và mô phỏng lịch sử; backfill positive adjustment thành
    non-expiring credit lot, negative adjustment thành FEFO debit; dữ liệu không thể phân bổ làm migration
    fail rõ với transaction ID, không clamp hoặc âm thầm sửa balance.
  - `LoyaltyDebitAllocator` và LoyaltyService giữ đồng thời cache = ledger net = tổng remaining credit lots;
    adjustment âm, redeem và expiry lock/allocate/update cache trong cùng transaction.
  - FEFO dùng lot có expiry sớm nhất, tie-break `created_at,id`; non-expiring adjustment credit dùng sau cùng
    theo FIFO. Expiry chỉ trừ phần remaining của earn lot và chạy lại không tạo debit/allocation trùng.
  - Tạo `LoyaltyExpirationPolicy` cho 12 calendar months có leap-day clamp và CLI `expire-points.php`.
  - Tạo RewardRepository/RewardService/RewardValidator, customer/admin controllers, route và UI tiếng Việt;
    customer xem reward đủ hạng, redeem atomically và chỉ xem redemption của mình; admin CRUD/inactivate
    cùng tier/service/vehicle restriction, RBAC, CSRF, validation và escaped output.
  - Seed Member có earn lot 150 điểm sắp hết hạn trong 20 ngày + 250 adjustment credit không expiry; Gold
    có 800 adjustment credit để demo reward/FEFO. Seeder idempotent không phục hồi lot đã tiêu thụ.
  - Reconcile CLI kiểm tra ba balance invariant và tổng allocation của từng debit.
- Chưa hoàn thành: áp reward vào checkout, service/vehicle restriction khi sử dụng, use-once/used/cancel
  restore và percentage cap thuộc Slice 12; monthly tier review thuộc Slice 11. RWD-04/REP-01 tổng thể còn
  `In Progress`; không triển khai promotion/perk hoặc Slice 11.
- File thay đổi: migration 007; loyalty/reward Controller/Service/Repository/Validator/exceptions; bootstrap,
  routes, views/layout; expiry/reconcile CLI; demo seed; unit/integration/migration/concurrency tests; README,
  Decisions, ERD, Specification, RTM, Test Plan và file status này.
- Migration: có `007_generalize_loyalty_credit_lots`; fresh reset và legacy positive/negative adjustment
  backfill đều có automated evidence. Không tạo entity/table mới ngoài schema loyalty đã duyệt.
- Test đã chạy:
  - `vendor/bin/phpunit tests/Unit/RewardRulesTest.php` — pass, 5 tests/15 assertions.
  - Migration + Loyalty + Reward integration — pass, 17 tests/148 assertions.
  - `AUTOWASH_DB_TESTS=1 ... composer check` trên host PHP 8.5/MySQL 8.4 — pass, PHPCS 129/129 file;
    PHPUnit 137 tests/578 assertions, không skip.
  - `docker compose exec -T -e AUTOWASH_DB_TESTS=1 web composer check` trên PHP 8.2.32/MySQL 8.4 — pass,
    PHPCS 129/129 file; PHPUnit 137 tests/578 assertions, không skip.
  - Fresh reset/migrate/seed, `expire-points.php` chạy hai lần theo automated test và reconcile CLI thật —
    cache, ledger net, credit lot balance và debit allocations đều `KHỚP`.
  - HTTP smoke Apache port 8081 — customer login/reward/redeem `303/200/303`; admin login/reward/create
    `303/200/303`; parser CSRF smoke ban đầu được sửa để lấy đúng một token trước khi kết luận.
- Kết quả: đạt acceptance riêng Slice 10; migration/backfill, generic allocation, reward redemption, expiry,
  adjustment và concurrency có evidence thật.
- Quyết định: DEC-011/018/032 được sửa đúng phê duyệt blocker; không phát sinh thay đổi ngoài loyalty Slice 10.
- Rủi ro còn lại: reward redemption mới ở trạng thái `available`; checkout use-once và restore khi cancel phải
  hoàn tất ở Slice 12. Migration DDL của MySQL không transactional hoàn toàn, nên preflight fail-fast chạy
  trước mọi ALTER để ngăn dữ liệu lịch sử không thể backfill.
- Lệnh chạy tiếp: sau khi accept Slice 10, thực hiện duy nhất Slice 11.
- Commit đề xuất: `feat(LOY): hoàn tất credit lot, đổi thưởng và hết hạn điểm [Slice 10]`.

## Slice 09 — Loyalty Ledger, Earn Points and Completion Integration

- Requirements: hoàn tất LOY-01 và ADM-06; phần Slice 09 của LOY-02, BKG-06 và REP-01; tiếp tục NFR-03, NFR-05, NFR-09..13, NFR-15, NFR-17, NFR-19..23.
- Hoàn thành:
  - Tạo `LoyaltyPointCalculator` dùng số nguyên/decimal string cho công thức floor hai bước; tải `point_rate` từ tier trong DB, giá 0 vẫn ghi earn 0 để giữ idempotency.
  - Tạo `LoyaltyTransactionRepository` và `LoyaltyService`; completion lock user sau booking, cập nhật status/completed_at, monthly spend/visits, earn lot, point balance, marker và research event trong cùng transaction.
  - Unique source booking ngăn earn lặp; lỗi insert loyalty rollback trạng thái completed, metrics, balance, marker và event.
  - Earn lot có `earned_at`, expiry 12 calendar months có clamp để customer xem tổng điểm sắp hết hạn trong 30 ngày; job expire/FEFO chưa chạy trước Slice 10.
  - Customer dashboard và `/diem-thuong` hiển thị tier, rate, balance, expiry 30 ngày và lịch sử đúng owner; có empty state, responsive và escaped output.
  - Admin `/admin/diem-thuong` điều chỉnh số nguyên khác 0 với reason bắt buộc, optional source transaction đúng owner; ledger/cache/audit cùng transaction, âm vượt balance bị reject không clamp.
  - Adjustment khóa user bằng `FOR UPDATE`; test hai tiến trình trừ 80 từ balance 100 cho đúng một thành công, balance cuối 20.
  - Tạo `scripts/reconcile-loyalty.php` đối chiếu tổng ledger với cached balance và trả exit code lỗi khi lệch.
- Chưa hoàn thành: redeem/FEFO allocations và expiry command thuộc Slice 10; reward eligibility/use thuộc Slice 10/12; promotion/reward usage khi completion thuộc Slice 12 nên BKG-06/LOY-02 tổng thể vẫn `In Progress`.
- File thay đổi: loyalty Controller/Service/Repository/Calculator/Validator/exception; booking completion wiring/message; bootstrap/routes; customer/admin loyalty views, dashboard/layout/CSS; reconcile CLI; unit/integration/concurrency tests; README, RTM và file status này.
- Migration: không có; dùng nguyên schema `users`, `bookings`, `loyalty_transactions`, `research_event_logs` và `audit_logs` từ Slice 02.
- Test đã chạy:
  - `vendor/bin/phpunit tests/Unit/LoyaltyRulesTest.php` — pass, 9 tests/12 assertions.
  - `AUTOWASH_DB_TESTS=1 ... vendor/bin/phpunit tests/Integration/Loyalty/LoyaltyFlowTest.php` — pass, 7 tests/72 assertions.
  - `AUTOWASH_DB_TESTS=1 ... composer check` trên host PHP 8.5/MySQL 8.4 — pass, PHPCS 115/115 file; PHPUnit 122 tests/487 assertions, không skip.
  - `docker compose exec -T -e AUTOWASH_DB_TESTS=1 web composer check` trên PHP 8.2.32/MySQL 8.4 — pass, PHPCS 115/115 file; PHPUnit 122 tests/487 assertions, không skip.
  - `php scripts/reconcile-loyalty.php` — pass, bốn customer demo đều ledger khớp cache.
  - HTTP smoke Apache port 8081 — admin login/trang điểm/adjust lần lượt 303/200/303; customer login/trang điểm 303/200 và thấy balance 25 điểm vừa điều chỉnh.
- Kết quả: đạt acceptance riêng của Slice 09; completion/loyalty atomic, earn idempotent, adjustment/audit/concurrency và customer history có evidence thật.
- Quyết định: giữ nguyên DEC-001..033; không phát sinh ADR, migration hoặc schema mới.
- Rủi ro còn lại: positive adjustment là ledger credit không có expiry/remaining lot; cách phân bổ credit này khi redeem cần tuân theo thiết kế Slice 10 mà không làm sai ledger/cache. Các booking completed trước khi deploy Slice 09 có marker null không được tự động backfill âm thầm.
- Lệnh chạy tiếp: sau khi accept Slice 09, thực hiện duy nhất Slice 10.
- Commit đề xuất: `feat(LOY): hoàn tất ledger và tích điểm nguyên tử [Slice 09]`.

## Slice 08 — Booking Lifecycle, Completion and Wash History

- Requirements: hoàn tất BKG-04; phần Slice 08 của BKG-05, BKG-06 và REP-01; tiếp tục NFR-03, NFR-05, NFR-09, NFR-11, NFR-12, NFR-13, NFR-15, NFR-17, NFR-19, NFR-21 và NFR-22.
- Hoàn thành:
  - Tạo `BookingLifecyclePolicy` làm nguồn duy nhất cho matrix `pending -> confirmed|cancelled`, `confirmed -> completed|cancelled|no_show`; trạng thái kết thúc không chuyển tiếp và trả domain error.
  - Customer xem danh sách, chi tiết đúng owner và wash history chỉ gồm booking `completed`; item name/price/duration/capacity lấy từ snapshot, không bị thay đổi theo cấu hình catalog mới.
  - Customer chỉ hủy booking pending/confirmed của mình khi còn ít nhất 2 giờ theo `Asia/Ho_Chi_Minh`; đúng boundary 2 giờ được phép, dưới 2 giờ bị từ chối và IDOR trả 404 an toàn.
  - Admin xem danh sách và thực hiện confirm, complete, cancel, no-show qua route role-guard + CSRF; admin cancel bắt buộc lý do và ghi `audit_logs` trong cùng transaction.
  - Mọi mutation lock booking bằng `FOR UPDATE`; cancelled/no-show giữ reservation để audit nhưng tự nhiên không còn được tính vào capacity active.
  - Complete chỉ từ confirmed, ghi `completed_at`; request complete lặp bị từ chối. `BookingCompletionProcessorInterface` chạy trong cùng transaction khi được inject và failure rollback trạng thái, sẵn sàng cho LoyaltyService Slice 09.
  - Slice 08 không inject completion processor nên `loyalty_processed_at` vẫn `NULL` làm marker chưa xử lý; không cập nhật point balance, monthly spend/visits, promotion/reward usage hoặc research completion event giả.
  - UI `/lich-dat`, `/lich-dat/{id}` và `/admin/lich-dat` bằng tiếng Việt, responsive, có status badge, empty/error/success state, form hủy có lý do và output escaping.
- Chưa hoàn thành: completion + loyalty/metrics/research event thuộc Slice 09/14; reward restore và promotion/reward usage khi cancel/complete thuộc Slice 12. Do đó BKG-05, BKG-06 và REP-01 tổng thể vẫn `In Progress` trong RTM.
- File thay đổi: lifecycle contract/policy/validator/exceptions; booking customer/admin Controller, Service, Repository; bootstrap/routes/logger; customer/admin booking views, layout/CSS; unit/integration test; README, RTM và file status này.
- Migration: không có; dùng nguyên `bookings`, `booking_items`, `booking_slot_reservations` và `audit_logs` từ Slice 02, không đổi schema/ERD.
- Test đã chạy:
  - `vendor/bin/phpunit tests/Unit/BookingLifecyclePolicyTest.php` — pass, 13 tests/13 assertions.
  - Bộ booking unit + MySQL integration Slice 07–08 — pass, 38 tests/139 assertions.
  - `AUTOWASH_DB_TESTS=1 ... composer check` trên host PHP 8.5/MySQL 8.4 — pass, PHPCS 104/104 file; PHPUnit 106 tests/403 assertions, không skip.
  - `docker compose exec ... composer check` trên PHP 8.2.32/MySQL 8.4 — pass, PHPCS 104/104 file; PHPUnit 106 tests/403 assertions, không skip.
  - HTTP smoke Apache port 8081 — customer login/list/detail/cancel lần lượt 303/200/200/303; admin login/list/confirm/complete 303/200/303/303; customer owner mở history 200 và thấy booking snapshot completed.
- Kết quả: đạt acceptance riêng của Slice 08; lifecycle transaction, boundary, ownership, capacity, audit, wash history và completion extension point có evidence thật.
- Quyết định: giữ nguyên DEC-001..033; không phát sinh ADR, migration, schema hoặc business rule mới.
- Rủi ro còn lại: completion hiện cố ý chưa atomic với loyalty cho đến Slice 09; booking đã completed ở Slice 08 mang marker `loyalty_processed_at = NULL` và không được diễn giải là đã cộng điểm. Reward chưa được gắn vào booking trước Slice 12 nên nhánh restore khi cancel chưa thể hoàn tất.
- Lệnh chạy tiếp: sau khi accept Slice 08, thực hiện duy nhất Slice 09.
- Commit đề xuất: `feat(BKG): hoàn tất vòng đời booking và lịch sử rửa xe [Slice 08]`.

## Slice 07 — Booking Creation, Tier Window, Pricing and Capacity Concurrency

- Requirements: hoàn tất VEH-03, CAT-02, SLOT-01, SLOT-02, BKG-01, BKG-02, BKG-07; phần Slice 07 của BKG-03; tiếp tục NFR-03, NFR-05, NFR-09, NFR-10, NFR-11, NFR-13, NFR-15, NFR-19, NFR-20, NFR-21, NFR-22 và NFR-23.
- Hoàn thành:
  - Tạo `BookingController`, `BookingService`, `BookingRepository`, DTO/validator, `BookingWindowPolicy`, `PriceCalculator` và `BookingResourceCalculator` đúng phân tầng.
  - Customer chỉ chọn vehicle active thuộc sở hữu; backend khóa lại vehicle, tải tier/window và cấu hình service–vehicle type active/supported từ DB trong transaction.
  - Boundary booking window dùng `Asia/Ho_Chi_Minh`: ngày quá khứ bị từ chối, bằng đúng 7/10/12/14 ngày được phép, vượt giới hạn bị từ chối.
  - Subtotal/final lấy từ DECIMAL string ở DB; discount Slice 07 bằng 0; mọi price/duration/capacity giả từ client bị bỏ qua. Item snapshot giữ tên, loại xe, giá, duration và capacity.
  - Multi-service cộng duration, lấy capacity lớn nhất giữa vehicle default và các override; tìm và khóa mọi slot chồng lấn theo thứ tự ổn định, yêu cầu coverage liên tục và tất cả slot open/đủ capacity.
  - Tính usage bằng locking current-read sau slot lock để request chờ thấy commit mới nhất dưới MySQL `REPEATABLE READ`; hai tiến trình tranh unit cuối cho đúng một thành công.
  - Chặn vehicle có booking `pending|confirmed` chồng lấn; booking, items, reservations và `booking_created` research event được commit/rollback cùng nhau.
  - Tạo UI `/dat-lich` tiếng Việt theo Design System, PRG/flash, state không có xe/dịch vụ/slot, slot full/outside-window, responsive và output escaping; route customer-only + CSRF.
  - Seed idempotent trong cùng ngày tạo ba slot liên tục tại các mốc `+1`, `+8`, `+11`, `+13` ngày để demo tier window và multi-slot.
- Chưa hoàn thành: confirm/cancel/complete/history thuộc Slice 08; loyalty thuộc Slice 09; reward/promotion/perk thật thuộc Slice 10–12. Vì vậy BKG-03 giữ `In Progress`, ba discount hiện lưu 0 đúng phạm vi Slice 07.
- File thay đổi: booking Controller/DTO/Service/Repository/Validator/exceptions/calculators; bootstrap/routes; booking/dashboard/layout view và CSS; seeder/base seed; unit/integration/concurrency worker test; README, RTM và file status này.
- Migration: không có; dùng nguyên schema `bookings`, `booking_items`, `booking_slot_reservations` và `research_event_logs` từ Slice 02.
- Test đã chạy:
  - `vendor/bin/phpunit tests/Unit/BookingRulesTest.php` — pass, 8 tests/23 assertions.
  - Bộ Slice 07 unit + integration MySQL — pass, 17 tests/66 assertions.
  - `AUTOWASH_DB_TESTS=1 ... composer check` trên host PHP 8.5/MySQL 8.4 — pass, PHPCS 95/95 file; PHPUnit 85 tests/330 assertions, không skip.
  - `docker compose exec ... composer check` trên PHP 8.2.32/MySQL 8.4 — pass, PHPCS 95/95 file; PHPUnit 85 tests/330 assertions, không skip.
  - HTTP smoke Apache port 8081 — guest `/dat-lich` nhận 303 về đăng nhập; customer login 303, mở trang đặt lịch 200 và tạo booking thật nhận 303 PRG.
- Kết quả: đạt acceptance Slice 07; full regression pass trên host và container PHP 8.2, HTTP flow thật hoạt động.
- Quyết định: giữ nguyên DEC-001..033; không phát sinh ADR, migration, schema hoặc business rule mới.
- Rủi ro còn lại: booking code dùng random 128-bit không có retry riêng cho xác suất collision cực thấp; seed slot tương đối idempotent trong cùng ngày và môi trường demo nên nên reset trước buổi demo để không giữ slot tương đối của ngày chạy cũ.
- Lệnh chạy tiếp: sau khi accept Slice 07, thực hiện duy nhất Slice 08.
- Commit đề xuất: `feat(BKG): hoàn tất tạo booking và khóa sức chứa [Slice 07]`.

## Slice 06 — Service Catalog and Wash Slot Capacity

- Requirements: CAT-01, phần Slice 06 của CAT-02 và SLOT-01, ADM-02, ADM-03; tiếp tục NFR-03, NFR-05, NFR-09, NFR-10, NFR-11, NFR-12, NFR-13, NFR-15 và NFR-19.
- Hoàn thành:
  - Tạo `ServiceCatalogRepository`, `ServiceCatalogService`, `ServiceCatalogValidator`, controller customer/admin và UI tiếng Việt theo Design System.
  - Customer/guest xem catalog theo loại phương tiện; query chỉ trả service, cặp giá và vehicle type active, supported; giá, thời lượng và capacity effective lấy từ database.
  - Admin tạo/sửa/kích hoạt/ngừng dịch vụ và bốn cấu hình theo loại xe; lưu service + price pairs atomically, unique code/pair và CHECK constraint được chuyển thành lỗi nghiệp vụ phù hợp.
  - Tạo `WashSlotRepository`, `WashSlotService`, `WashSlotValidator`, controller customer/admin và UI; admin tạo/đóng slot, backend từ chối ngày quá khứ, time range sai, capacity không dương và slot trùng.
  - Remaining capacity được tổng hợp từ `booking_slot_reservations` của booking `pending|confirmed`; cancelled không chiếm chỗ, closed/past không xuất hiện ở danh sách customer.
  - Seed idempotent thêm hai booking/reservation fixture `DEMO_NEAR_FULL` và `DEMO_FULL` để tạo slot gần đầy/đầy; fixture có booking item snapshot hợp lệ và không cung cấp chức năng tạo booking trước Slice 07.
  - Route mutation yêu cầu admin + CSRF; route slot customer yêu cầu authenticated customer; output động được escape, UI có empty/error/full/inactive state và responsive table/card.
- Chưa hoàn thành: tạo booking, tính multi-service duration/capacity, lock mọi slot overlap và concurrency thuộc Slice 07; vì vậy CAT-02/SLOT-01 tổng thể giữ `In Progress`, SLOT-02 chưa bắt đầu.
- File thay đổi: catalog/slot Controller, Service, Repository, Validator/exceptions/formatter; bootstrap/routes; customer/admin views và CSS; seeder/base seed; test unit/integration/database; README, RTM và file status này.
- Migration: không có; dùng nguyên schema `services`, `service_vehicle_prices`, `wash_slots`, `bookings`, `booking_items` và `booking_slot_reservations` từ Slice 02.
- Test đã chạy:
  - `vendor/bin/phpunit tests/Unit/ServiceCatalogValidatorTest.php tests/Unit/WashSlotValidatorTest.php` — pass, 4 tests/16 assertions.
  - Bộ Slice 06 gồm unit + integration MySQL — pass, 11 tests/57 assertions.
  - `AUTOWASH_DB_TESTS=1 ... composer check` trên host PHP 8.5/MySQL 8.4 — pass, PHPCS 79/79 file; PHPUnit 68 tests/264 assertions, không skip.
  - `docker compose exec ... composer check` trên PHP 8.2.32/MySQL 8.4 — pass, PHPCS 79/79 file; PHPUnit 68 tests/264 assertions, không skip.
  - HTTP smoke Apache port 8081 — catalog theo ô tô trả 200 và giá DB `200.000 ₫`; guest vào `/khung-gio` nhận 303 về đăng nhập.
  - `composer validate --strict`, strict PSR autoload, `composer audit`, `git diff --check` và scan secret/TODO/debug/SQL trong Controller/View — pass.
- Kết quả: đạt acceptance Slice 06; full regression pass trên host và container PHP 8.2.
- Quyết định: giữ nguyên DEC-001..033; không phát sinh ADR, migration, schema hoặc business rule mới.
- Rủi ro còn lại: slot demo dùng ngày cố định 15/01/2030; sau thời điểm đó cần cập nhật demo dataset trong Slice 15. Booking fixture chỉ chứng minh capacity read model, chưa chứng minh race/rollback khi tạo booking.
- Lệnh chạy tiếp: sau khi accept Slice 06, thực hiện duy nhất Slice 07.
- Commit đề xuất: `feat(CAT): hoàn tất danh mục dịch vụ và khung giờ [Slice 06]`.

## Slice 05 — Vehicle Management and Plate Validation

- Requirements: VEH-01, VEH-02, VEH-03 phần view/edit ownership, VEH-04, LPR-01; tiếp tục NFR-03, NFR-05, NFR-11, NFR-12, NFR-13, NFR-15 và NFR-19.
- Hoàn thành:
  - Tạo `VehicleRepository`, `VehicleService`, `VehicleController` và `VehicleValidator` đúng phân tầng; mọi query có input dùng prepared statement.
  - Tạo `LicensePlateService` dùng chung cho ứng dụng và seeder: uppercase, bỏ khoảng trắng/`-`/`.` và kiểm tra pattern dân sự thông dụng; loại trừ series đặc biệt `NG`/`NN`/`QT` nằm ngoài baseline.
  - Lưu `display_plate`, so trùng bằng `normalized_plate`; chuyển unique violation thành `DuplicateLicensePlateException` với thông báo nghiệp vụ.
  - Kiểm tra `vehicle_type_id` tồn tại/active từ DB; seed bốn customer/tier và bốn xe motorbike/car/truck/bus theo DEC-015/031.
  - Customer list, thêm, sửa và ngừng sử dụng xe qua UI tiếng Việt responsive; deactivate giữ record, không hard-delete.
  - Route vehicle yêu cầu authenticated customer; mutation qua POST + CSRF; mọi read/update/deactivate query đều ràng buộc owner, IDOR trả 404 an toàn và không lộ xe khác.
  - Có empty, validation, success và inactive state; output động được escape, form giữ input khi lỗi và có validation frontend hỗ trợ UX.
- Chưa hoàn thành: ownership khi chọn xe để booking thuộc Slice 07 nên VEH-03 tổng thể giữ `In Progress`; upload ảnh/provider/`lpr_attempts` thuộc Slice 13; không có admin CRUD vehicle type hoặc reactivation vì ngoài Slice 05.
- File thay đổi: vehicle Controller/Service/Repository/Validator/exceptions, bootstrap/routes, vehicle views/layout/dashboard/CSS, seeder/base seed, test unit/integration/database, README, RTM và file status này.
- Migration: không có; dùng nguyên schema `vehicle_types`/`vehicles` và unique/FK của Slice 02, không đổi ERD hoặc decision.
- Test đã chạy:
  - `composer dump-autoload --strict-psr --no-interaction` — pass.
  - `composer lint` — pass, PHPCS 63/63 file.
  - `vendor/bin/phpunit tests/Unit/LicensePlateServiceTest.php tests/Feature tests/Security` — pass, 19 tests/63 assertions ở lần chạy đầu sau sửa pattern.
  - `AUTOWASH_DB_TESTS=1 ... vendor/bin/phpunit tests/Integration/Vehicle` trên MySQL 8.4 host port 3307 — pass, 6 tests/21 assertions trước self-review; bộ Slice 05 sau self-review pass 17 tests/46 assertions.
  - `AUTOWASH_DB_TESTS=1 ... composer check` trên host PHP 8.5/MySQL 8.4 — pass, 57 tests/207 assertions, không skip.
  - `composer check` trong container PHP 8.2.32/MySQL 8.4 — pass, 57 tests/207 assertions, không skip.
  - HTTP smoke Apache port 8081 — login 303, danh sách owner 200, create hợp lệ 303 PRG và xe mới xuất hiện; request thiếu loại xe trả 422 đúng backend validation.
- Kết quả: đạt toàn bộ acceptance Slice 05; full regression MySQL pass trên host và container PHP 8.2.
- Quyết định: giữ nguyên DEC-001..033; không phát sinh ADR, migration, schema hoặc business rule mới.
- Rủi ro còn lại: baseline biển số cố ý chỉ dùng pattern đơn giản theo DEC-031, không bao phủ mọi series/quy tắc biển số Việt Nam; bốn tài khoản/xe seed chỉ dùng cho demo/local.
- Lệnh chạy tiếp: sau khi accept Slice 05, thực hiện duy nhất Slice 06.
- Commit đề xuất: `feat(VEH): hoàn tất quản lý phương tiện và biển số [Slice 05]`.

## Slice 04 — Authentication and Role Authorization

- Requirements: AUTH-01, AUTH-02, AUTH-03, AUTH-04, NFR-14; foundation cho NFR-15 và NFR-19; tiếp tục NFR-03, NFR-05, NFR-11 và NFR-17.
- Hoàn thành:
  - Tạo `UserRepository`, `AuthService`, `AuthValidator` và `AuthController` đúng phân tầng; query có input đều dùng prepared statement.
  - Đăng ký kiểm tra phone/name/password ở backend, luôn tạo role customer, dùng BCRYPT và chuyển duplicate constraint thành lỗi nghiệp vụ.
  - Đăng nhập dùng `password_verify`, lỗi chung cho sai/không tồn tại/disabled, dummy hash giảm khác biệt timing, cập nhật `last_login_at` và regenerate session ID.
  - Đăng xuất qua POST + CSRF, xóa dữ liệu phiên, hủy session và cookie; login failure log không chứa phone/password.
  - Bổ sung middleware theo route cho guest/auth/role; guest bị chuyển về login, customer gọi trực tiếp `/admin` nhận 403, admin/customer không vào chéo khu vực.
  - Tạo UI tiếng Việt responsive cho đăng ký/đăng nhập và landing customer/admin có empty state trung thực, không có số liệu nghiệp vụ giả.
  - Seed idempotent một admin và một customer demo; README ghi tài khoản, route và giới hạn môi trường demo.
- Chưa hoàn thành: ownership theo resource thuộc từng module từ Slice 05; rate limit login vẫn là SHOULD và chưa triển khai; dashboard nghiệp vụ/số liệu thuộc các slice sau.
- File thay đổi: `app/Controllers/AuthController.php`, `app/Services/AuthService.php`, `app/Repositories/UserRepository.php`, `app/Validation/AuthValidator.php`, Auth exceptions/middleware, HTTP core wiring, auth/customer/admin views, CSS, seed/seeder, test Auth/session/database, README, RTM và file status này.
- Migration: không có; dùng nguyên schema `users`/`tiers` của Slice 02, không thay ERD hoặc decision.
- Test đã chạy:
  - `composer dump-autoload --strict-psr --no-interaction` — pass.
  - `composer lint` — pass, PHPCS 54/54 file.
  - `vendor/bin/phpunit tests/Unit tests/Feature tests/Security` — pass, 29 tests/95 assertions.
  - `AUTOWASH_DB_TESTS=1 ... vendor/bin/phpunit tests/Integration` trên MySQL 8.4 host port 3307 — pass, 11 tests/64 assertions sau lần chạy cuối.
  - `AUTOWASH_DB_TESTS=1 ... composer check` trên host — pass, 40 tests/159 assertions.
  - `composer check` với DB test trong container PHP 8.2.32/MySQL 8.4 — pass, 40 tests/159 assertions; assertion PDO cũ được sửa portable cho giá trị `false`/`0` nhưng vẫn bắt buộc native prepares.
  - HTTP smoke Apache port 8081 — customer login 303, customer dashboard 200, customer vào admin 403, guest vào admin 303 về login, logout 303 và phiên cũ không vào lại dashboard.
- Kết quả: đạt toàn bộ acceptance Slice 04; full regression MySQL không skip trên host và container PHP 8.2.
- Quyết định: giữ nguyên DEC-001..033; không phát sinh ADR, schema hoặc business rule mới.
- Rủi ro còn lại: validator phone hiện chấp nhận chuỗi 9–15 chữ số đúng acceptance hiện có nhưng chưa chuẩn hóa tương đương `+84`/`0` vì tài liệu không khóa rule đó; tài khoản demo không được dùng ở production.
- Lệnh chạy tiếp: sau khi accept Slice 04, thực hiện duy nhất Slice 05.
- Commit đề xuất: `feat(AUTH): hoàn tất xác thực và phân quyền vai trò [Slice 04]`.

## Slice 03 — HTTP Core, Router and Security Foundation

- Requirements: NFR-03, NFR-12, NFR-13, NFR-14, NFR-18; tiếp tục foundation cho NFR-05, NFR-09, NFR-17 và NFR-23.
- Hoàn thành:
  - Tạo `public/index.php` làm Front Controller duy nhất, Apache rewrite/deny rule và asset CSS theo Design System.
  - Tạo Request, Response, Router có route parameter, 404 và 405/Allow; đăng ký GET `/`, GET `/health` và POST/Redirect/Get mẫu.
  - Tạo session wrapper với flash một request, cookie `HttpOnly`, `SameSite=Lax`, `Secure` khi HTTPS.
  - Tạo CSRF middleware toàn cục cho mutation, token entropy 256-bit, so sánh constant-time và xoay token sau khi dùng để chặn replay.
  - Tạo View/HTML escape helper, layout tiếng Việt responsive, trang home và các view 403/404/405/419/500.
  - Tạo error handler/logger với request ID; production response không lộ exception/stack trace và vẫn trả response an toàn nếu hạ tầng log lỗi.
- Chưa hoàn thành: regenerate/destroy session khi login/logout và Auth/RBAC thuộc Slice 04; error/empty state nghiệp vụ tiếp tục theo từng module.
- File thay đổi: `app/Core/`, `app/Exceptions/`, `app/Middleware/`, `app/Support/Html.php`, `bootstrap/app.php`, HTTP config, `public/`, `routes/web.php`, `resources/views/`, test HTTP/security, README, RTM, Test Plan và file status này.
- Migration: không có; không thay schema hoặc seed.
- Test đã chạy:
  - `composer dump-autoload --strict-psr --no-interaction` — pass.
  - `composer validate --strict` — pass.
  - `vendor/bin/phpunit tests/Unit tests/Feature tests/Security` — pass, 22 tests/75 assertions.
  - `APP_ENV=testing ... php database/reset.php --force --seed` trên MySQL 8.4 host port 3307 — pass.
  - `AUTOWASH_DB_TESTS=1 APP_ENV=testing ... composer check` — pass, PHPCS 42/42 file; PHPUnit 29 tests/101 assertions, không skip.
  - `docker compose config --quiet` và build/chạy image PHP 8.2/Apache — pass; `composer check` trong container pass 29 tests/77 assertions, 7 DB tests skip vì lệnh container không bật DB test flag.
  - HTTP smoke thật tại host port 8081 — `/` 200, `/health` 200, route lạ 404, `/.env` 403, POST hợp lệ 303 + flash, replay token 419.
  - `git diff --check`, scan private key/token/TODO/debug và `composer audit --no-interaction` — pass; từ TODO chỉ xuất hiện trong mô tả test/docs, không có artifact code.
- Kết quả: đạt toàn bộ acceptance Slice 03; full regression MySQL không skip, Docker PHP 8.2 và HTTP Apache đều có evidence thành công.
- Quyết định: giữ nguyên DEC-001..033; không phát sinh ADR, migration hoặc business rule.
- Rủi ro còn lại: authentication/role/ownership chưa tồn tại theo đúng phạm vi; health check chỉ phản ánh tiến trình web, không công khai trạng thái database. Máy test tiếp tục dùng port 3307/8081 vì port mặc định có thể đang bận.
- Lệnh chạy tiếp: sau khi accept Slice 03, thực hiện duy nhất Slice 04.
- Commit đề xuất: `feat(NFR): hoàn tất nền tảng HTTP và bảo mật [Slice 03]`.

## Slice 02 — Database Foundation, Migrations, Seeds and PDO

- Requirements: NFR-08, NFR-10, NFR-11, NFR-26; foundation cho NFR-09, NFR-22, VEH-04, CAT-02 và ADM-07.
- Hoàn thành:
  - Tạo PDO lazy singleton với exception mode, associative fetch, native prepares, `utf8mb4` và MySQL session timezone `+07:00` tương ứng `Asia/Ho_Chi_Minh`.
  - Tạo migration runner có history, batch và advisory lock; 6 migration dựng 26 bảng nghiệp vụ theo dependency và constraint đã khóa.
  - Tạo seed idempotent cho app settings, 4 tier, 4 vehicle type, 4 dịch vụ cùng 16 cặp giá, 4 slot và 5 reward theo DEC-015/019/022.
  - Tạo CLI migrate/seed/reset; reset chỉ chấp nhận local/testing và cờ `--force`.
  - Không tạo `lpr_attempts`; bảng này giữ đúng Slice 13 theo ERD. Không seed user vì tài khoản/password thuộc Slice 04.
- Chưa hoàn thành: backup/export và các invariant cần Service/transaction ở slice nghiệp vụ tiếp tục giữ trạng thái In Progress; chưa có Controller/View/HTTP.
- File thay đổi: `app/Core/Database.php`, `app/Database/`, `database/migrations/`, `database/seeds/base.php`, `database/migrate.php`, `database/seed.php`, `database/reset.php`, config/env/Compose/Composer, test database, README, ERD, RTM và file status này.
- Migration: 6 migration (`001_create_core_tables` đến `006_create_operations_tables`), 26 bảng nghiệp vụ và bảng `migrations`; không sửa decision/schema ngoài baseline.
- Test đã chạy:
  - `composer dump-autoload --strict-psr --no-interaction` — pass.
  - `composer validate --strict` và `composer lint` — pass.
  - `APP_ENV=testing DB_HOST=127.0.0.1 DB_PORT=3307 DB_PASSWORD=autowash_local php database/reset.php --force --seed` — pass trên MySQL 8.4 Docker.
  - `AUTOWASH_DB_TESTS=1 DB_HOST=127.0.0.1 DB_PORT=3307 DB_PASSWORD=autowash_local vendor/bin/phpunit tests/Integration/Database` — pass, 7 tests/24 assertions.
  - `AUTOWASH_DB_TESTS=1 DB_HOST=127.0.0.1 DB_PORT=3307 DB_PASSWORD=autowash_local composer check` — pass, PHPCS 14/14 file; PHPUnit 11 tests/34 assertions.
  - `docker compose config --quiet` — pass.
- Kết quả: đạt acceptance Slice 02; migrate/seed lặp an toàn, constraint và full regression đều có evidence thành công.
- Quyết định: giữ nguyên DEC-001..033; không phát sinh ADR.
- Rủi ro còn lại: máy kiểm thử đã dùng host port 3307 vì port 3306 đang bận; Compose hỗ trợ `DB_FORWARD_PORT` để xử lý. Backup/export thuộc hardening Slice 15.
- Lệnh chạy tiếp: sau khi accept Slice 02, thực hiện duy nhất Slice 03.
- Commit đề xuất: `feat(NFR): hoàn tất nền tảng cơ sở dữ liệu [Slice 02]`.

## Slice 01 — Project Bootstrap and Development Environment

- Requirements: NFR-04, NFR-05 foundation, NFR-09 foundation, NFR-17 foundation, NFR-25 foundation, NFR-26 foundation.
- Hoàn thành:
  - Khởi tạo Composer với PHP `>=8.2`, PSR-4 `App\\ -> app/`, autoload-dev `Tests\\ -> tests/`.
  - Cài Dotenv `5.6.4`, PHPUnit `11.5.56` và PHP_CodeSniffer `3.13.5`; không có application framework.
  - Tạo cấu trúc thư mục theo specification, chưa tạo Router, Front Controller, migration/schema hoặc UI.
  - Tạo `.env.example`, `.gitignore` và config app/database/loyalty đọc biến môi trường qua `App\\Support\\Env`.
  - Tạo Docker Compose cho PHP 8.2/Apache và MySQL 8.4; cấu hình `utf8mb4` và timezone `Asia/Ho_Chi_Minh`.
  - Cập nhật README với yêu cầu hệ thống, cài đặt host/Docker, lệnh quality check và giới hạn hiện tại.
- Chưa hoàn thành: các phần NFR xuyên suốt vẫn `In Progress` cho tới slice sở hữu cuối; chưa có database, HTTP/security core hoặc nghiệp vụ.
- File thay đổi: `composer.json`, `composer.lock`, `.editorconfig`, `.env.example`, `.gitignore`, `phpunit.xml`, `phpcs.xml`, `app/`, `bootstrap/`, `config/`, `database/`, `docker-compose.yml`, `docker/`, `public/`, `resources/`, `routes/`, `scripts/`, `storage/`, `tests/`, `README.md`, RTM và file status này.
- Migration: không tạo; `database/migrations/` mới là cấu trúc rỗng dành cho Slice 02.
- Test đã chạy:
  - `composer validate --strict` — pass.
  - `composer install --no-interaction --prefer-dist` — pass, tạo lockfile và cài 34 package.
  - `composer dump-autoload --strict-psr --no-interaction` — pass, 1.577 class trong optimized autoload.
  - `composer check` — pass: PHPCS 7/7 file; PHPUnit 2 tests, 6 assertions.
  - `docker compose config --quiet` — pass.
  - `git check-ignore -v .env` và kiểm tra `.env` không tồn tại — pass.
  - `composer audit --no-interaction` — pass, không có advisory bảo mật tại thời điểm kiểm tra.
- Kết quả: đạt acceptance Slice 01. Composer install/autoload, smoke test, PSR-12, env safety và Docker Compose config đều có evidence thành công.
- Quyết định: giữ nguyên DEC-001..033; không phát sinh ADR hoặc thay đổi business/design decision.
- Rủi ro còn lại: Docker image chưa được build/chạy container trong checkpoint bắt buộc của Slice 01; `docker compose config` đã hợp lệ. Clean setup đầy đủ gồm migrate/seed/app route chỉ có thể xác minh sau các slice tương ứng.
- Lệnh chạy tiếp: sau khi nhóm accept Slice 01, thực hiện duy nhất Slice 02.
- Commit đề xuất: `chore(NFR): hoàn tất nền tảng dự án [Slice 01]`.

## Slice 00 — Requirements Audit and Technical Design

- Hoàn thành toàn bộ đầu ra tài liệu Slice 00.
- Không có code, migration, database hoặc UI.
- Các câu hỏi cũ được chuyển sang 00B để khóa hoặc giữ lại có chủ đích.

## Mini-Slice 00B — Architecture Decisions and Design System

- Requirements/decisions: DEC-012, DEC-015..033; thêm BKG-07 và đồng bộ VEH/SLOT/LOY/ADM/RBL bị tác động.
- Hoàn thành:
  - Khóa Pure Modern PHP và kiến trúc không framework.
  - Thay vehicle ENUM bằng `vehicle_types` cho motorbike/car/truck/bus.
  - Thiết kế `service_vehicle_prices`, capacity units và booking snapshots.
  - Duyệt `loyalty_allocations`, FEFO, calendar expiry và point formula.
  - Khóa tier seed/rates, cancellation 2 giờ, reward/promotion type restrictions.
  - Khóa LPR provider/manual fallback, research ≥2.000 và performance target.
  - Tạo `DESIGN_SYSTEM.md` cho Customer/Admin.
  - Đồng bộ specification, RTM, ERD, tests, roadmap và execution plan.
  - Closure Patch khóa booking multi-service/multi-slot, expiry clamp, biển số dân sự Việt Nam thông dụng và negative adjustment.
  - Q-016..Q-019 đã đóng; không còn technical decision mở trước Slice 01.
- Xác nhận bên ngoài còn chờ, không chặn Slice 01:
  - Q-020: **External academic deliverable — Pending lecturer confirmation** cho survey thật, ML model, paper/conference-format report, cách chấm riêng và quy mô dataset.
  - Checkpoint bắt buộc trước phần Research/RBL chuyên sâu ở Slice 14; không bịa deliverable hoặc kết quả khi chưa xác nhận.
- File tạo:
  - `docs/DESIGN_SYSTEM.md`
- File cập nhật:
  - `docs/PROJECT_SPECIFICATION.md`
  - `docs/AI_AGENT_EXECUTION_PLAN.md` — ghi nhận 00B, baseline override và bắt buộc đọc Design System trước UI.
  - `docs/REQUIREMENT_TRACEABILITY.md`
  - `docs/ERD.md`
  - `docs/TEST_PLAN.md`
  - `docs/DECISIONS.md`
  - `docs/ASSUMPTIONS.md`
  - `docs/ROADMAP.md`
  - `docs/IMPLEMENTATION_STATUS.md`
- Migration: không tạo; ERD chỉ có thứ tự dự kiến.
- Database/SQL: không tạo.
- Product PHP/HTML/CSS/JS: không tạo.
- Dependency/Docker: không cài hoặc khởi tạo.
- Test đã chạy: static validation đối chiếu 79 spec IDs = 79 RTM rows, mọi row có planned test, 33 decision IDs duy nhất, Markdown fence và prohibited-artifact scan; bổ sung test design `BKG-CL`, `LOY-EXP`, `VEH-PLATE`, `LOY-ADJ`, `RBL-CL`. Chưa tuyên bố application test pass vì chưa có code.
- Kết quả: đạt. Mini-Slice 00B vẫn Complete; ERD/RTM/test/roadmap đồng bộ theo closure decisions; Design System không đổi; không có PHP, migration, SQL/database hoặc HTML/CSS/JS sản phẩm.
- Quyết định: DEC-001..033; DEC-005/017 được sửa, DEC-031..033 được thêm.
- Lệnh chạy tiếp: sau khi nhóm accept 00B, thực hiện duy nhất Slice 01.
- Commit đề xuất: `docs(00b): khóa các quyết định closure trước slice 01`.

## Điều kiện sẵn sàng Slice 01

- [x] Kiến trúc Pure PHP đã khóa.
- [x] ERD/data constraints đã khóa ở mức đủ cho bootstrap.
- [x] Test plan/RTM đã đồng bộ.
- [x] Design System tồn tại trước UI.
- [x] Q-016..019 đã khóa, không còn trạng thái mở.
- [x] Q-020 là external confirmation non-blocking và có checkpoint trước Slice 14.
- [x] Không có artifact sản phẩm được tạo trong 00B.
- [x] Slice 01 chưa thực hiện và đã đủ điều kiện để bắt đầu ở nhiệm vụ kế tiếp.

## Template handoff

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
