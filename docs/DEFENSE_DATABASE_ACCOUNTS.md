# TÀI KHOẢN DATABASE PHỤC VỤ BẢO VỆ

Database runtime được seed bằng:

```bash
composer db:seed-defense
```

Mật khẩu của toàn bộ tài khoản `DEMO` lấy từ `DEFENSE_DEMO_PASSWORD` trong `.env` local. Tài liệu này không lưu mật khẩu.

## Tài khoản quản trị

| Tên scenario | Phone | Dùng để kiểm thử |
|---|---|---|
| DEMO ADMIN 01 - Quản trị | `0888000001` | Dashboard, booking list, loyalty adjustment và audit |
| DEMO ADMIN 02 - Quản trị | `0888000002` | Kiểm thử account admin thứ hai và audit actor |

## Tài khoản customer chính

| Tên scenario | Phone | Tier | Vehicle | Booking | Điểm | Dùng để kiểm thử |
|---|---|---|---|---|---:|---|
| DEMO 01 - Khách mới | `0888100001` | Member | Không có | Không có | 0 | Empty state vehicle/booking và balance bằng 0 |
| DEMO 02 - Xe máy | `0888100002` | Silver | `78-F1 024.07` | Completed, cancelled, pending | 220 | Luồng xe máy, Standard/Premium và reward giới hạn xe máy |
| DEMO 03 - Nhiều phương tiện | `0888100003` | Gold | Xe máy, ô tô, truck, bus | Nhiều trạng thái | 230 | Chuyển đổi nhiều phương tiện và service support |
| DEMO 04 - Booking Pending | `0888100004` | Platinum | Ô tô | `DEF-BKG-0143`, `DEF-BKG-0145` pending | 240 | Pending booking và reward đang giữ |
| DEMO 05 - Booking Confirmed | `0888100005` | Member | Bus | `DEF-BKG-0142`, `DEF-BKG-0175` confirmed | 250 | Confirmed booking, slot full và reward đang giữ |
| DEMO 06 - Booking Completed | `0888100006` | Silver | Ô tô | `DEF-BKG-0001` completed | 260 | Revenue, wash history và booking completed |
| DEMO 07 - Booking Cancelled | `0888100007` | Gold | Ô tô | `DEF-BKG-0081` cancelled | 270 | Customer cancellation và reward đã release về available |
| DEMO 08 - No Show | `0888100008` | Platinum | Ô tô | `DEF-BKG-0116` no-show | 280 | No-show và historical reservation |
| DEMO 09 - Member | `0888100009` | Member | Bus | Completed/cancelled/confirmed | 290 | Tier Member |
| DEMO 10 - Silver | `0888100010` | Silver | Ô tô | Nhiều trạng thái | 300 | Tier Silver và tier-specific promotion |
| DEMO 11 - Gold | `0888100011` | Gold | Ô tô | `DEF-BKG-0177` confirmed | 310 | Tier Gold, perk cố định và reward Gold |
| DEMO 12 - Platinum | `0888100012` | Platinum | Ô tô | Nhiều trạng thái | 320 | Tier Platinum và perk phần trăm |
| DEMO 13 - Không có điểm | `0888100013` | Member | Ô tô | Completed/cancelled/no-show/confirmed | 0 | Lịch sử credit/debit dài nhưng balance bằng 0 |
| DEMO 14 - Vừa đủ đổi thưởng | `0888100014` | Silver | Ô tô | Nhiều trạng thái | 100 | Vừa đủ đổi reward `DISCOUNT_10K` |
| DEMO 15 - Thiếu một điểm | `0888100015` | Gold | Ô tô | Nhiều trạng thái | 99 | Thiếu đúng một điểm so với reward 100 điểm |
| DEMO 16 - Reward Available | `0888100016` | Platinum | Ô tô | Nhiều trạng thái | 500 | Redemption available và expired |
| DEMO 17 - Promotion | `0888100017` | Gold | Nhiều ô tô | Completed/confirmed | 370 | Promotion fixed/percentage/target/limit và stacking |
| DEMO 18 - Slot gần đầy | `0888100018` | Silver | Truck | `DEF-BKG-0141` pending | 380 | Slot còn đúng 1 capacity unit |
| DEMO 19 - Ownership A | `0888100019` | Gold | Ô tô và xe khác | Nhiều trạng thái | 390 | Ownership A; không được đọc/sửa xe của account B |
| DEMO 20 - FEFO | `0888100020` | Platinum | Nhiều xe | Completed/cancelled/no-show | 150 | Lot A 100 + Lot B 200, debit 150, allocation 100 + 50 |
| DEMO 21 - Ownership B | `0888100021` | Member | Ô tô và xe khác | Nhiều trạng thái | 410 | Ownership B; đối chiếu với account A |
| DEMO 22 - Price Snapshot | `0888100022` | Silver | Ô tô | `DEF-BKG-0011` completed | 420 | Snapshot giá Standard cũ khác catalog hiện tại |

