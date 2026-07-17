# AUTO WASH PRO — KỊCH BẢN DEMO VÀ BẢO VỆ

> Baseline release: Slice 15, 2026-07-17. Thời lượng đề xuất: 20–25 phút.

## 1. Chuẩn bị môi trường sạch

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec web composer install
docker compose exec web php database/reset.php --force --seed
docker compose exec -e AUTOWASH_DB_TESTS=1 web composer check
```

Kỳ vọng: MySQL healthy; reset/migrate/seed thành công; full suite không fail/skip khi bật
`AUTOWASH_DB_TESTS=1`. Mở `http://localhost:8080/health` nhận JSON `status=ok`.

Tài khoản demo dùng chung mật khẩu `AutoWash@123`:

| Vai trò/kịch bản | Số điện thoại |
|---|---|
| Admin | `0900000001` |
| Member, xe máy, có point lot hết hạn/sắp hết hạn | `0900000002` |
| Silver, ô tô con | `0900000003` |
| Gold, xe tải | `0900000004` |
| Platinum, xe khách | `0900000005` |

### Kiểm tra giao diện sản phẩm trước luồng nghiệp vụ

1. Mở `/` khi chưa đăng nhập: xác nhận hero “Chăm sóc phương tiện, chủ động từng khung giờ”, hai CTA, bốn
   nhóm phương tiện, dịch vụ, quy trình, quyền lợi thành viên, FAQ và footer; không có nội dung kỹ thuật.
2. Kiểm tra ở 360px, 768px, 1024px và 1440px: Guest/Customer menu không tràn; Admin dưới 1024px dùng drawer.
3. Đăng nhập Customer rồi mở `/`: kỳ vọng `303` tới `/tai-khoan`, không có menu quản trị.
4. Đăng nhập Admin rồi mở `/`: kỳ vọng `303` tới `/admin`, sidebar có đủ Lịch đặt, Dịch vụ, Khung giờ,
   Khách hàng & điểm, Xét hạng, Hạng & quyền lợi, Khuyến mãi, Quà tặng và Báo cáo.
5. Mở route không tồn tại và thử truy cập sai quyền: error page giữ đúng khu vực, có CTA quay lại và không lộ
   chi tiết kỹ thuật.

## 2. DEMO-01 — Auth, RBAC, phương tiện và LPR fallback

1. Đăng nhập Member tại `/dang-nhap`; xác nhận session chuyển sang `/tai-khoan`.
2. Mở `/phuong-tien`, thêm biển `59A-999.99`, chọn xe máy. Kỳ vọng lưu normalized `59A99999`.
3. Thử lại `59A 99999`. Kỳ vọng lỗi trùng từ backend, không tạo record thứ hai.
4. Tải JPEG/PNG/WebP tại `/phuong-tien/them`; provider mock chỉ gợi ý text/confidence. Sửa text rồi xác nhận.
5. Đổi `LPR_PROVIDER` sai hoặc upload file giả MIME. Kỳ vọng không lưu file/xe và form nhập tay vẫn dùng được.
6. Với customer, mở `/admin`. Kỳ vọng 403; không thấy dữ liệu quản trị.

## 3. DEMO-02 — Booking window theo tier

1. Member `0900000002` mở `/dat-lich` và chọn slot ở mốc `+8 ngày`. Kỳ vọng lỗi vượt quyền 7 ngày.
2. Silver `0900000003` chọn slot `+8 ngày`. Kỳ vọng tạo được booking.
3. Gold `0900000004` chọn `+11 ngày`; Platinum `0900000005` chọn `+13 ngày`. Kỳ vọng đều hợp lệ.
4. Trên form, sửa giá/discount/capacity bằng DevTools. Kỳ vọng backend bỏ qua, tải cấu hình DB và lưu snapshot.

### DEMO-02A — Service group selection correction

1. Mở `/dat-lich`: phần “Chọn một gói rửa chính” dùng radio cho Standard/Premium; “Dịch vụ bổ sung — không
   bắt buộc” dùng checkbox cho Tire Care/Engine Clean.
2. Chọn Premium rồi Standard. Kỳ vọng radio tự bỏ lựa chọn cũ; booking item chỉ lưu package cuối và add-on
   thực sự chọn, không tự thêm Standard vào Premium.
3. Dùng DevTools/resend POST để gửi đồng thời ID Standard + Premium. Kỳ vọng HTTP 422 với “Chỉ được chọn một
   gói rửa chính: Rửa tiêu chuẩn hoặc Rửa cao cấp.” và không tạo booking/item/reservation/event/audit.
4. Gửi add-on-only hoặc không có package. Kỳ vọng 422 “Vui lòng chọn một gói rửa chính.”
5. Admin mở `/admin/dich-vu`: list/form hiển thị group và policy; bỏ group hoặc gửi group inactive bị backend
   từ chối. Audit service có group trước/sau.
