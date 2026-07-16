# AUTO WASH PRO — IMPLEMENTATION STATUS

> Cập nhật: 2026-07-16  
> Slice hiện tại: Slice 11 — Complete
>
> Product code: đã có nền repository/database, HTTP/security, authentication/RBAC, quản lý phương tiện,
> danh mục dịch vụ, booking, loyalty generic credit lots, FEFO redemption/expiry, reward management và
> monthly tier review.

## Tổng quan

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
| 12–15 | Not started | Xem `ROADMAP.md` |

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
