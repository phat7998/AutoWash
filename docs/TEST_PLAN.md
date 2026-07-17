# AUTO WASH PRO — TEST PLAN

> Phiên bản: UI/UX Productization Pass, 2026-07-17
> Trạng thái release: toàn bộ test MUST đã có evidence Slice 15 trong `REQUIREMENT_TRACEABILITY.md`,
> `IMPLEMENTATION_STATUS.md` và `PERFORMANCE_REPORT.md`.

## 1. Chiến lược và nguyên tắc

- Test theo requirement ID; một test có thể chứng minh nhiều acceptance criteria nhưng mỗi requirement phải có ít nhất một test dự kiến.
- Unit test kiểm tra rule thuần; integration test kiểm tra database/transaction/locking; feature test kiểm tra HTTP journey; security test tập trung trust boundary; demo test là kịch bản bảo vệ có expected result.
- Test tiền dùng giá trị integer/decimal string, không float. Test ngày cố định timezone `Asia/Ho_Chi_Minh`.
- Concurrency và idempotency phải test bằng database thật MySQL 8 ở các luồng slot, complete, redeem, expiry, monthly review và promotion usage.
- Optional test chỉ bắt buộc nếu module optional được bật. Không dùng mock LPR để tuyên bố mô hình production đã hoạt động.

## 2. Unit test

| Test ID | Nội dung | Requirement | Expected chính |
|---|---|---|---|
| UT-VEH-01 | Normalize/validate biển số theo bảng input hợp lệ/sai | VEH-01, LPR-01 | Trim/uppercase/bỏ separator đúng; invalid bị từ chối |
| UT-BKG-01 | Booking window ở -1, 0, đúng 7/10/12/14 và vượt 1 ngày | BKG-01, BKG-02 | Boundary đúng timezone; bằng giới hạn được phép |
| UT-BKG-03 | Price calculator và discount floor | BKG-03 | Tải giá backend; final không âm; đúng giới hạn stacking |
| UT-LOY-01 | Công thức earn point | LOY-01 | `floor(floor(final/10.000) × rate)`; 250k × 1.25 = 31; giá 0 = 0 |
| UT-TIER-02 | Tier qualification | TIER-02 | Spend AND visits; chọn rank cao nhất |
| UT-RWD-01 | Reward eligibility | RWD-01 | Active/time/tier được kiểm tra |
| UT-RWD-03 | Reward discount theo loại/service/max | RWD-03 | Chỉ áp đúng service và cap |
| UT-PRO-02 | Promotion eligibility và boundary thời gian | PRO-02 | Đủ toàn bộ điều kiện mới hợp lệ |
| UT-PRO-03 | Chọn promotion tốt nhất | PRO-03 | Discount lớn nhất; hòa thì end sớm hơn |
| UT-PRO-04 | Chọn perk tốt nhất | PRO-04 | Một perk lợi nhất, snapshot đúng |
| UT-NFR-09 | Clock/timezone boundary | NFR-09 | Ngày/giờ nghiệp vụ ở Asia/Ho_Chi_Minh |
| UT-NFR-10 | Money arithmetic | NFR-10 | Không sai số float, làm tròn theo rule |

## 3. Integration test

