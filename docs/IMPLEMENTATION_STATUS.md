# AUTO WASH PRO — IMPLEMENTATION STATUS

> Cập nhật: 2026-07-16  
> Slice hiện tại: Slice 05 — Complete
>
> Product code: đã có nền repository/database, HTTP/security, authentication/RBAC và quản lý phương tiện.

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
| 06–15 | Not started | Xem `ROADMAP.md` |

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
