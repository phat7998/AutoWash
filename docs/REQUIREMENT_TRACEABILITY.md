# AUTO WASH PRO — REQUIREMENT TRACEABILITY MATRIX

> Baseline: Mini-Slice 00B Closure Patch, 2026-07-16  
> Trạng thái `Planned` nghĩa là đã thiết kế trace/test nhưng chưa có code hoặc evidence chạy thật.

## Functional requirements

| Requirement ID | Mô tả/acceptance rút gọn | Nguồn | Priority | Module / Slice | Code | Test dự kiến | Demo | Status |
|---|---|---|---|---|---|---|---|---|
| AUTH-01 | Register phone unique, password ≥8/BCRYPT, role luôn customer | Spec §7.1; SU26SWP01 | MUST | Auth / 04 | `app/Controllers/AuthController.php`, `app/Services/AuthService.php`, `app/Repositories/UserRepository.php`, `app/Validation/AuthValidator.php` | `AuthFlowTest`, `AuthValidatorTest` | DEMO-01 | Done |
| AUTH-02 | Login verify, generic error, regenerate session, chặn disabled | Spec §7.1 | MUST | Auth / 04 | `app/Services/AuthService.php`, `app/Core/Session.php` | `AuthFlowTest`, `SessionTest` | DEMO-01 | Done |
| AUTH-03 | Backend RBAC chặn customer vào admin | Spec §7.1 | MUST | Auth / 04 | `app/Middleware/AuthenticatedMiddleware.php`, `app/Middleware/GuestMiddleware.php`, `app/Middleware/RoleMiddleware.php` | `AuthAuthorizationTest` | DEMO-08 | Done |
| AUTH-04 | Logout hủy session/cookie, POST + CSRF | Spec §7.1 | MUST | Auth / 04 | `app/Controllers/AuthController.php`, `app/Services/AuthService.php`, `app/Core/Session.php`, `app/Middleware/CsrfMiddleware.php` | `AuthFlowTest`, `SessionTest`, `CsrfMiddlewareTest` | DEMO-01 | Done |
| VEH-01 | Biển dân sự VN thông dụng; uppercase/bỏ separator; shared validator pattern tập trung | Spec §7.2; DEC-031 | MUST | Vehicle / 05 | `app/Services/LicensePlateService.php`, `app/Validation/VehicleValidator.php` | `LicensePlateServiceTest`, `VehicleFlowTest` | DEMO-01 | Done |
| VEH-02 | `normalized_plate` unique; display khác nhưng normalized trùng thành domain error | Spec §7.2; DEC-031 | MUST | Vehicle / 05 | `app/Services/VehicleService.php`, `app/Repositories/VehicleRepository.php`, `app/Exceptions/DuplicateLicensePlateException.php` | `VehicleFlowTest` | DEMO-01 | Done |
| VEH-03 | Ownership cho view/edit/booking vehicle | Spec §7.2 | MUST | Vehicle / 05,07 | `app/Services/VehicleService.php`, `app/Repositories/VehicleRepository.php`, `app/Exceptions/VehicleOwnershipException.php` | `VehicleFlowTest`; booking ownership tiếp tục Slice 07 | DEMO-01 | In Progress |
| VEH-04 | Bốn vehicle types qua bảng cấu hình; inactive và không hard-delete khi referenced | Spec §7.2; DEC-015/029 | MUST | Vehicle type / 02,05 | `database/migrations/002_create_catalog_tables.php`, `database/seeds/base.php`, `app/Services/VehicleService.php` | `DatabaseFoundationTest`, `VehicleFlowTest` | DEMO-01 | Done |
| CAT-01 | Chỉ service/cặp giá active, supported, đúng type; giá từ DB | Spec §7.3 | MUST | Catalog / 06 | Planned | IT-CAT-01, CAT-00B-02..05 | DEMO-02 | Planned |
| CAT-02 | Price/duration/capacity override theo unique service + vehicle type và snapshot | Spec §7.3; DEC-016 | MUST | Pricing / 02,06,07 | `database/migrations/002_create_catalog_tables.php`, `database/seeds/base.php` | `DatabaseFoundationTest`, CAT-00B-01..08 | DEMO-02 | In Progress |
| SLOT-01 | Capacity = max(default, service overrides), không cộng; mọi slot chồng lấn phải đủ | Spec §7.3; DEC-017 | MUST | Slot / 06,07 | Planned | IT-SLOT-01, SLOT-00B-01..05,09, BKG-CL-02..05 | DEMO-02 | Planned |
| SLOT-02 | Lock mọi slot chồng lấn và tạo reservations atomically, không race/orphan | Spec §7.3; DEC-017 | MUST | Booking / 07 | Planned | IT-SLOT-02, SLOT-00B-06,08, BKG-CL-04..06 | DEMO-03 | Planned |
| BKG-01 | Booking window boundary theo timezone | Spec §7.4 | MUST | Booking / 07 | Planned | UT-BKG-01, FT-BKG-01 | DEMO-02 | Planned |
| BKG-02 | Priority = window 7/10/12/14, không chen ngang | Spec §7.4; SU26SWP01 | MUST | Booking / 07 | Planned | UT-BKG-01, FT-BKG-01 | DEMO-02 | Planned |
| BKG-03 | Server price; một perk/promo/reward; final ≥0 | Spec §7.4 | MUST | Booking+Pricing / 07,12 | Planned | UT-BKG-03, FT-BKG-03 | DEMO-07 | Planned |
| BKG-04 | Transition pending/confirmed/completed/cancelled/no_show đúng matrix | Spec §7.4 | MUST | Booking / 08 | Planned | IT-BKG-04 | DEMO-04 | Planned |
| BKG-05 | Customer hủy ≥2h; admin ngoại lệ; giải phóng units, không earn/penalty | Spec §7.4; DEC-021 | MUST | Booking / 08,12 | Planned | IT-BKG-05, BKG-CAN-01..08 | DEMO-04 | Planned |
| BKG-06 | Complete atomic và idempotent: metrics, loyalty, usages, event | Spec §7.4 | MUST | Booking+Loyalty / 08,09,12,14 | Planned | IT-BKG-06 | DEMO-04 | Planned |
| BKG-07 | Multi-service duration = sum; capacity = max; giữ trên mọi slot overlap; backend source | Spec §7.4; DEC-017 | MUST | Booking / 07 | Planned | BKG-CL-01..07 | DEMO-03 | Planned |
| LOY-01 | `floor(floor(final/10k) × rate)`, completed-only/idempotent | Spec §7.5; DEC-004/020 | MUST | Loyalty / 09 | Planned | UT-LOY-01, LOY-00B-01..03 | DEMO-04 | Planned |
| LOY-02 | Ledger + allocation + balance cache atomic; adjust/redeem không âm hoặc clamp | Spec §7.5; DEC-011/018/032 | MUST | Loyalty / 09 | Planned | IT-LOY-02, LOY-00B-09, LOY-ADJ-01..07 | DEMO-04 | Planned |
| LOY-03 | Redeem FEFO qua allocations/lots, rollback nếu thiếu, metrics không giảm | Spec §7.5; DEC-006/018 | MUST | Loyalty+Reward / 10 | Planned | IT-LOY-03, LOY-00B-04..07,10 | DEMO-05 | Planned |
| LOY-04 | 12 calendar months clamp; `current_time >= expires_at`; timezone thống nhất; idempotent | Spec §7.5; DEC-005/018 | MUST | Loyalty / 10 | Planned | IT-LOY-04, LOY-00B-07..09, LOY-EXP-01..05 | DEMO-06 | Planned |
| TIER-01 | Review tháng lịch trước, period unique | Spec §7.6 | MUST | Tier / 11 | Planned | IT-TIER-01 | DEMO-06 | Planned |
| TIER-02 | Spend AND visits; seed threshold/rate đã khóa, DB-configurable | Spec §7.6; DEC-003/019 | MUST | Tier / 11 | Planned | UT-TIER-02, TIER-00B-01 | DEMO-06 | Planned |
| TIER-03 | Upgrade/downgrade nhiều bậc; history; reset metrics, giữ point | Spec §7.6 | MUST | Tier / 11 | Planned | IT-TIER-03 | DEMO-06 | Planned |
| TIER-04 | Run/user idempotency và failure recovery | Spec §7.6 | MUST | Tier / 11 | Planned | IT-TIER-04 | DEMO-06 | Planned |
| RWD-01 | Reward active/time/tier eligibility ở backend | Spec §7.7 | MUST | Reward / 10 | Planned | UT-RWD-01 | DEMO-05 | Planned |
| RWD-02 | Redemption record + point debit atomic | Spec §7.7 | MUST | Reward / 10 | Planned | IT-RWD-02 | DEMO-05 | Planned |
| RWD-03 | Đúng service/vehicle type và percentage cap | Spec §7.7; DEC-022 | MUST | Pricing / 12 | Planned | UT-RWD-03, RWD-00B-01..02 | DEMO-07 | Planned |
| RWD-04 | Ownership và use-once | Spec §7.7 | MUST | Reward / 10,12 | Planned | IT-RWD-04 | DEMO-05 | Planned |
| PRO-01 | Tier targeting; Silver+ = Silver/Gold/Platinum | Spec §7.8; SU26SWP01 | MUST | Promotion / 12 | Planned | IT-PRO-01 | DEMO-07 | Planned |
| PRO-02 | Active/time/tier/minimum/total/per-user limits | Spec §7.8 | MUST | Promotion / 12 | Planned | UT-PRO-02, IT-PRO-02 | DEMO-07 | Planned |
| PRO-03 | Auto chọn một promo discount lớn nhất, tie-break end sớm | Spec §7.8 | MUST | Pricing / 12 | Planned | UT-PRO-03, IT-PRO-03 | DEMO-07 | Planned |
| PRO-04 | Auto chọn một perk lợi nhất và snapshot | Spec §7.8 | MUST | Pricing / 12 | Planned | UT-PRO-04, IT-PRO-04 | DEMO-07 | Planned |
| PRO-05 | Promotion scope theo service/vehicle type được kiểm tra backend | Spec §7.8; DEC-023 | MUST | Promotion / 12 | Planned | PRO-00B-05..06 | DEMO-07 | Planned |
| ADM-01 | Tier CRUD/inactive và constraints không âm/trùng | Spec §7.9 | MUST | Admin Tier / 12 | Planned | IT-ADM-01 | DEMO-07 | Planned |
| ADM-02 | Service CRUD/inactive; bảo toàn price snapshot | Spec §7.9 | MUST | Admin Catalog / 06 | Planned | IT-ADM-02 | DEMO-04 | Planned |
| ADM-03 | Slot CRUD/close và validation capacity/time/date/duplicate | Spec §7.9 | MUST | Admin Slot / 06 | Planned | IT-ADM-03 | DEMO-03 | Planned |
| ADM-04 | Reward CRUD/inactive và validation | Spec §7.9 | MUST | Admin Reward / 10 | Planned | IT-ADM-04 | DEMO-05 | Planned |
| ADM-05 | Promotion CRUD/inactive/conditions/target tiers | Spec §7.9 | MUST | Admin Promotion / 12 | Planned | IT-ADM-05 | DEMO-07 | Planned |
| ADM-06 | Adjust có reason/ledger/audit; âm vượt available bị reject, không clamp; concurrent-safe | Spec §7.9; DEC-032 | MUST | Admin Loyalty / 09,12 | Planned | IT-ADM-06, LOY-ADJ-01..07 | DEMO-08 | Planned |
| ADM-07 | Không sửa/xóa lịch sử tài chính/loyalty snapshot | Spec §7.9 | MUST | Persistence / 02+ | `database/migrations/001_create_core_tables.php`..`006_create_operations_tables.php` | `DatabaseFoundationTest`, IT-ADM-02 | DEMO-04 | In Progress |
| ADM-08 | Log thay đổi config quan trọng | Spec §7.9, §13 | SHOULD | Audit / 12,15 | Planned | IT-ADM-08 | DEMO-08 | Planned |
| REP-01 | Customer dashboard đúng owner và có empty state | Spec §7.10 | MUST | Dashboard / 09,10,14 | Planned | FT-REP-01 | DEMO-04 | Planned |
| REP-02 | Admin aggregate; revenue completed-only; admin-only | Spec §7.10 | MUST | Report / 14 | Planned | IT-REP-02, FT-REP-02 | DEMO-08 | Planned |
| LPR-01 | Manual input + normalize/validate; không gọi là LPR | Spec §7.11 | MUST | Vehicle / 05 | `app/Controllers/VehicleController.php`, `resources/views/customer/vehicles/form.php`, `app/Services/LicensePlateService.php` | `LicensePlateServiceTest`, `VehicleFlowTest` | DEMO-01 | Done |
| LPR-02 | LprProvider adapter + safe upload + confirm/edit + fallback/log | Spec §7.11; DEC-008/024 | MUST | LPR / 13 | Planned | FT-LPR-02, ST-UPLOAD-01, LPR-00B-01..06 | DEMO LPR | Planned |
| RBL-01 | Giữ đúng research question về tier progression | Spec §7.12; SU26SWP01 | MUST | Research docs / 14 | Planned | QT-RBL-01 | DEMO-08 | Planned |
| RBL-02 | Log đủ feature nghiên cứu với schema/data dictionary | Spec §7.12; SU26SWP01 | MUST | Research / 14 | Planned | IT-RBL-02 | DEMO-08 | Planned |
| RBL-03 | Export không PII, anonymous key an toàn | Spec §7.12 | MUST | Research / 14 | Planned | FT-RBL-03, ST-PRIVACY-01 | DEMO-08 | Planned |
| RBL-04 | Synthetic deterministic ≥2.000 records, bốn vehicle types, data_source rõ | Spec §7.12; DEC-025 | MUST | Research / 14 | Planned | IT-RBL-04, FT-RBL-04, RBL-00B-02..03 | DEMO-08 | Planned |
| RBL-05 | External academic deliverable pending lecturer confirmation; không bịa survey/ML/paper | Spec §7.12; SU26SWP01; DEC-033 | SHOULD | Research docs / 14 | Planned | FT-RBL-05, RBL-CL-01..02 | Optional demo | Pending external confirmation |

