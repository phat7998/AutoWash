# AutoWash Pro

AutoWash Pro là hệ thống quản lý dịch vụ chăm sóc phương tiện, đặt lịch trước và khách hàng thân thiết được xây dựng bằng Modern PHP thuần. Phiên bản đồ án cũ được lưu tại nhánh `legacy-main`.

Repository hiện hoàn thành nền tảng Slice 01: Composer/PSR-4, cấu hình môi trường, PHPUnit, PSR-12 và Docker Compose. HTTP Front Controller, database nghiệp vụ và giao diện thuộc các slice sau nên chưa có route ứng dụng ở giai đoạn này.

## Yêu cầu hệ thống

- PHP 8.2 trở lên với extension PDO.
- Composer 2.
- Docker và Docker Compose nếu dùng môi trường container.
- MySQL 8 sẽ được dùng từ Slice 02; Compose hiện cấu hình MySQL 8.4.

## Cài đặt bằng PHP trên máy

```bash
composer install
cp .env.example .env
composer dump-autoload --strict-psr
```

Chỉnh các giá trị local trong `.env` khi cần. File `.env` đã được Git ignore; không đưa secret thật vào repository.

## Môi trường Docker

Tạo file môi trường rồi build và chạy hai service `web` và `mysql`:

```bash
cp .env.example .env
docker compose config
docker compose up -d --build
docker compose run --rm web composer install
```

Kiểm tra trạng thái hoặc dừng môi trường:

```bash
docker compose ps
docker compose down
```

Port mặc định của Apache là `8080`, MySQL là `3306`. Có thể đặt `APP_PORT` hoặc `DB_FORWARD_PORT` trong `.env` nếu port local đã được sử dụng. Slice 01 chưa tạo `public/index.php`; route web đầu tiên sẽ được triển khai đúng kế hoạch ở Slice 03.

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

## Cấu trúc chính

```text
app/                 Mã nguồn ứng dụng theo namespace App\
bootstrap/           Khởi tạo dependency và môi trường
config/              Cấu hình app, database và loyalty từ biến môi trường
database/            Migration và seed từ Slice 02
docker/              Image PHP/Apache cho môi trường local
public/              Document root; Front Controller thuộc Slice 03
resources/views/     View customer/admin theo Design System
routes/              Route web/CLI từ các slice phù hợp
scripts/             CLI nghiệp vụ từ các slice phù hợp
storage/             Log và upload runtime không commit
tests/               Unit, integration và feature test
```

Múi giờ nghiệp vụ mặc định là `Asia/Ho_Chi_Minh`. Không dùng application framework; kiến trúc và phạm vi được khóa trong `docs/PROJECT_SPECIFICATION.md` và `docs/DECISIONS.md`.