## Tài khoản tier boundary bổ sung

| Tên scenario | Phone | Trạng thái chính |
|---|---|---|
| DEMO 23 - Thiếu spend Silver | `0888100023` | Spend 299.999, visit đạt ngưỡng Silver |
| DEMO 24 - Thiếu visit Silver | `0888100024` | Spend đạt 300.000, thiếu một visit |
| DEMO 27 - Đúng cả hai Silver | `0888100027` | Đúng spend và visit Silver |
| DEMO 28 - Có thể upgrade | `0888100028` | Member với metrics đạt Gold |
| DEMO 29 - Có thể downgrade | `0888100029` | Platinum với metrics bằng 0 |
| DEMO 30 - Giữ tier | `0888100030` | Silver đúng boundary |
| DEMO 32 - Có điểm nhưng metrics bằng 0 | `0888100032` | 800 điểm, monthly metrics bằng 0 |
| DEMO 44 - Reward chưa đủ tier | `0888100044` | Member dùng để xem reward yêu cầu Gold |
| DEMO 45 - Promotion minimum chưa đạt | `0888100045` | Order dưới minimum của promotion phần trăm |
| DEMO 46 - Promotion minimum vừa đạt | `0888100046` | Ô tô Standard đúng 100.000 |
| DEMO 50 - Booking cutoff | `0888100050` | Có cancellation đúng và dưới cutoff 2 giờ |

Biển nhập dạng `78-F1 023.07` được normalize thành `78F102307`; normalized plate này đã tồn tại trong database runtime trước khi seed và được giữ nguyên ownership lẫn display format hiện có. Seeder không chiếm lại record này; `DEMO 02` dùng biển deterministic liền kề để bảo toàn dữ liệu người dùng.

Research event dùng đúng event type đang có trong code: `booking_created`, `booking_completed`, `reward_redeemed`, `points_expired`, `tier_changed`, `promotion_used`. Cancellation/no-show nằm trong `cancellation_status` của event booking; điểm earn nằm trong `booking_completed`; tier review được biểu diễn bằng `tier_changed`.

## SQL chỉ-đọc

### Users

```sql
SELECT id, full_name, phone, role, point_balance
FROM users
WHERE full_name LIKE 'DEMO %'
ORDER BY full_name;
```

### Vehicles

```sql
SELECT vehicles.id, users.full_name, vehicle_types.code AS vehicle_type,
       vehicles.display_plate, vehicles.normalized_plate, vehicles.is_active
FROM vehicles
JOIN users ON users.id = vehicles.user_id
JOIN vehicle_types ON vehicle_types.id = vehicles.vehicle_type_id
WHERE users.full_name LIKE 'DEMO %'
ORDER BY users.full_name, vehicles.id;
```

### Bookings

```sql
SELECT bookings.booking_code, users.full_name, bookings.status,
       wash_slots.slot_date, wash_slots.start_time,
       bookings.subtotal, bookings.perk_discount,
       bookings.promotion_discount, bookings.reward_discount,
       bookings.final_price
FROM bookings
JOIN users ON users.id = bookings.user_id
JOIN wash_slots ON wash_slots.id = bookings.start_slot_id
WHERE bookings.booking_code LIKE 'DEF-BKG-%'
ORDER BY wash_slots.slot_date, wash_slots.start_time;
```

### Booking items

```sql
SELECT bookings.booking_code, services.code AS service_code,
       booking_items.service_name_snapshot,
       booking_items.vehicle_type_code_snapshot,
       booking_items.unit_price_snapshot,
       booking_items.duration_minutes_snapshot,
       booking_items.capacity_units_snapshot
FROM booking_items
JOIN bookings ON bookings.id = booking_items.booking_id
JOIN services ON services.id = booking_items.service_id
WHERE bookings.booking_code LIKE 'DEF-BKG-%'
ORDER BY bookings.booking_code, booking_items.id;
```

