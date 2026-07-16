# AUTO WASH PRO — CÁC QUYẾT ĐỊNH ĐÃ KHÓA

> Cập nhật: 2026-07-16 — Slice 10, blocker loyalty được nhóm phê duyệt
> Nguồn: đề tài `SU26SWP01`, `PROJECT_SPECIFICATION.md` và quyết định nhóm được khóa trong prompt 00B.

Các quyết định này là baseline trước Slice 01. Thay đổi sau 00B phải có decision/changelog rõ ràng.

| ID | Quyết định đã khóa | Hệ quả thiết kế | Requirement |
|---|---|---|---|
| DEC-001 | Một chi nhánh trong phạm vi nộp đồ án. | Không có branch entity/FK; multi-branch vẫn COULD. | BKG, SLOT, REP |
| DEC-002 | Priority access là booking window 7/10/12/14 ngày. | Không runtime queue, chen ngang hoặc hủy booking đã xác nhận. | BKG-01, BKG-02 |
| DEC-003 | Tier qualification dùng spend **AND** completed visits. | Chọn tier cao nhất thỏa đồng thời hai ngưỡng. | TIER-02 |
| DEC-004 | Chỉ `completed` ghi revenue, spend, visit, point, usage và research event. | Completion atomic/idempotent. | BKG-06, LOY-01, NFR-21/22 |
| DEC-005 | Earn lot hết hạn sau đúng 12 calendar months, dùng calendar-month clamp. | Nếu ngày tương ứng không tồn tại thì dùng ngày cuối tháng đích; expired khi `current_time >= expires_at`; cùng timezone hệ thống; không dùng 365 ngày. | LOY-04 |
| DEC-006 | Redeem/expire tiêu thụ theo FEFO. | Lock earn lots theo expiry sớm nhất. | LOY-03, LOY-04 |
| DEC-007 | Online payment, refund và penalty tự động khi hủy nằm ngoài phạm vi. | Không payment/refund module; cancellation không trừ tiền/điểm. | OUT, BKG-05 |
| DEC-008 | LPR luôn có manual fallback; regex không phải LPR. | Provider failure/confidence thấp không chặn nhập/sửa thủ công. | LPR-01, LPR-02 |
| DEC-009 | Một booking dùng tối đa một perk, một promotion và một reward. | Chọn benefit theo rule; không stacking tùy ý. | BKG-03, PRO-03/04 |
| DEC-010 | Redeem không giảm accumulated/monthly spend hoặc visits. | Tier metrics độc lập point balance. | LOY-03, TIER-03 |
| DEC-011 | Ledger là nguồn lịch sử; `users.point_balance` chỉ là cache/tổng hợp. | Sau mọi mutation: cache = ledger net = tổng `remaining_points` của credit lot; mọi mutation qua LoyaltyService và reconcile được. | LOY-02 |
| DEC-012 | Pure Modern PHP là quyết định kiến trúc nhóm nếu đề tài không bắt buộc stack. | PHP 8.2+, MySQL, PDO, Front Controller, Controller–Service–Repository, Composer PSR-4, Dotenv, PHPUnit; không Laravel/Symfony/Yii/full-stack framework. | NFR-05, NFR-26 |
| DEC-013 | Timezone nghiệp vụ là `Asia/Ho_Chi_Minh`. | Boundary booking/cancellation/expiry/display dùng cùng timezone. | BKG-01/05, NFR-09 |
| DEC-014 | Research export ẩn danh và phân biệt nguồn. | Không PII; synthetic không được mô tả là dữ liệu thật. | RBL-03/04, NFR-24 |
| DEC-015 | Hỗ trợ `motorbike`, `car`, `truck`, `bus` bằng bảng `vehicle_types`. | Không ENUM; seed 20/1, 40/2, 90/4, 120/5; inactive thay vì hard-delete. | VEH-04 |
| DEC-016 | Giá/thời lượng/capacity override phụ thuộc service + vehicle type. | `service_vehicle_prices`, unique pair, backend từ chối unsupported/missing. | CAT-01/02, ADM-02 |
| DEC-017 | Slot quản lý bằng `capacity_units`, không số xe; booking nhiều dịch vụ dùng tổng duration và capacity lớn nhất. | `booking_duration_minutes = sum(service durations)`; `booking_capacity_units = max(vehicle default, service overrides)`, không cộng units; giữ units trên mọi slot chồng lấn bằng transaction/lock. | CAT-02, SLOT-01/02, BKG-07 |
| DEC-018 | `loyalty_allocations` là generic allocation từ debit transaction vào credit lot. | Credit gồm `earn`, `adjust_credit`; debit gồm `redeem`, `expire`, `adjust_debit`. Mọi debit phân bổ đủ trị tuyệt đối delta; unique debit+credit. | LOY-02..04, ADM-06 |
| DEC-019 | Tier seed: Member 0/0/7/1.00; Silver 300k/2/10/1.10; Gold 800k/5/12/1.25; Platinum 1.5m/8/14/1.50. | Lưu DB, admin cấu hình; `point_rate` là decimal. | TIER-02, ADM-01 |
| DEC-020 | Earn formula: `floor(floor(final_price/10.000) × point_rate)`. | Gold 250.000 VND earn 31; completion chỉ xử lý một lần. | LOY-01, BKG-06 |
| DEC-021 | Customer tự hủy khi còn ít nhất 2 giờ; đúng 2 giờ được phép. | Dưới 2 giờ cần admin ngoại lệ; hủy giải phóng capacity, không earn. | BKG-05 |
| DEC-022 | Reward là data cấu hình; seed 100/250/300/400/700 điểm theo danh sách 00B. | Có giới hạn service/vehicle type ở backend; vehicle restriction dùng join table. | RWD-01..04, ADM-04 |
| DEC-023 | Promotion có thể target tier, service và vehicle type. | Dùng association tables; Silver+ là Silver/Gold/Platinum, không hard-code rải rác. | PRO-01/02/05 |
| DEC-024 | LPR dùng `LprProviderInterface`, `MockLprProvider`, future `ExternalLprProvider`. | Không tuyên bố self-trained/production LPR nếu chưa có evidence. | LPR-02 |
| DEC-025 | Synthetic dataset tối thiểu 2.000 records, bao phủ bốn loại xe. | Deterministic seed, research fields đầy đủ, no-PII CSV. | RBL-02..04 |
| DEC-026 | Performance target: 10.000 bookings, 20 VU, read P95 <1s, write/report P95 <2s, error <1%. | Mục tiêu kiểm thử đồ án; loại latency external LPR; phải ghi môi trường. | NFR-02 |
| DEC-027 | `docs/DESIGN_SYSTEM.md` là nguồn bắt buộc trước mọi UI. | Customer/Admin khác layout nhưng dùng chung token/component foundation. | NFR-01, NFR-03 |
| DEC-028 | Sản phẩm được mô tả là “phạm vi hoàn thiện để nộp đồ án”. | Không gọi toàn dự án là prototype hoặc MVP sơ sài. | NFR-06, NFR-25 |
| DEC-029 | Dùng active/inactive để giữ lịch sử vehicle type, vehicle, service price và config. | Không hard-delete record đang được tham chiếu; chưa áp dụng soft-delete toàn cục. | VEH-04, ADM-07 |
| DEC-030 | Loyalty không dùng reversal ở baseline hiện tại. | Cancellation trước completion không tạo earn; hậu kiểm completed dùng adjust có audit cho đến khi có requirement reversal riêng. | LOY-02, ADM-06 |
| DEC-031 | Hỗ trợ biển số dân sự Việt Nam thông dụng theo pattern chuẩn hóa tập trung. | Uppercase, bỏ space/`-`/`.`; unique `normalized_plate`; pattern `2 chữ số + 1–2 chữ cái + 4–5 chữ số`; các nhóm biển đặc biệt nằm ngoài phạm vi. | VEH-01/02, LPR-01 |
| DEC-032 | Adjustment tách thành `adjust_credit` và `adjust_debit`; adjustment âm vượt available points bị từ chối, không clamp. | Credit dương mặc định không hết hạn và dùng sau lot có expiry theo FIFO; debit âm dùng cùng FEFO, allocation, reason/audit và locking; có thể tham chiếu source transaction. | LOY-02..04, ADM-06, NFR-21/22 |
| DEC-033 | Survey/ML/paper có trạng thái **External academic deliverable — Pending lecturer confirmation**. | Không chặn Slice 01; vẫn làm research log, CSV ẩn danh, synthetic data; không bịa survey/kết quả/accuracy/kết luận; checkpoint trước Research/RBL chuyên sâu. | RBL-01..05 |