| Test ID | Nội dung | Requirement | Expected chính |
|---|---|---|---|
| IT-AUTH-01 | Register và unique phone | AUTH-01 | Hash BCRYPT; role luôn customer; duplicate rollback |
| IT-AUTH-02 | Login đúng/sai/disabled | AUTH-02 | Verify đúng; generic error; regenerate session |
| IT-AUTH-04 | Logout | AUTH-04 | Session/cookie vô hiệu; request lặp an toàn |
| IT-VEH-02 | Duplicate normalized plate | VEH-02 | DB unique được bắt thành domain error |
| IT-VEH-03 | Ownership query/update | VEH-03 | User A không đọc/sửa/đặt bằng xe B |
| IT-CAT-01 | Catalog theo active/type và price DB | CAT-01, CAT-02 | Cặp supported/active tồn tại; client price bị bỏ qua |
| IT-CAT-GROUP-01 | Fresh migrate/seed và legacy backfill service groups | CAT-01, ADM-07 | Đúng hai group/bốn mapping; service ID và booking/research snapshot cũ bất biến |
| IT-CAT-GROUP-02 | Admin tạo/sửa service với group | ADM-02, ADM-08 | Thiếu/invalid/inactive group bị chặn; audit before/after có group |
| IT-CAT-CAP-01 | Capacity correction catalog | CAT-02, SLOT-01 | Bốn service override null; booking mới dùng vehicle default; snapshot cũ không đổi |
| IT-SLOT-01 | Slot closed/past/thiếu capacity units | SLOT-01 | Requested units tính backend; slot không hợp lệ bị từ chối |
| IT-SLOT-02 | Hai transaction tranh capacity cuối | SLOT-02 | Tổng units không vượt slot |
| IT-BKG-04 | State transition matrix | BKG-04 | Chỉ transition khai báo được commit |
| IT-BKG-05 | Cancel boundary/capacity/reward restore | BKG-05 | ≥2h cho phép, <2h chặn customer; capacity/reward đúng |
| IT-BKG-06 | Complete transaction/idempotency | BKG-06 | Status, metrics, point, usage, event atomic; không lặp |
| IT-BKG-GROUP-01 | Standard/Premium đơn và package + add-on | CAT-01, BKG-03/07 | Đúng một package pass; nhiều add-on pass; không cộng giá/duration trùng |
| IT-BKG-GROUP-02 | Hai package/add-on-only/empty/POST bypass | CAT-01, NFR-19/20 | 422 tiếng Việt; không booking/item/reservation/research/audit rác |
| IT-BKG-GROUP-03 | Promotion/reward/perk theo service ID | PRO-05, RWD-03/04 | Standard target không lan Premium; reward Standard/Tire đúng item; global perk giữ nguyên |
| IT-LOY-02 | Ledger/balance consistency | LOY-02 | Mutation atomic; balance không âm; reconcile bằng nhau |
| IT-LOY-03 | Redeem FEFO/generic allocation/concurrency | LOY-03 | Qua earn + adjustment credit; allocation đúng; rollback khi thiếu; metrics không đổi |
| IT-LOY-04 | Expiry calendar/allocation/idempotency | LOY-04 | Chỉ expire remaining earn credit; transaction/allocation duy nhất |
| IT-TIER-01 | Chọn kỳ tháng vừa kết thúc | TIER-01 | Review period chính xác và unique |
| IT-TIER-03 | Upgrade/downgrade và snapshot/reset | TIER-03 | Nhiều bậc; metrics reset; point giữ nguyên |
| IT-TIER-04 | Monthly review lặp/failure recovery | TIER-04 | Mỗi user/period một history; completed run không lặp |
| IT-RWD-02 | Redeem record + ledger | RWD-02 | Cùng transaction; lỗi rollback toàn bộ |
| IT-RWD-04 | Ownership/use-once/concurrency | RWD-04 | Không dùng chéo; chỉ một lần thành công |
| IT-PRO-01 | Mapping Silver+ | PRO-01 | Silver/Gold/Platinum eligible, Member không |
| IT-PRO-02 | Total/per-user limit cạnh tranh | PRO-02 | Counter không vượt limit |
| IT-PRO-03 | Persist best promotion snapshot | PRO-03 | Một promotion, kết quả ổn định |
| IT-PRO-04 | Persist best perk snapshot | PRO-04 | Một perk, không đổi theo config về sau |
| IT-ADM-01 | Constraints tier CRUD/inactive | ADM-01 | Không âm/trùng/xóa tier đang dùng |
| IT-ADM-02 | Service update và price snapshot | ADM-02, ADM-07 | Giá lịch sử không đổi |
| IT-ADM-03 | Slot admin validation | ADM-03 | Từ chối capacity/time/date/duplicate sai |
| IT-ADM-04 | Reward admin validation | ADM-04 | Type/value/tier/service hợp lệ |
| IT-ADM-05 | Promotion admin validation | ADM-05 | Time/value/limit/target hợp lệ |
| IT-ADM-06 | Point adjust credit/debit atomic + audit | ADM-06 | Reason/actor; debit FEFO; cache = ledger net = remaining credit lots |
| IT-ADM-08 | Config log | ADM-08 | Log có actor/action, không có secret |
| IT-REP-02 | Report aggregation | REP-02 | Revenue chỉ completed; số liệu đúng fixture |
| IT-RBL-02 | Event persistence/idempotency | RBL-02 | Snapshot đủ data dictionary; completion không ghi lặp |
| IT-RBL-04 | Synthetic deterministic seed | RBL-04 | ≥2.000 record, bốn vehicle types, cùng seed cùng kết quả |
| IT-NFR-08 | Migrate/seed/reset từ DB trống | NFR-08 | Repeatable, không tạo dữ liệu rác |
| IT-NFR-21 | Failure injection transaction | NFR-21 | Không có partial state ở mọi critical flow |
| IT-NFR-22 | Race/idempotency suite | NFR-22 | Constraint/lock giữ invariant |

