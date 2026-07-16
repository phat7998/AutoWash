# AutoWash Pro

AutoWash Pro là hệ thống quản lý dịch vụ chăm sóc phương tiện, đặt lịch trước và khách hàng thân thiết được xây dựng bằng Modern PHP thuần. Phiên bản đồ án cũ được lưu tại nhánh `legacy-main`.

Repository hiện hoàn thành Slice 08: Composer/PSR-4, database foundation, hạ tầng HTTP/security,
authentication/RBAC, quản lý phương tiện, danh mục dịch vụ và khung giờ. Customer xem được giá/thời lượng
theo loại xe, chọn nhiều dịch vụ và tạo booking theo booking window của tier. Backend tự tính giá, tổng thời
lượng, capacity lớn nhất, khóa mọi slot chồng lấn và lưu booking/items/reservations atomically. Admin quản lý
dịch vụ, khung giờ và vòng đời booking qua backend có validation, role guard và CSRF. Customer xem chi tiết,
hủy trước/đúng cutoff 2 giờ và xem wash history từ item snapshot; admin confirm/complete/cancel/no-show.

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

Migration runner lưu lịch sử và có advisory lock chống hai tiến trình chạy đồng thời. Seed có thể chạy lại
mà không tạo thêm tier, loại xe, phương tiện, dịch vụ, slot hoặc reward trùng.

Reset chỉ dành cho `APP_ENV=local|testing`, xóa toàn bộ dữ liệu trong database đang cấu hình và bắt buộc xác nhận rõ:

```bash
php database/reset.php --force --seed
```

Không chạy lệnh reset trên database có dữ liệu cần giữ. Seed có năm tài khoản demo cố định:

| Vai trò | Số điện thoại | Mật khẩu |
|---|---|---|
| Admin | `0900000001` | `AutoWash@123` |
| Customer Member · xe máy | `0900000002` | `AutoWash@123` |
| Customer Silver · ô tô con | `0900000003` | `AutoWash@123` |
| Customer Gold · xe tải | `0900000004` | `AutoWash@123` |
| Customer Platinum · xe khách | `0900000005` | `AutoWash@123` |

Đây là thông tin chỉ dành cho môi trường demo/local, không dùng làm secret production. Các route chính:

- `/dang-ky`: đăng ký customer; dữ liệu role từ request luôn bị bỏ qua.
- `/dang-nhap`: đăng nhập bằng số điện thoại và mật khẩu.
- `/tai-khoan`: vùng customer đã xác thực.
- `/phuong-tien`: danh sách xe của customer đang đăng nhập.
- `/phuong-tien/them`: nhập biển số thủ công và thêm xe; GET hiển thị form, POST lưu dữ liệu.
- `/phuong-tien/{id}/sua`: sửa xe đúng owner; GET hiển thị form, POST lưu dữ liệu.
- `/phuong-tien/{id}/ngung-su-dung`: chỉ nhận POST, giữ record và chuyển xe sang inactive.
- `/dich-vu`: danh mục public theo loại phương tiện; chỉ hiển thị service/cặp giá active và supported.
- `/khung-gio`: customer xem khung giờ mở và capacity units còn lại.
- `/dat-lich`: customer chọn xe, nhiều dịch vụ và khung giờ để tạo booking pending.
- `/lich-dat`: customer xem danh sách trạng thái và lịch sử rửa xe đã hoàn thành.
- `/lich-dat/{id}`: customer xem chi tiết snapshot của booking đúng owner và tự hủy khi còn ít nhất 2 giờ.
- `/admin`: vùng admin đã xác thực và kiểm tra role.
- `/admin/lich-dat`: admin xác nhận, hoàn thành, hủy có lý do/audit hoặc đánh dấu khách không đến.
- `/admin/dich-vu`: admin tạo, sửa, kích hoạt hoặc ngừng dịch vụ và cấu hình theo loại xe.
- `/admin/khung-gio`: admin tạo hoặc đóng khung giờ vận hành.
- `/dang-xuat`: chỉ nhận POST có CSRF hợp lệ.

Seed demo có slot trống, gần đầy, đầy và đóng ngày `15/01/2030`. Hai booking fixture
`DEMO_NEAR_FULL`/`DEMO_FULL` phục vụ kiểm tra cách tính capacity. Seed còn tạo ba slot liên tục vào các mốc
`+1`, `+8`, `+11`, `+13` ngày tính từ ngày chạy seed để demo booking window Member/Silver/Gold/Platinum và
booking nhiều slot. Hai fixture `DEMO_NEAR_FULL`/`DEMO_FULL` có thể được admin chuyển trạng thái để demo
confirm/complete, capacity release và wash history. Completion Slice 08 chưa cộng điểm hoặc monthly metrics;
tích hợp loyalty atomically thuộc Slice 09.

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
- HTTP/security test kiểm tra router, 404/405, CSRF, session flash/cookie, escaping, PRG, production error
  response, Auth/RBAC và ownership phương tiện.

Integration test database cần MySQL riêng có thể reset an toàn:

```bash
AUTOWASH_DB_TESTS=1 vendor/bin/phpunit tests/Integration/Database
AUTOWASH_DB_TESTS=1 vendor/bin/phpunit tests/Integration/Vehicle
AUTOWASH_DB_TESTS=1 vendor/bin/phpunit tests/Integration/CatalogSlot
AUTOWASH_DB_TESTS=1 vendor/bin/phpunit tests/Integration/Booking
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