### Booking slot reservations

```sql
SELECT bookings.booking_code, wash_slots.slot_date,
       wash_slots.start_time, wash_slots.end_time,
       wash_slots.capacity_units,
       booking_slot_reservations.capacity_units_reserved
FROM booking_slot_reservations
JOIN bookings ON bookings.id = booking_slot_reservations.booking_id
JOIN wash_slots ON wash_slots.id = booking_slot_reservations.wash_slot_id
WHERE bookings.booking_code LIKE 'DEF-BKG-%'
ORDER BY bookings.booking_code, wash_slots.slot_date, wash_slots.start_time;
```

### Loyalty transactions

```sql
SELECT users.full_name, loyalty_transactions.id,
       loyalty_transactions.type, loyalty_transactions.points_delta,
       loyalty_transactions.remaining_points,
       loyalty_transactions.description,
       loyalty_transactions.expires_at
FROM loyalty_transactions
JOIN users ON users.id = loyalty_transactions.user_id
WHERE users.full_name LIKE 'DEMO %'
ORDER BY users.full_name, loyalty_transactions.created_at,
         loyalty_transactions.id;
```

### Loyalty allocations

```sql
SELECT users.full_name,
       loyalty_allocations.debit_transaction_id,
       loyalty_allocations.credit_transaction_id,
       loyalty_allocations.allocated_points,
       loyalty_allocations.allocated_at
FROM loyalty_allocations
JOIN loyalty_transactions AS debit
  ON debit.id = loyalty_allocations.debit_transaction_id
JOIN users ON users.id = debit.user_id
WHERE users.full_name LIKE 'DEMO %'
ORDER BY users.full_name, loyalty_allocations.id;
```

### Reward redemptions

```sql
SELECT users.full_name, rewards.code AS reward_code,
       reward_redemptions.status, reward_redemptions.points_spent,
       bookings.booking_code, reward_redemptions.redeemed_at,
       reward_redemptions.expires_at, reward_redemptions.used_at
FROM reward_redemptions
JOIN users ON users.id = reward_redemptions.user_id
JOIN rewards ON rewards.id = reward_redemptions.reward_id
LEFT JOIN bookings ON bookings.id = reward_redemptions.booking_id
WHERE users.full_name LIKE 'DEMO %'
ORDER BY users.full_name, reward_redemptions.id;
```

### Promotion usages

```sql
SELECT promotions.code AS promotion_code, users.full_name,
       bookings.booking_code, promotion_usages.discount_amount,
       promotion_usages.used_at
FROM promotion_usages
JOIN promotions ON promotions.id = promotion_usages.promotion_id
JOIN users ON users.id = promotion_usages.user_id
JOIN bookings ON bookings.id = promotion_usages.booking_id
WHERE bookings.booking_code LIKE 'DEF-BKG-%'
ORDER BY promotions.code, promotion_usages.id;
```

### LPR attempts

```sql
SELECT users.full_name, lpr_attempts.provider,
       lpr_attempts.recognized_text, lpr_attempts.normalized_text,
       lpr_attempts.confidence, lpr_attempts.status,
       lpr_attempts.image_path, lpr_attempts.created_at
FROM lpr_attempts
LEFT JOIN users ON users.id = lpr_attempts.user_id
WHERE lpr_attempts.image_path LIKE 'storage/uploads/lpr/defense/%'
ORDER BY lpr_attempts.id;
```

### Audit logs

```sql
SELECT audit_logs.action, audit_logs.target_type,
       audit_logs.target_id, users.full_name AS actor,
       audit_logs.reason, audit_logs.created_at
FROM audit_logs
LEFT JOIN users ON users.id = audit_logs.actor_user_id
WHERE audit_logs.reason LIKE 'Dữ liệu defense audit #%'
ORDER BY audit_logs.id;
```

### Research event logs

```sql
SELECT event_key, event_type, event_time, tier_code,
       vehicle_type_code, service_code, order_value,
       points_earned, points_redeemed,
       used_reward, used_promotion, cancellation_status,
       data_source
FROM research_event_logs
WHERE JSON_UNQUOTE(JSON_EXTRACT(metadata_json, '$.nguon')) = 'defense'
ORDER BY event_time, id;
```