6. Giải thích migration 010: bốn capacity override catalog là null, booking mới dùng vehicle default; booking
   lịch sử và snapshot price/duration/capacity không bị sửa hoặc revalidate.

## 4. DEMO-03 — Slot race và multi-slot

1. Dùng fixture `DEMO_NEAR_FULL` ngày `15/01/2030` để giải thích capacity units; xe tải giữ 4/5 units.
2. Chạy `BookingFlowTest` case concurrency hai tiến trình.
3. Kỳ vọng chỉ request đủ capacity commit; request còn lại nhận `SlotFullException`; không có reservation rác.
4. Chọn dịch vụ dài qua nhiều slot. Kỳ vọng duration là tổng service, capacity là max và cùng units được giữ ở mọi slot chồng lấn.

## 5. DEMO-04 — Lifecycle, completion và loyalty

1. Admin mở `/admin/lich-dat`, xác nhận rồi hoàn thành một booking pending.
2. Kỳ vọng status `completed`, revenue/metrics/point/event/usage ghi cùng transaction.
3. Gửi lại thao tác complete. Kỳ vọng transition bị chặn; point, spend, visit, promotion và event không tăng lần hai.
4. Guest mở `/diem-thuong` nhận `303` về `/dang-nhap`. Customer đăng nhập, dùng cả menu “Điểm” và link
   “Xem sổ giao dịch” trên `/tai-khoan`; `/diem-thuong` trả `200`, chỉ hiện balance/ledger owner, không có
   form điều chỉnh. Thêm `?user_id=<id-khác>` không đổi owner; path giả không tồn tại.
5. Customer mở `/admin/diem-thuong` nhận `403`; Admin mở cùng URL nhận `200` và form adjustment vẫn có CSRF.
6. Customer xem `/lich-dat`; chỉ thấy dữ liệu owner và item snapshot. Tạo booking khác rồi hủy khi còn ít
   nhất 2 giờ. Kỳ vọng capacity/reward được trả; không earn, không penalty.

## 6. DEMO-05 — Redeem FEFO và metrics hạng

1. Ghi nhận `monthly_spend`/`monthly_visits` của Member trước thao tác.
2. Mở `/doi-thuong`, đổi reward đủ điểm.
3. Kỳ vọng debit và redemption atomically; allocation dùng lot có expiry sớm nhất trước lot không expiry.
4. Xác nhận point balance giảm nhưng monthly spend/visits không đổi.
5. Chạy `php scripts/reconcile-loyalty.php`; kỳ vọng mọi user `KHỚP`.

## 7. DEMO-06 — Expiry và monthly review idempotent

```bash
php scripts/expire-points.php
php scripts/expire-points.php
php scripts/monthly-review.php
php scripts/monthly-review.php
```

Fresh seed có một earn lot 50 điểm đã qua hạn và một lot 150 điểm hết hạn trong 20 ngày. Lần expiry đầu xử lý
50 điểm; lần hai xử lý 0. Monthly review lần đầu xử lý bốn customer: Member lên Gold, Silver giữ hạng, Gold
về Member, Platinum giữ hạng; lần hai bị từ chối vì run completed. Point balance không bị reset.

## 8. DEMO-07 — Promotion, perk và reward checkout

1. Admin mở `/admin/hang-thanh-vien`, `/admin/promotion`, `/admin/reward` và giải thích config trong DB.
2. Member tạo booking: không nhận promotion Silver+.
3. Silver/Gold/Platinum tạo cùng selection: backend chọn tối đa một perk, một promotion tốt nhất và một reward.
4. Kỳ vọng discount có cap, final không âm, snapshot không đổi khi config về sau thay đổi.
5. Thử cạnh tranh lượt promotion cuối bằng test concurrency. Kỳ vọng không vượt total/per-user limit.

## 9. DEMO-08 — Dashboard và research export

1. Customer mở `/tai-khoan`: tier/point/booking/history/reward đúng owner.
2. Admin mở `/admin`: bookings hôm nay, completed-only revenue, capacity, tier/point/reward/promotion aggregate.
3. Chạy:

```bash
php scripts/generate-synthetic-research-data.php \
  --output=storage/research/synthetic.csv --count=2000 --seed=20260717
php scripts/export-research-data.php \
  --output=storage/research/system.csv --source=system
```

4. Kỳ vọng synthetic có 2.000 record, đủ bốn vehicle type và cùng seed cho cùng nội dung. CSV system dùng
allowlist, UTF-8, anonymous key; không có name/phone/email/password/hash/plate/raw IP/user ID trực tiếp.

## 10. Kết thúc demo

```bash
composer release:audit
git status --short
```

Trình bày `docs/PERFORMANCE_REPORT.md`, `docs/KNOWN_LIMITATIONS.md` và release checklist. Không gọi mock LPR
là OCR production, không gọi descriptive dashboard là ML và không mô tả target local thành SLA thương mại.