## Non-functional requirements

| Requirement ID | Mô tả/acceptance rút gọn | Nguồn | Priority | Slice chính | Code | Test dự kiến | Demo | Status |
|---|---|---|---|---|---|---|---|---|
| NFR-01 | Responsive desktop/mobile cơ bản | Spec §14 | MUST | 15 | Planned | QT-NFR-01 | DEMO-01..08 | Planned |
| NFR-02 | 10k bookings/20 VU; read P95 <1s; booking/redeem/report <2s; error <1% | Spec §14; DEC-026 | MUST | 15 | Planned | QT-NFR-02, PERF-00B-01..08 | Performance report | Planned |
| NFR-03 | Empty/error state dễ hiểu | Spec §14 | MUST | 03+ | `app/Core/ErrorHandler.php`, `resources/views/errors/` | `HttpCoreTest`, `ProductionErrorTest`; FT-NFR-03 tiếp tục theo module | DEMO-01..08 | In Progress |
| NFR-04 | PSR-12/lint | Spec §14 | MUST | 01+ | `composer.json`, `phpcs.xml` | QT-NFR-04: `composer lint` | — | Done |
| NFR-05 | Đúng layer, không SQL/formula trong Controller/View | Spec §5, §14 | MUST | 01+ | `composer.json`, `app/` structure | QT-NFR-05 | — | In Progress |
| NFR-06 | Không TODO/placeholder/code giả ở luồng MUST | Spec §14 | MUST | 15 | Planned | QT-NFR-06 | DEMO-01..08 | Planned |
| NFR-07 | Mọi MUST có test/demo evidence | Spec §14, §15 | MUST | Mọi slice | Planned | QT-NFR-07 | DEMO-01..08 | Planned |
| NFR-08 | Migrate/seed/reset/backup-export tái lập | Spec §12, §14 | MUST | 02,15 | `app/Database/`, `database/migrate.php`, `database/seed.php`, `database/reset.php` | `DatabaseFoundationTest`, IT-NFR-08 | DEMO setup | In Progress |
| NFR-09 | Timezone Asia/Ho_Chi_Minh | Spec §5, §14 | MUST | 01+ | `.env.example`, `config/app.php`, `bootstrap/app.php`, `docker/php/Dockerfile` | `EnvironmentConfigTest`; UT-NFR-09 tiếp tục theo boundary nghiệp vụ | DEMO-02 | In Progress |
| NFR-10 | DECIMAL, không float cho tiền | Spec §14 | MUST | 02+ | `database/migrations/` | `DatabaseFoundationTest`, UT-NFR-10 | DEMO-07 | In Progress |
| NFR-11 | PDO prepared statement thật, utf8mb4 | Spec §5.4, §8 | MUST | 02+ | `app/Core/Database.php`, `app/Database/DatabaseSeeder.php` | `DatabaseFoundationTest`, ST-SQL-01 | — | In Progress |
| NFR-12 | Escape HTML mặc định | Spec §8 | MUST | 03+ | `app/Core/View.php`, `app/Support/Html.php`, `resources/views/` | `ViewTest`, ST-XSS-01 | — | Done |
| NFR-13 | CSRF cho mọi mutation | Spec §8 | MUST | 03+ | `app/Middleware/CsrfMiddleware.php`, `app/Core/CsrfTokenManager.php` | `CsrfMiddlewareTest`, `HttpCoreTest`, ST-CSRF-01 | — | Done |
| NFR-14 | Session cookie hardening + regenerate/logout | Spec §8 | MUST | 03,04 | `app/Core/Session.php`, `app/Services/AuthService.php`, `bootstrap/app.php` | `SessionTest`, `AuthFlowTest` | DEMO-01 | Done |
| NFR-15 | Backend role + ownership authorization | Spec §8 | MUST | 04+ | `app/Middleware/AuthenticatedMiddleware.php`, `app/Middleware/RoleMiddleware.php`, `app/Services/VehicleService.php` | `AuthAuthorizationTest`, `VehicleFlowTest`; ownership tiếp tục theo module | DEMO-08 | In Progress |
| NFR-16 | Upload MIME/size/random/non-executable | Spec §8 | MUST | 13 | Planned | ST-UPLOAD-01 | DEMO LPR | Planned |
| NFR-17 | Không commit/log secret/token/password/PII | Spec §8, §13 | MUST | 01+ | `.gitignore`, `.dockerignore`, `.env.example`, `app/Core/Logger.php`, `app/Services/AuthService.php` | `ProductionErrorTest`, `HttpSecurityConfigurationTest`, `AuthFlowTest`; ST-SECRET-01 tiếp tục toàn dự án | — | In Progress |
| NFR-18 | Production error không lộ kỹ thuật; log request ID | Spec §8, §10 | MUST | 03+ | `app/Core/Application.php`, `app/Core/ErrorHandler.php`, `app/Core/Logger.php` | `ProductionErrorTest`, `HttpCoreTest`, ST-ERROR-01 | — | Done |
| NFR-19 | Backend validation đủ trust boundary | Spec §8.2 | MUST | 04+ | `app/Validation/AuthValidator.php`, `app/Validation/VehicleValidator.php`, `app/Services/AuthService.php`, `app/Services/VehicleService.php` | `AuthValidatorTest`, `AuthFlowTest`, `LicensePlateServiceTest`, `VehicleFlowTest`; tiếp tục theo module | DEMO-01..07 | In Progress |
| NFR-20 | Chống client tamper giá/điểm/quyền lợi | Spec §8.3 | MUST | 07,09,10,12 | Planned | ST-PRICE-01 | DEMO-07 | Planned |
| NFR-21 | Transaction cho critical flows | Spec §9 | MUST | 07,09..12 | Planned | IT-NFR-21 | DEMO-03..07 | Planned |
| NFR-22 | Lock/unique/idempotency giữ invariant | Spec §9 | MUST | 02,07,09..12 | `database/migrations/`, `app/Database/MigrationRunner.php` | `DatabaseFoundationTest`, IT-NFR-22 | DEMO-03,06 | In Progress |
| NFR-23 | Không network call dài trong DB transaction | Spec §9 | MUST | 03+ | Planned | QT-NFR-23 | — | Planned |
| NFR-24 | Research privacy và data_source | Spec §7.12, §14 | MUST | 14 | Planned | ST-PRIVACY-01 | DEMO-08 | Planned |
| NFR-25 | Chạy từ môi trường sạch theo README | Spec §1.2, §12, §14 | MUST | 01,15 | `README.md`, `composer.json`, `docker-compose.yml` | FT-NFR-25; Slice 01 install/config evidence | DEMO setup | In Progress |
| NFR-26 | PHP 8.2+, Composer PSR-4, MySQL 8, PDO, PHPUnit, không framework | Spec §5, §14 | MUST | 01,02 | `composer.json`, `composer.lock`, `docker-compose.yml`, `app/Core/Database.php` | QT-NFR-26; Composer autoload và MySQL integration test | — | Done |