## Reward seed ban đầu

| Reward | Points |
|---|---:|
| Giảm 10.000 VND | 100 |
| Giảm 30.000 VND | 250 |
| Miễn phí rửa tiêu chuẩn xe máy | 300 |
| Tặng dịch vụ bổ sung | 400 |
| Giảm 100.000 VND | 700 |

## Mâu thuẫn đã giải quyết

- Baseline Slice 00 có motorbike/car dạng ENUM; DEC-015 thay bằng bốn loại cấu hình. Đề tài gốc thiên về motorbike nhưng không cấm mở rộng loại xe.
- Baseline gọi `loyalty_allocations` là đề xuất; DEC-018 đã duyệt.
- Baseline chưa có cancellation cutoff; DEC-021 khóa 2 giờ.
- Baseline dùng `points_per_unit` nguyên; DEC-019/020 thay bằng rate decimal và công thức floor hai bước.
- Baseline NFR performance định tính; DEC-026 khóa mục tiêu định lượng.
- Từ “MVP/prototype” chỉ còn được dùng khi trích dẫn lịch sử; phạm vi sản phẩm dùng DEC-028.
- Q-016 được DEC-017 khóa: duration cộng tổng, capacity lấy max và giữ trên mọi slot chồng lấn.
- Q-017 được DEC-005 khóa bằng calendar-month clamp và boundary `current_time >= expires_at`.
- Q-018 được DEC-031 khóa cho biển số dân sự Việt Nam thông dụng; validator tập trung, không đồng nhất regex với LPR.
- Q-019 được DEC-032 khóa: adjust âm vượt available points bị từ chối, không clamp.
- Blocker Slice 10 được nhóm phê duyệt: tổng quát hóa earn lot thành credit lot và bắt buộc mọi debit,
  kể cả adjustment âm, phải có allocation; migration 007 backfill adjustment lịch sử và fail-fast nếu không reconcile.
- Q-020 chuyển thành xác nhận deliverable học thuật bên ngoài theo DEC-033 và không chặn Slice 01.

## Ngoài phạm vi vẫn giữ

- Payment/refund/wallet, accounting/full inventory, microservices/Kafka/Kubernetes, native app, facial recognition.
- AI personalization, multi-branch, SMS thật, real-time và self-trained LPR không được tự nâng thành MUST.
