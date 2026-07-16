# AutoWash Pro

AutoWash Pro là hệ thống quản lý dịch vụ chăm sóc phương tiện, đặt lịch trước và khách hàng thân thiết được xây dựng bằng Modern PHP thuần. Phiên bản đồ án cũ được lưu tại nhánh `legacy-main`.

Repository hiện hoàn thành Slice 03: Composer/PSR-4, cấu hình môi trường, database foundation và hạ tầng HTTP/security. Ứng dụng đã có Front Controller, router, session/CSRF, view escaping, xử lý lỗi an toàn, trang nền tảng và health check; authentication và các module nghiệp vụ thuộc các slice sau.

## Yêu cầu hệ thống

- PHP 8.2 trở lên với extension PDO.
- Composer 2.
- Docker và Docker Compose nếu dùng môi trường container.
- MySQL 8; Docker Compose hiện cấu hình MySQL 8.4.

## Cài đặt bằng PHP trên máy

```bash
composer install
cp .env.example .env
composer dump-autoload --strict-psr
```

Chỉnh các giá trị local trong `.env` khi cần. File `.env` đã được Git ignore; không đưa secret thật vào repository.
Khi chạy PHP trực tiếp trên máy, đặt `DB_HOST=127.0.0.1`; giá trị `mysql` dùng cho container web.

## Môi trường Docker

Tạo file môi trường rồi build và chạy hai service `web` và `mysql`:

```bash
cp .env.example .env
docker compose config
docker compose up -d --build
docker compose run --rm web composer install
```

Mở `http://localhost:8080/` để xem trang nền tảng hoặc `http://localhost:8080/health` để kiểm tra trạng thái tối thiểu. Mọi URL động được Apache chuyển qua `public/index.php`.

Kiểm tra trạng thái hoặc dừng môi trường:

```bash
docker compose ps
docker compose down
```

Port mặc định của Apache là `8080`, MySQL là `3306`. Có thể đặt `APP_PORT` hoặc `DB_FORWARD_PORT` trong `.env` nếu port local đã được sử dụng.

Nếu chạy bằng PHP built-in server, dùng router script là Front Controller:

```bash
php -S 127.0.0.1:8080 -t public public/index.php
```

## Database

Sau khi MySQL đã sẵn sàng và `.env` chứa đúng thông tin kết nối:

```bash
php database/migrate.php
php database/seed.php --demo
```

Migration runner lưu lịch sử và có advisory lock chống hai tiến trình chạy đồng thời. Seed có thể chạy lại mà không tạo thêm tier, loại xe, dịch vụ, slot hoặc reward trùng.

Reset chỉ dành cho `APP_ENV=local|testing`, xóa toàn bộ dữ liệu trong database đang cấu hình và bắt buộc xác nhận rõ:

```bash
php database/reset.php --force --seed
```

Không chạy lệnh reset trên database có dữ liệu cần giữ. Dữ liệu seed Slice 02 chỉ là cấu hình và demo nền tảng; tài khoản demo thuộc Slice 04.

## Kiểm tra chất lượng

```bash
composer validate --strict
composer lint
composer test
composer check
```

- `composer lint`: kiểm tra PSR-12 bằng PHP_CodeSniffer.
- `composer test`: chạy PHPUnit.
- `composer check`: chạy lint rồi toàn bộ test hiện có.
- HTTP/security test kiểm tra router, 404/405, CSRF, session flash/cookie, escaping, PRG và production error response.

Integration test database cần MySQL riêng có thể reset an toàn:

```bash
AUTOWASH_DB_TESTS=1 vendor/bin/phpunit tests/Integration/Database
```

## Cấu trúc chính

```text
app/                 Mã nguồn ứng dụng theo namespace App\
bootstrap/           Khởi tạo dependency và môi trường
config/              Cấu hình app, database và loyalty từ biến môi trường
database/            Migration, seed và CLI migrate/reset
docker/              Image PHP/Apache cho môi trường local
public/              Document root và Front Controller duy nhất
resources/views/     View customer/admin theo Design System
routes/              Route web/CLI từ các slice phù hợp
scripts/             CLI nghiệp vụ từ các slice phù hợp
storage/             Log và upload runtime không commit
tests/               Unit, integration và feature test
```

Múi giờ nghiệp vụ mặc định là `Asia/Ho_Chi_Minh`. Session cookie dùng `HttpOnly`, `SameSite=Lax` và tự bật `Secure` với HTTPS. Mọi route mutation đi qua CSRF middleware; output HTML động phải dùng helper escape của View. Không dùng application framework; kiến trúc và phạm vi được khóa trong `docs/PROJECT_SPECIFICATION.md` và `docs/DECISIONS.md`.
