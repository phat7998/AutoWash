# AUTO WASH PRO — GIẢ ĐỊNH VÀ CÂU HỎI CÒN MỞ

> Cập nhật: 2026-07-17 — checkpoint Q-020 trước Slice 14
> Nội dung đã được 00B khóa không còn là câu hỏi. Chỉ các điểm thật sự chưa được quyết định mới nằm dưới đây.

## Giả định vận hành còn hiệu lực

| ID | Giả định | Căn cứ | Rủi ro |
|---|---|---|---|
| ASM-001 | Một chi nhánh trong phạm vi nộp đồ án. | DEC-001 | Nếu đổi sang multi-branch phải thiết kế lại slot/booking/report. |
| ASM-002 | Active/inactive là cơ chế bảo toàn lịch sử mặc định; chưa dùng soft-delete toàn cục. | DEC-029 | Nếu nhóm chọn soft-delete chung phải chuẩn hóa query và index. |
| ASM-003 | Cặp service–vehicle type unsupported có thể tồn tại với price/duration null để giữ cấu hình; khi supported thì price/duration >0. | DEC-016 | Cần CHECK/business validation nhất quán ở Slice 02/06. |
| ASM-004 | Research event dùng snapshot code/metric, không FK trực tiếp user, để tránh PII và giữ lịch sử khi config đổi. | DEC-014/025 | Data dictionary phải giải thích snapshot. |
| ASM-005 | Không có post-completion refund/reversal trong baseline; admin correction dùng adjust có audit. | DEC-007/030 | Nếu giảng viên yêu cầu hoàn tác completed booking phải thêm reversal rule. |
| ASM-006 | `display_plate` có thể lưu theo nhập/xác nhận của người dùng; `normalized_plate` luôn là khóa nghiệp vụ để so trùng. | DEC-031 | Khi mở rộng biển đặc biệt phải cập nhật validator tập trung và fixture, không rải regex. |

## Quyết định cũ đã được 00B giải quyết

| Câu hỏi Slice 00 | Kết quả khóa |
|---|---|
| Pure PHP/framework | Pure Modern PHP do nhóm chọn; không full-stack framework (DEC-012). |
| Chỉ xe máy hay cả ô tô | Bốn loại motorbike/car/truck/bus qua `vehicle_types` (DEC-015). |
| Spend AND visits | AND (DEC-003). |
| Tier thresholds/point rate | Đã có seed cụ thể và admin-configurable (DEC-019). |
| Priority queue | Booking window, không runtime queue (DEC-002). |
| LPR thật/mock | Adapter + mock + manual fallback; external provider tương lai (DEC-008/024). |
| Synthetic target | Tối thiểu 2.000 records (DEC-025). |
| Cancellation | Customer cutoff 2 giờ; admin ngoại lệ (DEC-021). |
| 12 tháng hay 365 ngày | 12 calendar months (DEC-005). |
| Performance threshold | Đã khóa tại DEC-026/NFR-02. |
| Capacity booking nhiều dịch vụ | Tổng duration, capacity lớn nhất và giữ trên mọi slot chồng lấn (DEC-017). |
| Expiry 29/02/cuối tháng | Calendar-month clamp; boundary `current_time >= expires_at` (DEC-005). |
| Phạm vi biển số | Biển dân sự Việt Nam thông dụng; validator tập trung (DEC-031). |
| Adjust âm vượt balance | Từ chối toàn bộ, không clamp (DEC-032). |

## Xác nhận học thuật bên ngoài đã khóa

Q-020 đã được nhóm xác nhận tại checkpoint trước Slice 14 theo DEC-034.

| ID | Kết quả | Ảnh hưởng | Trạng thái |
|---|---|---|---|
| Q-020 | Survey thật, ML model, kiểm định chuyên sâu và paper/conference-format report không bắt buộc cho sản phẩm chính; chỉ là điểm cộng nếu còn thời gian. | Không thay rule-based loyalty và không chặn Slice 14/release. Synthetic minimum vẫn là 2.000 theo DEC-025. | Resolved — Deferred bonus work |

Phạm vi phân tích trong Slice 14 chỉ là descriptive analytics từ dữ liệu hệ thống hoặc synthetic có nhãn nguồn.
Không tự tạo survey response, accuracy, p-value, hypothesis result, kết luận nghiên cứu, nguồn dataset ngoài
hoặc tuyên bố paper đã hoàn thành.

## Mâu thuẫn/giới hạn đã ghi nhận

- Đề tài `SU26SWP01` mô tả chủ yếu xe máy; quyết định nhóm 00B mở rộng bốn loại nhưng không đổi research question hay loyalty core.
- Đề tài dùng “priority queue”; tài liệu kỹ thuật định nghĩa chính xác là booking window.
- AI personalization trong đề tài là optional; không được dùng để mô tả hệ thống là AI-powered nếu chưa có implementation/evidence.
- Không cam kết 3.000 record bên ngoài; chỉ synthetic minimum 2.000 đã được khóa.
- NFR hiệu năng là mục tiêu môi trường đồ án, không phải SLA thương mại.