## Decision–ERD–Acceptance–Test trace cho Mini-Slice 00B

| Requirement | Decision | ERD/entity/constraint | Acceptance criteria | Test case |
|---|---|---|---|---|
| VEH-04 | DEC-015, DEC-029 | `VEHICLE_TYPES -> VEHICLES`; code UK; no ENUM; active state | Tạo đủ 4 loại; inactive/unknown bị chặn; referenced type không hard-delete | VEH-00B-01..09 |
| CAT-01/02 | DEC-016 | `SERVICE_VEHICLE_PRICES`; UK service+type | Giá/duration/capacity từ DB; unsupported/missing/invalid bị chặn; snapshot giữ lịch sử | CAT-00B-01..08 |
| SLOT-01/02, BKG-07 | DEC-017 | `BOOKINGS` duration/capacity snapshots; `BOOKING_SLOT_RESERVATIONS` unique booking+slot | Duration sum; capacity max không thấp hơn default; mọi slot overlap đủ; client bị bỏ qua; atomic/race-safe | SLOT-00B-01..09, BKG-CL-01..07 |
| BKG-03 | DEC-016/020/023 | Booking/item price snapshots, promotion associations | Backend tính subtotal/final; config đổi không sửa booking cũ | UT-BKG-03, CAT-00B-08, PRO-00B-06 |
| BKG-05 | DEC-021 | Booking cancelled_at/reason/status; active booking capacity sum | ≥2h cho phép; <2h customer bị chặn; admin ngoại lệ; giải phóng units; không earn | BKG-CAN-01..08 |
| BKG-06 | DEC-004/020 | Booking loyalty marker + unique loyalty/research/usage keys | Complete lặp không tăng point/spend/visit/event/usage | IT-BKG-06, LOY-00B-03, RBL-00B-04 |
| LOY-01 | DEC-019/020 | `TIERS.point_rate`, loyalty earn transaction | Floor hai bước; 250k ×1.25 =31; completed-only | UT-LOY-01, LOY-00B-01..03 |
| LOY-02/03/04, ADM-06 | DEC-005/006/011/018/032 | Ledger/allocation; nullable adjustment source self-FK; debit+earn UK | FEFO; calendar clamp/boundary; adjust reject không clamp; no-negative/concurrency/idempotency | LOY-00B-04..10, LOY-EXP-01..05, LOY-ADJ-01..07 |
| TIER-02/03/04 | DEC-003/019 | tiers + monthly runs + tier histories unique keys | AND, seed thresholds, upgrade/downgrade/hold, history và rerun an toàn | TIER-00B-01..06 |
| RWD-03 | DEC-022 | `REWARD_VEHICLE_TYPES`, reward service FK | Reward vehicle/service restriction kiểm tra backend | RWD-00B-01..02 |
| PRO-01/02/05 | DEC-023 | promotion tier/service/vehicle association tables | Silver+ và service/type/time/active restrictions đúng backend | PRO-00B-01..06 |
| VEH-01/02, LPR-01/02 | DEC-008/024/031 | `VEHICLES.normalized_plate` UK; shared validator; `LPR_ATTEMPTS` | Normalize/pattern/scope/duplicate đúng; mock/fallback an toàn; regex ≠ LPR | VEH-PLATE-01..07, LPR-00B-01..06 |
| RBL-02/03/04 | DEC-014/025 | `RESEARCH_EVENT_LOGS.event_key` UK và snapshot fields | No PII; ≥2.000; đủ 4 types; event không lặp | RBL-00B-01..04 |
| RBL-05 | DEC-033 | Không yêu cầu schema core mới trước xác nhận | External pending, non-blocking; không bịa deliverable/kết quả; checkpoint trước Slice 14 | RBL-CL-01..02 |
| NFR-02 | DEC-026 | Không thay schema core; performance fixture/report | 10k/20VU; P95/error target; ghi môi trường; loại external LPR latency | PERF-00B-01..08 |
| NFR-01/03 | DEC-027 | `DESIGN_SYSTEM.md` token/component/state/layout rules | Customer/Admin nhất quán, responsive, accessible, đủ UI states | QT-NFR-01, FT-NFR-03 |
| NFR-26 | DEC-012 | Architecture decision; không ERD-specific | PHP 8.2+, PDO/MySQL, Composer/Dotenv/PHPUnit, no full-stack framework | QT-NFR-26 |