## 4. Feature test

| Test ID | Nội dung | Requirement | Expected chính |
|---|---|---|---|
| FT-AUTH-03 | Guest/customer/admin route matrix | AUTH-03 | Backend trả 403/redirect an toàn |
| FT-BKG-01 | Customer tạo booking theo tier | BKG-01, BKG-02 | Các tier thấy đúng window |
| FT-BKG-03 | Checkout bị sửa giá/discount client | BKG-03 | Giá server thắng |
| FT-REP-01 | Customer dashboard | REP-01 | Chỉ dữ liệu owner; có empty state |
| FT-LOY-01 | Customer mở `/diem-thuong` khi dashboard factory cùng được đăng ký | LOY-02, REP-01 | Guest 303; customer 200 owner-only; link đúng; empty state và loại/dấu giao dịch đúng |
| FT-REP-02 | Admin dashboard | REP-02 | Chỉ admin; aggregate đúng |
| FT-LPR-01 | Nhập biển số thủ công end-to-end | LPR-01 | Hoạt động độc lập recognizer |
| FT-LPR-02 | Upload/recognize/confirm/fallback | LPR-02 | Success/failure/timeout đều quay về manual an toàn |
| FT-RBL-03 | Export CSV privacy | RBL-03 | Không có name/phone/email/hash/plate/raw IP |
| FT-RBL-04 | Export phân biệt nguồn | RBL-04 | data_source đúng và filter được |
| FT-RBL-05 | Survey link/consent boundary | RBL-05 | Không nhập survey thành transaction system |
| FT-NFR-03 | Empty/error state luồng chính | NFR-03 | Thông báo rõ, không lộ kỹ thuật |
| FT-NFR-25 | Setup từ môi trường sạch | NFR-25 | Làm đúng README và chạy được |

## 5. Security test

