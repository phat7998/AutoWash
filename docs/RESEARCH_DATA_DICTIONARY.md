# AUTO WASH PRO — RESEARCH DATA DICTIONARY

> Schema export: `1.0` — Slice 14
> Phạm vi: descriptive analytics từ transaction system hoặc synthetic dataset có nhãn nguồn.

## Câu hỏi nghiên cứu

“What factors most influence customer loyalty tier progression in smart service ecosystems?”

Dataset cung cấp dữ liệu mô tả để nhóm trình bày kết quả quan sát được. Dataset không tự chứng minh quan hệ
nhân quả, không chứa kết luận giả thuyết, p-value hoặc độ chính xác mô hình.

## Event hệ thống

| `event_type` | Thời điểm ghi | Khóa idempotency | Feature chính |
|---|---|---|---|
| `booking_created` | Booking được commit | `booking_created:{booking_id}` | Tier, loại xe, dịch vụ, lead days, order value, reward/promotion flag |
| `booking_completed` | Completion + loyalty được commit | `booking_completed:{booking_id}` | Order value, monthly metrics, points earned, reward/promotion flag |
| `reward_redeemed` | Reward redemption + debit được commit | `reward_redeemed:{redemption_id}` | Tier, points redeemed, monthly metrics |
| `points_expired` | Expire debit + allocation được commit | `points_expired:{debit_id}` | Tier, points expired, credit lot reference nội bộ |
| `tier_changed` | Tier history được commit | `tier_changed:{history_id}` | Tier trước/sau, review period, spend/visits snapshot |
| `promotion_used` | Promotion usage của booking completion được commit | `promotion_used:{booking_id}` | Tier, xe, dịch vụ, lead days, order value |

Unique constraint của `research_event_logs.event_key` chống ghi lặp. Event nghiệp vụ chạy trong cùng database
transaction với mutation tương ứng; lỗi ghi event làm rollback mutation.

## Cột CSV schema 1.0

| Cột | Kiểu/giá trị | Ý nghĩa |
|---|---|---|
| `schema_version` | `1.0` | Phiên bản cấu trúc export |
| `event_key` | string unique | Khóa sự kiện kỹ thuật; không phải user ID |
| `anonymous_user_key` | SHA-256 hex | Pseudonymous key ổn định trong hệ thống, không xuất user ID trực tiếp |
| `event_type` | event code | Loại sự kiện ở bảng trên |
| `event_time` | `YYYY-MM-DD HH:MM:SS` | Thời gian nghiệp vụ theo timezone hệ thống |
| `tier_code` | tier code | Tier tại thời điểm event |
| `tier_before_code` | nullable tier code | Tier trước review nếu áp dụng |
| `tier_after_code` | nullable tier code | Tier sau review nếu áp dụng |
| `vehicle_type_code` | nullable code | `motorbike`, `car`, `truck`, `bus` |
| `service_code` | nullable code | Một service khi event chỉ có một service |
| `service_codes` | nullable code/JSON array | Toàn bộ service code; chỉ đọc allowlisted metadata path ở event multi-service |
| `booking_lead_days` | nullable integer | Số ngày từ lúc tạo booking đến ngày dùng dịch vụ |
| `order_value` | nullable decimal string | `final_price` snapshot, không dùng float để tính |
| `monthly_spend_snapshot` | nullable decimal string | Chi tiêu tháng tại thời điểm event |
| `monthly_visits_snapshot` | nullable integer | Lượt completed trong tháng tại thời điểm event |
| `points_earned` | nullable integer | Điểm earn của event |
| `points_redeemed` | nullable integer | Điểm redeem/expire của event; đọc cùng `event_type` |
| `used_reward` | `0|1` | Event/booking có dùng reward |
| `used_promotion` | `0|1` | Event/booking có dùng promotion |
| `cancellation_status` | nullable code | `cancelled` hoặc `no_show` khi nguồn có dữ liệu |
| `return_frequency_days` | nullable integer | Số ngày từ booking completed trước của anonymous key |
| `data_source` | `system|synthetic|survey` | Nguồn record; Slice 14 không tạo survey record |

## Privacy boundary

Export dùng allowlist cột cố định và không xuất raw `metadata_json`. Không có:

- Họ tên, số điện thoại, email.
- Password hoặc password hash.
- Biển số hoặc đường dẫn ảnh LPR.
- User ID trực tiếp, session/token/secret.
- IP thô.

`anonymous_user_key` chỉ dùng để nối chuỗi event trong dataset. Không dùng key này để hiển thị danh tính.

## Synthetic dataset

Lệnh acceptance mặc định sinh 2.000 record:

```bash
php scripts/generate-synthetic-research-data.php \
  --output=storage/research/synthetic.csv \
  --count=2000 \
  --seed=20260717
```

Giả định generator:

- Deterministic theo `seed` và thứ tự record; cùng input tạo cùng CSV.
- Phân bố tuần hoàn bảo đảm đủ bốn loại xe.
- Order value, visits, lead days, reward/promotion, cancellation/no-show và return frequency được tạo bằng
  quy tắc deterministic để có dữ liệu demo đa dạng, không đại diện cho khách hàng thật.
- Mọi record ghi `data_source=synthetic`; không dùng nguồn dataset bên ngoài.
- CLI từ chối acceptance count dưới 2.000.

## Export dữ liệu hệ thống

```bash
php scripts/export-research-data.php \
  --output=storage/research/system.csv \
  --from=2026-01-01 \
  --to=2026-12-31 \
  --source=system
```

`--from`, `--to`, `--source` là tùy chọn; date dùng `YYYY-MM-DD`, source chỉ nhận
`system|synthetic|survey`. Thư mục đầu ra phải tồn tại và có quyền ghi.

## Dashboard descriptive analytics

Admin dashboard hiển thị booking tạo hôm nay theo trạng thái, revenue completed-only, capacity utilization,
tier distribution, điểm earn/redeem/expire và reward/promotion usage. Customer dashboard chỉ tải booking,
wash history, tier/point và reward của user trong session. Biểu đồ là mô tả aggregate trực tiếp; không phải
kết quả kiểm định, dự báo hoặc kết luận nguyên nhân.

## Deferred bonus work và giới hạn

Theo DEC-034, survey thật, mô hình ML, kiểm định chuyên sâu và paper/conference-format report là
OPTIONAL/SHOULD, trạng thái `Deferred bonus work`, không chặn Slice 14 hoặc release. Không được suy diễn hoặc
tự tạo survey result, accuracy, p-value, hypothesis conclusion, research conclusion hay nguồn dataset ngoài.
