# AUTO WASH PRO — REQUIREMENT TRACEABILITY MATRIX

> Baseline đóng release: Slice 15, 2026-07-17.
> `Done` chỉ được ghi sau khi có code, automated test hoặc demo/review evidence đã chạy thật.

## Functional requirements

| Requirement ID | Mô tả/acceptance rút gọn | Nguồn | Priority | Module / Slice | Code | Test dự kiến | Demo | Status |
|---|---|---|---|---|---|---|---|---|
| AUTH-01 | Register phone unique, password ≥8/BCRYPT, role luôn customer | Spec §7.1; SU26SWP01 | MUST | Auth / 04 | `app/Controllers/AuthController.php`, `app/Services/AuthService.php`, `app/Repositories/UserRepository.php`, `app/Validation/AuthValidator.php` | `AuthFlowTest`, `AuthValidatorTest` | DEMO-01 | Done |
| AUTH-02 | Login verify, generic error, regenerate session, chặn disabled | Spec §7.1 | MUST | Auth / 04 | `app/Services/AuthService.php`, `app/Core/Session.php` | `AuthFlowTest`, `SessionTest` | DEMO-01 | Done |
| AUTH-03 | Backend RBAC chặn customer vào admin | Spec §7.1 | MUST | Auth / 04 | `app/Middleware/AuthenticatedMiddleware.php`, `app/Middleware/GuestMiddleware.php`, `app/Middleware/RoleMiddleware.php` | `AuthAuthorizationTest` | DEMO-08 | Done |
| AUTH-04 | Logout hủy session/cookie, POST + CSRF | Spec §7.1 | MUST | Auth / 04 | `app/Controllers/AuthController.php`, `app/Services/AuthService.php`, `app/Core/Session.php`, `app/Middleware/CsrfMiddleware.php` | `AuthFlowTest`, `SessionTest`, `CsrfMiddlewareTest` | DEMO-01 | Done |
| VEH-01 | Biển dân sự VN thông dụng; uppercase/bỏ separator; shared validator pattern tập trung | Spec §7.2; DEC-031 | MUST | Vehicle / 05 | `app/Services/LicensePlateService.php`, `app/Validation/VehicleValidator.php` | `LicensePlateServiceTest`, `VehicleFlowTest` | DEMO-01 | Done |
| VEH-02 | `normalized_plate` unique; display khác nhưng normalized trùng thành domain error | Spec §7.2; DEC-031 | MUST | Vehicle / 05 | `app/Services/VehicleService.php`, `app/Repositories/VehicleRepository.php`, `app/Exceptions/DuplicateLicensePlateException.php` | `VehicleFlowTest` | DEMO-01 | Done |
| VEH-03 | Ownership cho view/edit/booking vehicle | Spec §7.2 | MUST | Vehicle / 05,07 | `app/Services/VehicleService.php`, `app/Repositories/VehicleRepository.php`, `app/Services/BookingService.php`, `app/Repositories/BookingRepository.php` | `VehicleFlowTest`, `BookingFlowTest` | DEMO-01 | Done |
| VEH-04 | Bốn vehicle types qua bảng cấu hình; inactive và không hard-delete khi referenced | Spec §7.2; DEC-015/029 | MUST | Vehicle type / 02,05 | `database/migrations/002_create_catalog_tables.php`, `database/seeds/base.php`, `app/Services/VehicleService.php` | `DatabaseFoundationTest`, `VehicleFlowTest` | DEMO-01 | Done |
| CAT-01 | Active/supported đúng type; group policy DB; đúng một wash package, add-on không độc lập | Spec §7.3; DEC-035 | MUST | Catalog / correction | migration 010, `ServiceCatalogRepository`, `BookingRepository`, grouped customer/admin views | `DatabaseFoundationTest`, `ServiceGroupMigrationBackfillTest`, `CatalogSlotFlowTest`, `BookingFlowTest` | DEMO-02 | Done |
| CAT-02 | Price/duration/capacity theo service+type; capacity vật lý; snapshot bất biến | Spec §7.3; DEC-016/035 | MUST | Pricing / correction | `BookingResourceCalculator`, `BookingService`, seed capacity override null | `DatabaseFoundationTest`, `ServiceGroupMigrationBackfillTest`, `CatalogSlotFlowTest`, `BookingFlowTest` | DEMO-02/03 | Done |
| SLOT-01 | Capacity = max(default, service overrides), không cộng; mọi slot chồng lấn phải đủ | Spec §7.3; DEC-017 | MUST | Slot / 06,07 | `app/Services/WashSlotService.php`, `app/Repositories/WashSlotRepository.php`, `app/Services/BookingService.php` | `CatalogSlotFlowTest`, `WashSlotValidatorTest`, `BookingRulesTest`, `BookingFlowTest` | DEMO-02 | Done |
| SLOT-02 | Lock mọi slot chồng lấn và tạo reservations atomically, không race/orphan | Spec §7.3; DEC-017 | MUST | Booking / 07 | `app/Repositories/BookingRepository.php`, `app/Services/BookingService.php`, `tests/Support/BookingConcurrencyWorker.php` | `BookingFlowTest` (IT-SLOT-02, SLOT-00B-06,08, BKG-CL-04..06) | DEMO-03 | Done |
| BKG-01 | Booking window boundary theo timezone | Spec §7.4 | MUST | Booking / 07 | `app/Services/BookingWindowPolicy.php`, `app/Services/BookingService.php` | `BookingRulesTest`, `BookingFlowTest` | DEMO-02 | Done |
| BKG-02 | Priority = window 7/10/12/14, không chen ngang | Spec §7.4; SU26SWP01 | MUST | Booking / 07 | `app/Services/BookingWindowPolicy.php`, `app/Repositories/BookingRepository.php` | `BookingRulesTest`, `BookingFlowTest` | DEMO-02 | Done |
| BKG-03 | Selection policy trước server pricing; một perk/promo/reward; final ≥0 | Spec §7.4; DEC-035 | MUST | Booking+Pricing / correction | `PriceCalculator`, `PromotionService`, `BookingService`, `BookingRepository` | `BookingRulesTest`, `BookingFlowTest`, `PromotionConfigurationFlowTest` | DEMO-02/07 | Done |
| BKG-04 | Transition pending/confirmed/completed/cancelled/no_show đúng matrix | Spec §7.4 | MUST | Booking / 08 | `app/Services/BookingLifecyclePolicy.php`, `app/Services/BookingService.php`, `app/Repositories/BookingRepository.php` | `BookingLifecyclePolicyTest`, `BookingLifecycleFlowTest` | DEMO-04 | Done |
| BKG-05 | Customer hủy ≥2h; admin ngoại lệ; giải phóng units, không earn/penalty | Spec §7.4; DEC-021 | MUST | Booking / 08,12 | `BookingLifecyclePolicy`, `BookingService`, `PromotionService` | `BookingLifecyclePolicyTest`, `BookingLifecycleFlowTest`, `BookingFlowTest` | DEMO-04 | Done |
| BKG-06 | Complete atomic và idempotent: metrics, loyalty, usages, event | Spec §7.4 | MUST | Booking+Loyalty / 08,09,12,14 | `BookingCompletionService`, `LoyaltyService`, `PromotionService`, `BookingService` | `BookingLifecycleFlowTest`, `LoyaltyFlowTest`, `BookingFlowTest` | DEMO-04 | Done |
| BKG-07 | Service hợp lệ: duration sum, capacity max; giữ mọi slot; invalid selection không tạo artifact | Spec §7.4; DEC-017/035 | MUST | Booking / correction | `BookingResourceCalculator`, `BookingService`, `BookingRepository` | `BookingRulesTest`, `BookingFlowTest`, `ServiceGroupMigrationBackfillTest` | DEMO-02/03 | Done |
| LOY-01 | `floor(floor(final/10k) × rate)`, completed-only/idempotent | Spec §7.5; DEC-004/020 | MUST | Loyalty / 09 | `app/Services/LoyaltyPointCalculator.php`, `app/Services/LoyaltyService.php`, `app/Repositories/LoyaltyTransactionRepository.php` | `LoyaltyRulesTest`, `LoyaltyFlowTest` | DEMO-04 | Done |
| LOY-02 | Ledger + generic credit/debit allocation + balance cache atomic; customer xem ledger owner-only; không âm/clamp | Spec §7.5; DEC-011/018/032 | MUST | Loyalty / 09,10 + route regression | `LoyaltyController`, `LoyaltyService`, `LoyaltyDebitAllocator`, `LoyaltyTransactionRepository`, `routes/web.php`, customer loyalty view, migration 007, reconcile CLI | `LoyaltyFlowTest`, `RewardFlowTest`, `LoyaltyMigrationBackfillTest` | DEMO-04/05 | Done |
| LOY-03 | Redeem FEFO qua credit lots, rollback nếu thiếu, metrics không giảm | Spec §7.5; DEC-006/018 | MUST | Loyalty+Reward / 10 | `app/Services/RewardService.php`, `app/Services/LoyaltyDebitAllocator.php`, `app/Repositories/RewardRepository.php` | `RewardRulesTest`, `RewardFlowTest` | DEMO-05 | Done |
| LOY-04 | 12 calendar months clamp; boundary timezone; allocation expiry idempotent | Spec §7.5; DEC-005/018 | MUST | Loyalty / 10 | `app/Services/LoyaltyExpirationPolicy.php`, `app/Services/LoyaltyService.php`, `scripts/expire-points.php` | `RewardRulesTest`, `RewardFlowTest` | DEMO-06 | Done |
| TIER-01 | Review tháng lịch trước, period unique | Spec §7.6 | MUST | Tier / 11 | `app/Services/TierReviewPolicy.php`, `app/Services/TierReviewService.php`, `scripts/monthly-review.php` | `TierRulesTest`, `TierReviewFlowTest` | DEMO-06 | Done |
| TIER-02 | Spend AND visits; seed threshold/rate đã khóa, DB-configurable | Spec §7.6; DEC-003/019 | MUST | Tier / 11 | `app/Services/TierReviewPolicy.php`, `app/Repositories/TierRepository.php` | `TierRulesTest`, `TierReviewFlowTest` | DEMO-06 | Done |
| TIER-03 | Upgrade/downgrade nhiều bậc; history; reset metrics, giữ point | Spec §7.6 | MUST | Tier / 11 | `app/Services/TierReviewService.php`, `app/Repositories/TierRepository.php` | `TierReviewFlowTest` | DEMO-06 | Done |
| TIER-04 | Run/user idempotency và failure recovery | Spec §7.6 | MUST | Tier / 11 | `app/Services/TierReviewService.php`, `app/Repositories/TierRepository.php`, `app/Controllers/AdminTierReviewController.php` | `TierReviewFlowTest` | DEMO-06 | Done |
| RWD-01 | Reward active/time/tier eligibility ở backend | Spec §7.7 | MUST | Reward / 10 | `app/Services/RewardService.php`, `app/Repositories/RewardRepository.php`, customer reward UI | `RewardRulesTest`, `RewardFlowTest` | DEMO-05 | Done |
| RWD-02 | Redemption record + point debit atomic | Spec §7.7 | MUST | Reward / 10 | `app/Services/RewardService.php`, `app/Repositories/RewardRepository.php`, `app/Services/LoyaltyService.php` | `RewardFlowTest` | DEMO-05 | Done |
| RWD-03 | Đúng service/vehicle type và percentage cap | Spec §7.7; DEC-022 | MUST | Pricing / 12 | `PriceCalculator`, `PromotionService`, migration 008 | `BookingRulesTest`, `BookingFlowTest`, `RewardRulesTest` | DEMO-07 | Done |
| RWD-04 | Ownership và use-once | Spec §7.7 | MUST | Reward / 10,12 | `RewardService`, `PromotionService`, `PromotionRepository` | `RewardFlowTest`, `BookingFlowTest` | DEMO-05 | Done |
| PRO-01 | Tier targeting; Silver+ = Silver/Gold/Platinum | Spec §7.8; SU26SWP01 | MUST | Promotion / 12 | `PromotionService`, `PromotionRepository`, seed Silver+ | `PromotionConfigurationFlowTest`, `BookingFlowTest` | DEMO-07 | Done |
| PRO-02 | Active/time/tier/minimum/total/per-user limits | Spec §7.8 | MUST | Promotion / 12 | `PromotionService`, `PromotionRepository` | `PromotionConfigurationFlowTest`, `BookingFlowTest` concurrency | DEMO-07 | Done |
| PRO-03 | Auto chọn một promo discount lớn nhất, tie-break end sớm | Spec §7.8 | MUST | Pricing / 12 | `PriceCalculator`, `PromotionService` | `BookingRulesTest`, `BookingFlowTest` | DEMO-07 | Done |
| PRO-04 | Auto chọn một perk lợi nhất và snapshot | Spec §7.8 | MUST | Pricing / 12 | `PriceCalculator`, `TierConfigurationService` | `BookingRulesTest`, `BookingFlowTest` | DEMO-07 | Done |
| PRO-05 | Promotion scope theo service/vehicle type được kiểm tra backend | Spec §7.8; DEC-023 | MUST | Promotion / 12 | `PromotionService`, `PromotionValidator` | `PromotionConfigurationFlowTest`, `BookingFlowTest` | DEMO-07 | Done |
| ADM-01 | Tier CRUD/inactive và constraints không âm/trùng | Spec §7.9 | MUST | Admin Tier / 12 | `AdminTierController`, `TierConfigurationService`, `TierConfigurationRepository` | `PromotionConfigurationFlowTest` | DEMO-07 | Done |
| ADM-02 | Service CRUD bắt buộc active group; audit group; bảo toàn price/duration/capacity snapshot | Spec §7.9; DEC-035 | MUST | Admin Catalog / correction | `AdminServiceController`, `ServiceCatalogService`, `ServiceCatalogRepository`, admin service views | `ServiceCatalogValidatorTest`, `CatalogSlotFlowTest`, `ServiceGroupMigrationBackfillTest` | DEMO-02/04 | Done |
| ADM-03 | Slot CRUD/close và validation capacity/time/date/duplicate | Spec §7.9 | MUST | Admin Slot / 06 | `app/Controllers/AdminSlotController.php`, `app/Services/WashSlotService.php`, `resources/views/admin/slots/` | `CatalogSlotFlowTest`, `WashSlotValidatorTest` | DEMO-03 | Done |
| ADM-04 | Reward CRUD/inactive và validation | Spec §7.9 | MUST | Admin Reward / 10 | `AdminRewardController`, `RewardService`, `RewardRepository`, `RewardValidator`, admin reward views | `RewardRulesTest`, `RewardFlowTest` | DEMO-05 | Done |
| ADM-05 | Promotion CRUD/inactive/conditions/target tiers | Spec §7.9 | MUST | Admin Promotion / 12 | `AdminPromotionController`, `PromotionService`, `PromotionRepository`, `PromotionValidator` | `PromotionConfigurationFlowTest` | DEMO-07 | Done |
| ADM-06 | Adjust có reason/ledger/audit; âm vượt available bị reject, không clamp; concurrent-safe | Spec §7.9; DEC-032 | MUST | Admin Loyalty / 09,12 | `app/Controllers/AdminLoyaltyController.php`, `app/Services/LoyaltyService.php`, `app/Repositories/LoyaltyTransactionRepository.php` | `LoyaltyRulesTest`, `LoyaltyFlowTest` | DEMO-08 | Done |
| ADM-07 | Không sửa/xóa lịch sử tài chính/loyalty snapshot | Spec §7.9 | MUST | Persistence / 02+ | Migration FK no-cascade; booking/ledger/allocation/redemption snapshot chỉ có flow append/state | `DatabaseFoundationTest`, `CatalogSlotFlowTest`, IT-ADM-02 | DEMO-04 | Done |
| ADM-08 | Log thay đổi config quan trọng | Spec §7.9, §13 | SHOULD | Audit / 12,15 | Audit tier/perk/promotion/service-price và admin mutation có actor/before/after/reason | `PromotionConfigurationFlowTest`, `CatalogSlotFlowTest`, `LoyaltyFlowTest` | DEMO-07/08 | Done |
| REP-01 | Customer dashboard và sổ điểm đúng owner, link điều hướng đúng, có empty state | Spec §7.10 | MUST | Dashboard / 09,10,14 + route regression | `DashboardController`, `DashboardService`, `ResearchReportRepository`, `LoyaltyController`, customer dashboard/loyalty views, `routes/web.php` | `ResearchFlowTest`, `BookingLifecycleFlowTest`, `LoyaltyFlowTest` | DEMO-04 | Done |
| REP-02 | Admin aggregate; revenue completed-only; admin-only | Spec §7.10 | MUST | Report / 14 | `DashboardController`, `DashboardService`, `ResearchReportRepository`, `resources/views/admin/dashboard.php` | `ResearchFlowTest`, `AuthAuthorizationTest` | DEMO-08 | Done |
| LPR-01 | Manual input + normalize/validate; không gọi là LPR | Spec §7.11 | MUST | Vehicle / 05 | `app/Controllers/VehicleController.php`, `resources/views/customer/vehicles/form.php`, `app/Services/LicensePlateService.php` | `LicensePlateServiceTest`, `VehicleFlowTest` | DEMO-01 | Done |
| LPR-02 | LprProvider adapter + safe upload + confirm/edit + fallback/log | Spec §7.11; DEC-008/024 | MUST | LPR / 13 | `LprService`, `LprUploadService`, `LprAttemptRepository`, `MockLprProvider`, migration 009, vehicle recognition UI | `LprFlowTest`, `RequestResponseTest`, `DatabaseFoundationTest` | DEMO LPR | Done |
| RBL-01 | Giữ đúng research question về tier progression | Spec §7.12; SU26SWP01 | MUST | Research docs / 14 | `docs/RESEARCH_DATA_DICTIONARY.md` | QT-RBL-01, documentation review | DEMO-08 | Done |
| RBL-02 | Log đủ feature nghiên cứu với schema/data dictionary | Spec §7.12; SU26SWP01 | MUST | Research / 14 | `ResearchEventService`, `ResearchEventRepository`, business event hooks, data dictionary | `ResearchFlowTest`, `BookingFlowTest`, `LoyaltyFlowTest` | DEMO-08 | Done |
| RBL-03 | Export không PII, anonymous key an toàn | Spec §7.12 | MUST | Research / 14 | `ResearchExportService`, `ResearchCsvExporter`, `scripts/export-research-data.php` | `ResearchDataTest`, `ResearchFlowTest` | DEMO-08 | Done |
| RBL-04 | Synthetic deterministic ≥2.000 records, bốn vehicle types, data_source rõ | Spec §7.12; DEC-025 | MUST | Research / 14 | `SyntheticResearchDataGenerator`, `scripts/generate-synthetic-research-data.php` | `ResearchDataTest`, acceptance CLI 2.000 records | DEMO-08 | Done |
| RBL-05 | Survey/ML/kiểm định/paper là Deferred bonus work; không quyết định tier, không bịa kết quả | Spec §7.12; DEC-034 | OPTIONAL/SHOULD | Research / Bonus | `docs/DECISIONS.md`, `docs/ASSUMPTIONS.md` | RBL-CL-01..02 | Optional | Deferred bonus work |