| Test ID | Nội dung | Requirement | Expected chính |
|---|---|---|---|
| ST-AUTH-01 | Role injection khi register | AUTH-01 | Request role bị bỏ qua |
| ST-AUTH-02 | User enumeration/session fixation | AUTH-02 | Generic error; session ID đổi |
| ST-AUTH-03 | Admin route/IDOR matrix | AUTH-03, NFR-15 | Không vượt role/ownership |
| ST-LOY-01 | Query/path/POST/session-data tampering trên sổ điểm customer | LOY-02, NFR-15 | Không chọn được owner khác; customer không có mutation/adjustment và không vào admin loyalty |
| ST-CSRF-01 | Thiếu/sai/replay token trên mutation | AUTH-04, NFR-13 | Request bị từ chối |
| ST-SQL-01 | Payload SQL injection ở input chính | NFR-11 | Prepared statement, không đổi query |
| ST-XSS-01 | Stored/reflected XSS ở field hiển thị | NFR-12 | Output được escape |
| ST-SESSION-01 | Cookie/session lifecycle | NFR-14 | Flags đúng môi trường; logout vô hiệu |
| ST-UPLOAD-01 | MIME giả, oversized, path/file execution | NFR-16, LPR-02 | Upload bị từ chối/an toàn |
| ST-SECRET-01 | Scan secret và sensitive log | NFR-17 | Không secret/password/token/PII |
| ST-ERROR-01 | Trigger 4xx/5xx production | NFR-18 | Không stack/secret; log có request ID |
| ST-VALIDATION-01 | Tamper ownership/status/type | NFR-19 | Backend từ chối |
| ST-PRICE-01 | Tamper price/point/tier/reward/promo | NFR-20 | Backend tải lại nguồn tin cậy |
| ST-PRIVACY-01 | Scan CSV research | RBL-03, NFR-24 | Không PII, anonymous key không trực tiếp là user ID |

## 6. Demo/acceptance test

| Demo ID | Kịch bản | Requirement chính | Expected |
|---|---|---|---|
| DEMO-01 | Register → login → thêm xe | AUTH, VEH, LPR-01 | Tài khoản customer, xe normalized |
| DEMO-02 | Member vượt 7 ngày; Silver/Gold đặt xa hơn | BKG-01, BKG-02 | Reject/accept đúng window |
| DEMO-03 | Hai request tranh slot cuối | SLOT-02 | Một thành công, một báo vừa hết chỗ |
| DEMO-04 | Admin confirm/complete; customer thấy point/history | BKG-04, BKG-06, LOY-01, REP-01 | Chỉ complete cộng một lần |
| DEMO-05 | Redeem FEFO rồi chứng minh tier metrics không giảm | LOY-03, RWD | Lot đúng, metrics giữ nguyên |
| DEMO-06 | Chạy expiry và monthly review hai lần | LOY-04, TIER | Lần hai không xử lý lặp |
| DEMO-07 | Promotion Silver+ + best perk/reward checkout | PRO, BKG-03 | Member không nhận; discount snapshot giải thích được |
| DEMO-08 | Admin dashboard và export research CSV | REP-02, RBL | Revenue completed-only; CSV không PII |

## 7. Quality/NFR verification

| Test ID | Kiểm tra | Requirement |
|---|---|---|
| QT-NFR-01 | Manual viewport desktop/mobile các màn hình chính | NFR-01 |
| QT-NFR-02 | 10.000 bookings, 20 VU; read P95 <1s, booking/redeem/report <2s, error <1%; ghi môi trường | NFR-02 |
| QT-NFR-04 | PSR-12 lint/formatter | NFR-04 |
| QT-NFR-05 | Static review: SQL/formula/layer dependency | NFR-05 |
| QT-NFR-06 | Scan TODO/placeholder/dead debug ở luồng MUST | NFR-06 |
| QT-NFR-07 | Script/checklist đối chiếu RTM evidence | NFR-07 |
| QT-NFR-23 | Review transaction không bao network call dài | NFR-23 |
| QT-NFR-26 | Kiểm tra version/Composer và không có framework | NFR-26 |
| QT-RBL-01 | Review research question/objectives giữ nguyên nghĩa đề tài SU26SWP01 | RBL-01 |

### 7.1. UI/UX Productization regression

