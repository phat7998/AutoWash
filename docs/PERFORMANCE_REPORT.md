# AUTO WASH PRO — BÁO CÁO HIỆU NĂNG NFR-02

> Chạy thật ngày 2026-07-17; workload HTTP qua Apache, database MySQL thật. Đây là target đồ án, không phải SLA.

## Môi trường

- Application: Docker `php:8.2-apache`, PHP 8.2.32; Apache tại `http://127.0.0.1:8081`.
- Database: MySQL 8.4.10 trong Docker, InnoDB/utf8mb4, native prepared statement.
- Workload runner: PHP CLI 8.5.8, `curl` + `pcntl`.
- Máy: Linux 6.18.38, Intel Core i5-8365U 1.60 GHz.
- Dataset: 10.000 completed booking hợp lệ, booking item/reservation snapshot; 20 customer/vehicle/credit lot
  riêng và một future slot đủ capacity.
- Concurrency: 20 process bắt đầu gần đồng thời; mỗi VU login, đọc catalog/slot/history, tạo một booking,
  redeem một reward; 20 admin session đọc report aggregate.

## Kết quả

| Endpoint/metric | Samples | P95 | Max | Target | Kết quả |
|---|---:|---:|---:|---:|---|
| Login | 20 | 189,36 ms | 193,10 ms | <1.000 ms | Pass |
| Xem dịch vụ | 20 | 96,49 ms | 99,25 ms | <1.000 ms | Pass |
| Xem slot | 20 | 139,00 ms | 139,91 ms | <1.000 ms | Pass |
| Booking history (500/user) | 20 | 142,89 ms | 161,15 ms | <1.000 ms | Pass |
| Tạo booking | 20 | 203,33 ms | 204,54 ms | <2.000 ms | Pass |
| Redeem reward | 20 | 98,31 ms | 112,06 ms | <2.000 ms | Pass |
| Admin report | 20 | 69,17 ms | 73,16 ms | <2.000 ms | Pass |

- Tổng request được đo: 140.
- Error: 0; error rate 0% (<1%).
- Thời gian toàn workload: 1,052 giây.
- External LPR latency không nằm trong workload theo NFR-02/DEC-026.

## Cách tái lập

Các lệnh sau chỉ dùng database local/testing và bắt đầu bằng reset có chủ đích:

```bash
php database/reset.php --force --seed
APP_ENV=testing php scripts/prepare-performance-data.php --bookings=10000 --users=20
APP_ENV=testing php scripts/run-performance-test.php \
  --base-url=http://127.0.0.1:8080 --vus=20 \
  --output=storage/performance/result.json
```

Nếu runner ở host còn MySQL chạy trong Docker, đặt `DB_HOST=127.0.0.1` và `DB_PORT` bằng forwarded port.
JSON runtime được ghi vào `storage/performance/` và bị Git ignore; bảng trên là evidence release đã review.

## Giới hạn phép đo

- Một lần chạy local, không đo nhiều region, network WAN, autoscaling hoặc soak test dài.
- Mỗi VU thực hiện một mutation booking/reward để tránh làm sai invariant active-booking per vehicle.
- Target và conclusion chỉ áp dụng cấu hình/dataset ghi trên; không suy rộng thành năng lực production.