## Non-functional requirements

| Requirement ID | Mô tả/acceptance rút gọn | Nguồn | Priority | Slice chính | Code | Test dự kiến | Demo | Status |
|---|---|---|---|---|---|---|---|---|
| NFR-01 | Responsive desktop/mobile cơ bản | Spec §14 | MUST | 15 | Productized guest/customer shell, admin sidebar/drawer, responsive table/card/grid, `app.css`/`app.js` | QT-NFR-01, QT-UI-01 viewport/source review | DEMO-01..08 | Done |
| NFR-02 | 10k bookings/20 VU; read P95 <1s; booking/redeem/report <2s; error <1% | Spec §14; DEC-026 | MUST | 15 | `prepare-performance-data.php`, `run-performance-test.php` | QT-NFR-02, PERF-00B-01..08 | `PERFORMANCE_REPORT.md` | Done |
| NFR-03 | Empty/error state dễ hiểu | Spec §14 | MUST | 03+ | Role-aware `ErrorHandler`, productized error/empty/flash states, CTA an toàn | `HttpCoreTest`, `ProductionErrorTest`, FT-UI-05, module tests | DEMO-01..08 | Done |
| NFR-04 | PSR-12/lint | Spec §14 | MUST | 01+ | `composer.json`, `phpcs.xml` | QT-NFR-04: `composer lint` | — | Done |
| NFR-05 | Đúng layer, không SQL/formula trong Controller/View | Spec §5, §14 | MUST | 01+ | Controller–Service–Repository–View; `scripts/release-audit.php` | QT-NFR-05 + release audit | — | Done |
| NFR-06 | Không TODO/placeholder/code giả ở luồng MUST | Spec §14 | MUST | 15 | `scripts/release-audit.php` | QT-NFR-06 + full source scan | DEMO-01..08 | Done |
| NFR-07 | Mọi MUST có test/demo evidence | Spec §14, §15 | MUST | Mọi slice | RTM + `release-audit.php` + release docs | QT-NFR-07 | DEMO-01..08 | Done |
| NFR-08 | Migrate/seed/reset/backup-export tái lập | Spec §12, §14 | MUST | 02,15 | `app/Database/`, migrate/seed/reset, research export | `DatabaseFoundationTest`, fresh Docker reset/migrate/seed | DEMO setup | Done |
| NFR-09 | Timezone Asia/Ho_Chi_Minh | Spec §5, §14 | MUST | 01+ | env/config/bootstrap/Docker + domain clocks | `EnvironmentConfigTest`, boundary rule tests | DEMO-02/06 | Done |
| NFR-10 | DECIMAL, không float cho tiền | Spec §14 | MUST | 02+ | DECIMAL migrations, decimal-string calculators | `DatabaseFoundationTest`, `BookingRulesTest`, `LoyaltyRulesTest` | DEMO-07 | Done |
| NFR-11 | PDO prepared statement thật, utf8mb4 | Spec §5.4, §8 | MUST | 02+ | `Database`, repositories, database tooling | `DatabaseFoundationTest`, ST-SQL-01 review | — | Done |
| NFR-12 | Escape HTML mặc định | Spec §8 | MUST | 03+ | `app/Core/View.php`, `app/Support/Html.php`, `resources/views/` | `ViewTest`, ST-XSS-01 | — | Done |
| NFR-13 | CSRF cho mọi mutation | Spec §8 | MUST | 03+ | `app/Middleware/CsrfMiddleware.php`, `app/Core/CsrfTokenManager.php` | `CsrfMiddlewareTest`, `HttpCoreTest`, ST-CSRF-01 | — | Done |
| NFR-14 | Session cookie hardening + regenerate/logout | Spec §8 | MUST | 03,04 | `app/Core/Session.php`, `app/Services/AuthService.php`, `bootstrap/app.php` | `SessionTest`, `AuthFlowTest` | DEMO-01 | Done |
| NFR-15 | Backend role + ownership authorization | Spec §8 | MUST | 04+ | Auth/role middleware + owner-scoped Vehicle/Booking/Reward/LPR/Report services | authorization/ownership integration suite | DEMO-01/08 | Done |
| NFR-16 | Upload MIME/size/random/non-executable | Spec §8 | MUST | 13 | `UploadedFile`, `LprUploadService`, protected image route, Docker runtime volumes | `LprFlowTest` | DEMO LPR | Done |
| NFR-17 | Không commit/log secret/token/password/PII | Spec §8, §13 | MUST | 01+ | ignore files, sanitized logger/auth/LPR/research, release secret scan | security tests + ST-SECRET-01 release audit | — | Done |
| NFR-18 | Production error không lộ kỹ thuật; log request ID | Spec §8, §10 | MUST | 03+ | `app/Core/Application.php`, `app/Core/ErrorHandler.php`, `app/Core/Logger.php` | `ProductionErrorTest`, `HttpCoreTest`, ST-ERROR-01 | — | Done |
| NFR-19 | Backend validation đủ trust boundary | Spec §8.2 | MUST | 04+ | Validators + domain services cho mọi module input | unit/integration invalid/tamper matrix | DEMO-01..07 | Done |
| NFR-20 | Chống client tamper giá/điểm/quyền lợi | Spec §8.3 | MUST | 07,09,10,12 | `PriceCalculator`, Booking/Promotion/Reward/Loyalty services | booking/reward/promotion tamper tests | DEMO-07 | Done |
| NFR-21 | Transaction cho critical flows | Spec §9 | MUST | 07,09..12 | repository transaction boundaries cho booking/complete/redeem/expire/review/config | rollback/failure integration suite | DEMO-03..07 | Done |
| NFR-22 | Lock/unique/idempotency giữ invariant | Spec §9 | MUST | 02,07,09..12 | DB constraints + ordered locking + idempotency source/event | concurrency/idempotency process suite | DEMO-03/06 | Done |
| NFR-23 | Không network call dài trong DB transaction | Spec §9 | MUST | 03+ | LPR provider call trước attempt DB write; business transaction không gọi network | QT-NFR-23 code review, `LprFlowTest` | — | Done |
| NFR-24 | Research privacy và data_source | Spec §7.12, §14 | MUST | 14 | CSV allowlist, anonymous key, source filter, synthetic source marker | `ResearchDataTest`, `ResearchFlowTest`, CSV privacy scan | DEMO-08 | Done |
| NFR-25 | Chạy từ môi trường sạch theo README | Spec §1.2, §12, §14 | MUST | 01,15 | README, Compose, migration/seed/reset, release checklist | fresh Docker reset/migrate/seed + full suite | DEMO setup | Done |
| NFR-26 | PHP 8.2+, Composer PSR-4, MySQL 8, PDO, PHPUnit, không framework | Spec §5, §14 | MUST | 01,02 | `composer.json`, `composer.lock`, `docker-compose.yml`, `app/Core/Database.php` | QT-NFR-26; Composer autoload và MySQL integration test | — | Done |