| Test ID | Kiểm tra | Expected |
|---|---|---|
| FT-UI-01 | Guest `GET /` | `200`, có hero/CTA/bốn loại xe; không có CSRF, Front Controller, PRG/scaffold |
| FT-UI-02 | Customer/Admin `GET /` | `303` tới `/tai-khoan` hoặc `/admin` đúng role |
| FT-UI-03 | Navigation theo role | Customer không có route admin; sidebar Admin có đủ module đã triển khai |
| FT-UI-04 | CTA và link điều hướng | CTA landing đúng `/dat-lich`, `/dich-vu`; link chính không trả 404 sau đăng nhập phù hợp |
| FT-UI-05 | Error page | 403/404/405/500 đúng status, product shell phù hợp, không stack trace production |
| FT-UI-06 | Form regression | Giữ `_csrf_token`, field name/action và radio/checkbox policy của booking |
| QT-UI-01 | Viewport 360/768/1024/1440 | Menu không tràn; admin drawer; grid reflow; table admin cuộn có chủ đích |
| QT-UI-02 | Accessibility source/manual | Skip link, focus visible, label, heading, dialog confirm, reduced motion |

## 8. Test case bổ sung theo quyết định 00B

### 8.1. Vehicle

| Case ID | Loại | Test case | Expected |
|---|---|---|---|
| VEH-00B-01 | Integration | Tạo motorbike | FK type hợp lệ, lưu plate normalized |
| VEH-00B-02 | Integration | Tạo car | Thành công với type active |
| VEH-00B-03 | Integration | Tạo truck | Thành công với type active |
| VEH-00B-04 | Integration | Tạo bus | Thành công với type active |
| VEH-00B-05 | Integration | Hai plate khác display nhưng trùng sau normalize | Record thứ hai bị từ chối bằng domain error |
| VEH-00B-06 | Integration | vehicle_type_id không tồn tại | Backend/DB từ chối |
| VEH-00B-07 | Integration | vehicle type inactive | Backend từ chối tạo/đặt mới |
| VEH-00B-08 | Security | Customer truy cập xe người khác | 403/not found an toàn |
| VEH-00B-09 | Integration | Xóa vehicle type đang được tham chiếu | Bị từ chối; inactive vẫn giữ lịch sử |

### 8.2. Service pricing

| Case ID | Loại | Test case | Expected |
|---|---|---|---|
| CAT-00B-01 | Integration | Một service có giá khác nhau cho bốn vehicle types | Backend chọn đúng cặp |
| CAT-00B-02 | Security | Frontend gửi price giả | Giá DB thắng |
| CAT-00B-03 | Integration | Service không hỗ trợ bus | Lỗi nghiệp vụ rõ ràng |
| CAT-00B-04 | Integration | Service không hỗ trợ truck | Lỗi nghiệp vụ rõ ràng |
| CAT-00B-05 | Integration | Không có cặp service/type | Không tạo booking |
| CAT-00B-06 | Integration | Price âm hoặc bằng 0 khi supported | Validation/constraint từ chối |
| CAT-00B-07 | Integration | Tạo cặp service/type trùng | Unique constraint từ chối |
| CAT-00B-08 | Integration | Đổi giá sau booking | Item snapshot cũ không đổi |

### 8.3. Capacity units

| Case ID | Loại | Test case | Expected |
|---|---|---|---|
| SLOT-00B-01 | Integration | Slot còn nhiều hơn requested units | Booking thành công |
| SLOT-00B-02 | Integration | Slot vừa đủ units | Booking thành công, remaining = 0 |
| SLOT-00B-03 | Integration | Slot thiếu 1 unit | Booking bị từ chối |
| SLOT-00B-04 | Integration | Bus cần 5, slot còn 3 | Bị từ chối |
| SLOT-00B-05 | Integration | Truck cần 4, slot còn 2 | Bị từ chối |
| SLOT-00B-06 | Concurrency | Hai request tranh phần capacity cuối | Tổng committed units không vượt slot |
| SLOT-00B-07 | Integration | Hủy booking | Capacity được giải phóng |
| SLOT-00B-08 | Integration | Insert item/booking thất bại | Rollback, không giữ capacity rác |
| SLOT-00B-09 | Security | Client gửi capacity thấp giả | Backend bỏ qua và tải config |

### 8.4. Loyalty và tier