## Optional và out-of-scope

| Hạng mục | Phân loại | Cách xử lý |
|---|---|---|
| Email adapter/log mail | SHOULD | Chỉ làm khi MUST ổn định; không có slice riêng |
| Self-trained/production LPR model | COULD/không tuyên bố | Slice 13 dùng adapter/mock/manual; external provider chỉ khi có |
| Biển quân đội/ngoại giao/nước ngoài/chuyên dùng/tạm/định dạng hiếm | OUT baseline plate validator | Trả lỗi rõ ràng hoặc manual review nếu domain sau này hỗ trợ; không gọi regex là LPR |
| Audit log đầy đủ | SHOULD | ADM-08; point adjust audit vẫn MUST |
| Docker Compose | SHOULD | Slice 01 nếu nhóm dùng Docker |
| AI personalization/recommendation | COULD | Không lập code/test trong phạm vi nộp đồ án |
| Multi-branch, SMS thật, real-time, self-trained LPR | COULD | Không lập schema trong phạm vi nộp đồ án |
| Online payment/refund/wallet | OUT | Không tạo module/migration |
| Accounting/full inventory/microservices/native app/facial recognition | OUT | Không triển khai |
| Survey thật/ML model/paper-conference report | External pending | Q-020/DEC-033; không chặn Slice 01, checkpoint trước Slice 14 và không bịa deliverable/kết quả |

## Quy tắc cập nhật

- Chỉ chuyển `Done` khi cột Code có đường dẫn thật và Test/Demo có evidence chạy.
- Nếu requirement thay đổi, cập nhật changelog/decision trước code.
- Commit/PR từ Slice 01 phải ghi Requirement ID liên quan.