## Decision–ERD–Acceptance–Test trace cho Mini-Slice 00B

| Requirement | Decision | ERD/entity/constraint | Acceptance criteria | Test case |
|---|---|---|---|---|
| VEH-04 | DEC-015, DEC-029 | `VEHICLE_TYPES -> VEHICLES`; code UK; no ENUM; active state | Tạo đủ 4 loại; inactive/unknown bị chặn; referenced type không hard-delete | VEH-00B-01..09 |
| CAT-01/02 | DEC-016 | `SERVICE_VEHICLE_PRICES`; UK service+type | Giá/duration/capacity từ DB; unsupported/missing/invalid bị chặn; snapshot giữ lịch sử | CAT-00B-01..08 |
| CAT-01/02, BKG-03/07, ADM-02/07 | DEC-035 | `SERVICE_GROUPS -> SERVICES`; group FK NOT NULL; policy min/max/mode | Đúng một package; add-on không độc lập; invalid POST không tạo artifact; capacity catalog null; lịch sử bất biến | IT-CAT-GROUP-01..02, IT-CAT-CAP-01, IT-BKG-GROUP-01..03 |
| SLOT-01/02, BKG-07 | DEC-017 | `BOOKINGS` duration/capacity snapshots; `BOOKING_SLOT_RESERVATIONS` unique booking+slot | Duration sum; capacity max không thấp hơn default; mọi slot overlap đủ; client bị bỏ qua; atomic/race-safe | SLOT-00B-01..09, BKG-CL-01..07 |
| BKG-03 | DEC-016/020/023 | Booking/item price snapshots, promotion associations | Backend tính subtotal/final; config đổi không sửa booking cũ | UT-BKG-03, CAT-00B-08, PRO-00B-06 |
| BKG-05 | DEC-021 | Booking cancelled_at/reason/status; active booking capacity sum | ≥2h cho phép; <2h customer bị chặn; admin ngoại lệ; giải phóng units; không earn | BKG-CAN-01..08 |
| BKG-06 | DEC-004/020 | Booking loyalty marker + unique loyalty/research/usage keys | Complete lặp không tăng point/spend/visit/event/usage | IT-BKG-06, LOY-00B-03, RBL-00B-04 |
| LOY-01 | DEC-019/020 | `TIERS.point_rate`, loyalty earn transaction | Floor hai bước; 250k ×1.25 =31; completed-only | UT-LOY-01, LOY-00B-01..03 |
| LOY-02/03/04, ADM-06 | DEC-005/006/011/018/032 | Ledger; generic debit+credit allocation; nullable adjustment source self-FK | FEFO; calendar clamp/boundary; generic credit lots; no-negative/concurrency/idempotency | LOY-00B-04..10, LOY-EXP-01..05, LOY-ADJ-01..07 |
| TIER-02/03/04 | DEC-003/019 | tiers + monthly runs + tier histories unique keys | AND, seed thresholds, upgrade/downgrade/hold, history và rerun an toàn | TIER-00B-01..06 |
| RWD-03 | DEC-022 | `REWARD_VEHICLE_TYPES`, reward service FK | Reward vehicle/service restriction kiểm tra backend | RWD-00B-01..02 |
| PRO-01/02/05 | DEC-023 | promotion tier/service/vehicle association tables | Silver+ và service/type/time/active restrictions đúng backend | PRO-00B-01..06 |
| VEH-01/02, LPR-01/02 | DEC-008/024/031 | `VEHICLES.normalized_plate` UK; shared validator; `LPR_ATTEMPTS` | Normalize/pattern/scope/duplicate đúng; mock/fallback an toàn; regex ≠ LPR | VEH-PLATE-01..07, LPR-00B-01..06 |
| RBL-02/03/04 | DEC-014/025 | `RESEARCH_EVENT_LOGS.event_key` UK và snapshot fields | No PII; ≥2.000; đủ 4 types; event không lặp | RBL-00B-01..04 |
| RBL-05 | DEC-033/034 | Không yêu cầu schema core cho bonus work | Resolved, deferred và non-blocking; không bịa deliverable/kết quả | RBL-CL-01..02 |
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
| Survey thật/ML model/kiểm định/paper-conference report | OPTIONAL/SHOULD | Q-020/DEC-034; Deferred bonus work, không chặn Slice 14/release và không bịa deliverable/kết quả |

## Quy tắc cập nhật

- Chỉ chuyển `Done` khi cột Code có đường dẫn thật và Test/Demo có evidence chạy.
- Nếu requirement thay đổi, cập nhật changelog/decision trước code.
- Commit/PR từ Slice 01 phải ghi Requirement ID liên quan.