| Case ID | Loại | Test case | Expected |
|---|---|---|---|
| LOY-00B-01 | Unit | Earn theo bốn tier rates | Floor hai bước đúng |
| LOY-00B-02 | Integration | Booking chưa completed | Không earn |
| LOY-00B-03 | Integration | Complete request gửi hai lần | Không lặp point/spend/visit/event/usage |
| LOY-00B-04 | Integration | Redeem FEFO một credit lot | Allocation trỏ lot có expiry sớm nhất |
| LOY-00B-05 | Integration | Redeem qua nhiều earn lots | Allocation sum bằng debit |
| LOY-00B-06 | Integration | Redeem thiếu điểm | Rollback toàn bộ |
| LOY-00B-07 | Integration | Redeem điểm đã expired/remaining=0 | Không sử dụng |
| LOY-00B-08 | Integration | Expiry command chạy hai lần | Không transaction/allocation trùng |
| LOY-00B-09 | Concurrency | Hai redeem đồng thời | point_balance và remaining credit lot không âm |
| LOY-00B-10 | Integration | Redeem thành công | Spend/visits không giảm |
| TIER-00B-01 | Unit | Chỉ đạt spend hoặc visits | Không đạt tier; dùng AND |
| TIER-00B-02 | Integration | Monthly review chạy hai lần | Run/history/reset không lặp |
| TIER-00B-03 | Integration | Upgrade nhiều bậc | Tier/history đúng |
| TIER-00B-04 | Integration | Downgrade nhiều bậc | Tier/history đúng |
| TIER-00B-05 | Integration | Giữ nguyên tier | History snapshot và reset đúng rule |
| TIER-00B-06 | Integration | Thay tier rule sau review | History cũ không đổi |

### 8.5. Cancellation

| Case ID | Loại | Test case | Expected |
|---|---|---|---|
| BKG-CAN-01 | Unit/Integration | Hủy trước hơn 2 giờ | Customer được hủy |
| BKG-CAN-02 | Unit/Integration | Hủy đúng 2 giờ | Customer được hủy |
| BKG-CAN-03 | Unit/Integration | Hủy dưới 2 giờ | Customer bị từ chối |
| BKG-CAN-04 | Feature | Admin hủy ngoại lệ dưới 2 giờ | Được phép, có reason/audit |
| BKG-CAN-05 | Integration | Hủy completed | Bị từ chối |
| BKG-CAN-06 | Integration | Hủy cancelled | Bị từ chối |
| BKG-CAN-07 | Integration | Hủy active booking | Capacity giải phóng |
| BKG-CAN-08 | Integration | Hủy booking chưa completed | Không tạo earn transaction |

### 8.6. Reward và promotion

| Case ID | Loại | Test case | Expected |
|---|---|---|---|
| RWD-00B-01 | Integration | Reward có allowed vehicle type | Chỉ loại liên kết được dùng |
| RWD-00B-02 | Integration | Reward rửa xe máy dùng cho bus | Bị từ chối |
| PRO-00B-01 | Integration | Promotion Silver+ | Silver/Gold/Platinum được, Member không |
| PRO-00B-02 | Unit | Promotion đã hết hạn | Không eligible |
| PRO-00B-03 | Unit | Promotion chưa bắt đầu | Không eligible |
| PRO-00B-04 | Unit | Promotion inactive | Không eligible |
| PRO-00B-05 | Integration | Promotion sai service/vehicle type | Bị từ chối backend |
| PRO-00B-06 | Security | Frontend giả discount/final_price | Backend tính lại |

### 8.7. LPR và research

| Case ID | Loại | Test case | Expected |
|---|---|---|---|
| LPR-00B-01 | Feature | Mock provider thành công | Trả text/confidence, user xác nhận |
| LPR-00B-02 | Feature | Provider lỗi/mất mạng | Manual fallback vẫn dùng được |
| LPR-00B-03 | Feature | Confidence thấp | Yêu cầu xác nhận/sửa, không auto-save |
| LPR-00B-04 | Feature | Manual fallback | Normalize/validate/duplicate như luồng chuẩn |
| LPR-00B-05 | Integration | Recognized plate trùng sau normalize | Domain error, không tạo xe |
| LPR-00B-06 | Documentation | Review wording | Regex không được gọi là LPR |
| RBL-00B-01 | Feature/Security | Export CSV | Ẩn danh; không password/phone/plate/raw IP |
| RBL-00B-02 | Integration | Sinh synthetic | Ít nhất 2.000 records |
| RBL-00B-03 | Integration | Synthetic vehicle distribution | Có đủ motorbike/car/truck/bus |
| RBL-00B-04 | Integration | Gửi lại idempotent completion | Research event_key không lặp |

### 8.8. Performance

| Case ID | Loại | Test case | Expected |
|---|---|---|---|
| PERF-00B-01 | Performance | Seed 10.000 booking | Dataset hợp lệ và reproducible |
| PERF-00B-02 | Performance | 20 concurrent virtual users | Chạy được workload đã định nghĩa |
| PERF-00B-03 | Performance | Login/service/slot/history | P95 <1 giây |
| PERF-00B-04 | Performance | Create booking | P95 <2 giây |
| PERF-00B-05 | Performance | Redeem reward | P95 <2 giây |
| PERF-00B-06 | Performance | Admin report | P95 <2 giây |
| PERF-00B-07 | Performance | Error rate | <1%, không tính external LPR latency |
| PERF-00B-08 | Documentation | Test report | Ghi CPU/RAM/DB/PHP, dataset, tool, duration và limitations |

## 9. Closure Patch test cases

### 9.1. Booking nhiều dịch vụ và nhiều slot

| Case ID | Loại | Test case | Expected |
|---|---|---|---|
| BKG-CL-01 | Unit | Booking có hai dịch vụ | `booking_duration_minutes` bằng tổng hai duration từ DB |
| BKG-CL-02 | Unit | Hai service có capacity override khác nhau | Dùng giá trị lớn nhất, không dùng tổng |
| BKG-CL-03 | Unit | Mọi override thấp hơn vehicle default | Capacity không thấp hơn default của loại xe |
| BKG-CL-04 | Integration | Booking duration chồng lấn nhiều slot | Kiểm tra/giữ cùng capacity trên mọi slot chồng lấn |
| BKG-CL-05 | Integration | Slot giữa hành trình đã đầy | Booking và toàn bộ reservations thất bại |
| BKG-CL-06 | Integration | Lỗi sau khi đã lock/tạo một phần hold | Rollback, không để capacity reservation rác |
| BKG-CL-07 | Security | Client gửi duration/capacity giả | Backend bỏ qua, tải service/type config |

### 9.2. Expiry boundary

| Case ID | Loại | Test case | Expected |
|---|---|---|---|
| LOY-EXP-01 | Unit | Earn `29/02/2024` | `expires_at = 28/02/2025` cùng local time |
| LOY-EXP-02 | Unit | Earn ngày cuối tháng | Cộng 12 tháng và clamp về ngày hợp lệ cuối tháng khi cần |
| LOY-EXP-03 | Integration | Chạy ngay trước `expires_at` | Lot chưa expire |
| LOY-EXP-04 | Integration | Chạy đúng `expires_at` | Lot remaining được expire một lần |
| LOY-EXP-05 | Integration | Chạy lại expiry command | Không tạo transaction/allocation trùng, balance không âm |

### 9.3. Biển số dân sự Việt Nam thông dụng

| Case ID | Loại | Test case | Expected |
|---|---|---|---|
| VEH-PLATE-01 | Unit | Nhập chữ thường | Normalized chuyển chữ hoa |
| VEH-PLATE-02 | Unit | Có gạch ngang, dấu chấm và khoảng trắng | Loại bỏ toàn bộ separator quy định |
| VEH-PLATE-03 | Integration | Hai display khác nhau cùng normalized | Bản ghi thứ hai bị từ chối là trùng |
| VEH-PLATE-04 | Unit/Integration | Fixture hợp lệ cho motorbike/car/truck/bus | Đều qua shared validator và lưu được với type tương ứng |
| VEH-PLATE-05 | Unit | Thiếu hai chữ số mã địa phương | Bị từ chối bằng lỗi validation rõ ràng |
| VEH-PLATE-06 | Unit | Có ký tự ngoài `[0-9A-Z]` sau normalize | Bị từ chối |
| VEH-PLATE-07 | Feature | Biển ngoài phạm vi baseline | Lỗi rõ ràng hoặc manual review nếu domain hỗ trợ; không gọi regex là LPR |

### 9.4. Negative point adjustment

| Case ID | Loại | Test case | Expected |
|---|---|---|---|
| LOY-ADJ-01 | Integration | Adjustment dương có reason | `adjust_credit` non-expiring lot; ledger/cache cùng tăng, audit đúng |
| LOY-ADJ-02 | Integration | Adjustment âm nhỏ hơn available points | `adjust_debit` FEFO allocation, remaining và cache cùng giảm |
| LOY-ADJ-03 | Integration | Adjustment âm bằng available points | Allocation đủ, remaining/cache bằng 0 |
| LOY-ADJ-04 | Integration | Adjustment âm vượt available points | Từ chối toàn bộ, không clamp/ledger rác |
| LOY-ADJ-05 | Validation/Security | Adjustment không có reason | Từ chối trước mutation |
| LOY-ADJ-06 | Concurrency | Hai adjustment âm đồng thời | Lock/transaction không cho overspend hoặc balance âm |
| LOY-ADJ-07 | Integration | Reconcile sau adjustment | Cache = ledger net = remaining credits; debit allocation đủ; source lưu được |
| LOY-CREDIT-01 | Integration | Booking tính ra 0 điểm | Vẫn đánh dấu loyalty đã xử lý, không tạo credit transaction 0 điểm |

### 9.4.1. Generic credit lot migration được phê duyệt tại Slice 10

| Case ID | Loại | Test case | Expected |
|---|---|---|---|
| LOY-MIG-01 | Integration | Backfill positive `adjust` lịch sử | Thành `adjust_credit`, remaining bằng credit, expiry null |
| LOY-MIG-02 | Integration | Backfill negative `adjust` lịch sử | Thành `adjust_debit`, phân bổ FEFO vào credit lịch sử |
| LOY-MIG-03 | Integration | Allocation redeem cũ qua migration | Đổi tên generic nhưng giữ debit, credit, points và timestamp |
| LOY-MIG-04 | Integration | Negative adjustment không có credit trước nó | Migration fail-fast và báo chính xác transaction ID |
| LOY-MIG-05 | Integration | Legacy credit transaction có 0 điểm | Migration fail-fast và báo chính xác transaction ID |
| LOY-CON-01 | Integration | Reconcile sau earn/adjust/redeem/expire | Cache = ledger net = tổng remaining credit lots; debit allocation đủ |

### 9.5. Research deliverable boundary

| Case ID | Loại | Test case | Expected |
|---|---|---|---|
| RBL-CL-01 | Documentation | Review trạng thái Survey/ML/Paper | Ghi đúng “Deferred bonus work — Non-blocking” theo DEC-034; không chặn Slice 14/release |
| RBL-CL-02 | Documentation/Security | Review research output trước xác nhận | Có log/export ẩn danh/synthetic plan; không có survey/kết quả/accuracy/kết luận/paper bị bịa |

## 10. Entry/exit criteria

Entry cho một slice có code: dependency trước đó Done, schema/decision liên quan đã duyệt, fixture xác định. Exit: test mới + regression liên quan pass, RTM/status có evidence, không có lỗi blocker/high còn mở trong phạm vi.

Mini-Slice 00B chỉ hoàn tất thiết kế test. Không có tuyên bố application/performance test pass.
