# AUTO WASH PRO — 30 CÂU HỎI PHẢN BIỆN

### Q01. Vì sao không dùng Laravel?

DEC-012 khóa Modern PHP 8.2+ thuần để chứng minh Front Controller, Router, middleware và phân tầng. Composer
vẫn cung cấp PSR-4, Dotenv, PHPUnit/PHPCS; repository không dùng application framework.

### Q02. Controller có truy cập SQL không?

Không. Controller đọc Request, gọi Service và chọn Response/View. SQL nằm ở Repository/Database; lệnh
`composer release:audit` quét SQL keyword trong Controller/View.

### Q03. Làm sao chống SQL injection?

Query có input dùng PDO prepared statement thật; `Database` đặt `ATTR_EMULATE_PREPARES=false`, charset
`utf8mb4`. Tên bảng động chỉ xuất hiện với allowlist cố định trong repository nội bộ.

### Q04. Làm sao chống XSS?

`View` cấp helper escape dựa trên `htmlspecialchars`; view dùng `$e(...)` cho output động. Test XSS đưa HTML
vào dữ liệu rồi xác nhận response chỉ chứa dạng escaped.

### Q05. CSRF được bảo vệ ở đâu?

`CsrfMiddleware` là global middleware cho mọi method không an toàn. Token 256-bit được so bằng `hash_equals`
và tiêu thụ sau một lần dùng; thiếu/sai token trả 419.

### Q06. Session fixation được xử lý thế nào?

Login hợp lệ gọi `session_regenerate_id(true)`. Cookie dùng HttpOnly, SameSite=Lax, Secure khi HTTPS; logout
xóa dữ liệu, cookie và hủy session.

### Q07. Customer có thể giả role khi đăng ký không?

Không. `AuthController/AuthService` không nhận role từ request; `UserRepository::createCustomer` cố định role
customer. Route admin còn đi qua authenticated + role middleware ở backend.

### Q08. IDOR được ngăn thế nào?

Service/repository luôn nhận owner ID từ session cho vehicle, booking, reward redemption và ảnh LPR; không tin
owner ID do client gửi. Test customer A truy cập resource của B xác nhận bị chặn.

### Q09. Giá booking có thể bị sửa trên frontend không?

Không. Client chỉ gửi ID selection. Backend khóa/tải service price, tier, perk, promotion, reward và capacity từ
DB, kiểm tra selection policy theo `service_groups`, tính lại bằng `PriceCalculator`, rồi lưu snapshot. Gửi thẳng
Standard + Premium hoặc add-on-only vẫn bị từ chối trước mọi phép tính/ghi dữ liệu.

### Q10. Tiền có dùng float không?

Schema dùng DECIMAL; service dùng decimal string/integer và phép floor có chủ đích. Float chỉ dùng confidence
LPR, không dùng cho tiền.

### Q11. Vì sao duration cộng nhưng capacity lấy max?

DEC-017 định nghĩa booking nhiều dịch vụ: tổng duration, capacity lớn nhất giữa vehicle default và override.
Cùng units được giữ trên mọi slot chồng lấn để không double-count service nối tiếp. DEC-035 làm rõ capacity là
sức chứa vật lý, không phải độ phức tạp/thời gian; Premium không làm xe lớn hơn nên bốn service seed dùng
vehicle default, còn thời gian Premium nằm ở `duration_minutes`.

### Q12. Hai khách tranh slot cuối thì sao?

`BookingService/BookingRepository` mở transaction, lock các slot theo thứ tự, tính active reservations và chỉ
commit khi tất cả slot đủ. Integration test dùng hai process chứng minh tổng units không vượt capacity.

### Q13. Complete gửi hai lần có cộng điểm hai lần không?

Không. State transition, marker `loyalty_processed_at`, unique source transaction/event/usage và transaction
giữ idempotency. Request thứ hai không tạo point, metrics hay research event mới.

### Q14. Công thức earn point là gì?

DEC-020: `floor(floor(final_price / 10.000) × point_rate)`. Rate lấy từ tier trong DB; ví dụ Gold 250.000 ₫
cho 31 điểm.

### Q15. Vì sao có ledger và point_balance?

Ledger là nguồn lịch sử bất biến; `users.point_balance` chỉ là cache. Reconcile kiểm tra cache bằng ledger net và
tổng remaining credit lot sau mọi mutation.

### Q16. Redeem chọn lot nào?

FEFO: lot có expiry sớm nhất trước, hòa thì created_at/id; credit không expiry dùng sau theo FIFO. Debit phải có
allocation đủ, không cho balance hoặc remaining âm.

### Q17. Điểm hết hạn tính 12 tháng hay 365 ngày?

12 calendar months theo DEC-005, clamp về cuối tháng nếu ngày đích không tồn tại. Boundary hết hạn là
`current_time >= expires_at` trong Asia/Ho_Chi_Minh.

### Q18. Monthly review có thể chạy lặp không?

Run unique theo period, advisory lock chặn batch đồng thời và history unique user/period. Completed run bị từ
chối; failed/stale run resume và bỏ qua user đã commit.

### Q19. Điều kiện lên hạng dùng AND hay OR?

AND theo DEC-003: customer phải đạt đồng thời spend và completed visits, rồi chọn tier active rank cao nhất.
Threshold/rate nằm trong DB và có audit khi admin đổi.

### Q20. Promotion có bị vượt giới hạn khi concurrency không?

Không. Checkout lock config/current usage, tính cả booking đang giữ lượt và kiểm tra total/per-user limit.
Concurrency test cho lượt cuối chỉ một booking thành công.

### Q21. Reward có dùng chéo tài khoản hoặc nhiều lần không?

Không. Redemption owner-only, booking liên kết unique, status chuyển available→used atomically khi complete.
Cancel trả available nếu còn hạn, nếu hết hạn chuyển expired.

### Q22. Upload LPR an toàn thế nào?

Backend kiểm tra upload error, byte size thật, MIME bằng fileinfo và allowlist JPEG/PNG/WebP; tên random, quyền
hạn chế, lưu ngoài public. Ảnh chỉ đọc qua route có ownership.

### Q23. Mock LPR chứng minh điều gì?

Chỉ chứng minh provider contract, timeout/failure/low-confidence/manual-confirm flow. Nó không phải OCR/model
production; external adapter cần endpoint/model/credential riêng.

### Q24. Research CSV bảo vệ privacy thế nào?

Exporter dùng allowlist 22 cột, anonymous key và source marker; không xuất raw metadata/PII. Test scan name,
phone, email, password/hash, plate, raw IP và direct user ID.

### Q25. Synthetic data có bị gọi là dữ liệu thật không?

Không. `data_source=synthetic`, generator deterministic theo seed, tối thiểu 2.000 record và đủ bốn loại xe.
Data dictionary ghi rõ giới hạn suy diễn.

### Q26. Dashboard revenue tính booking nào?

Chỉ `completed` theo DEC-004. Cancelled/no-show/pending không đóng góp revenue, spend, visit, point, usage hay
completion event.

### Q27. Hệ thống có đạt performance target không?

Workload Slice 15 dùng 10.000 booking, 20 VU qua Apache/MySQL thật. Tất cả read P95 <1s, booking/redeem/report
P95 <2s và error 0%; chi tiết môi trường/số đo ở `docs/PERFORMANCE_REPORT.md`.

### Q28. Database có dựng lại được không?

Có 10 migration, advisory lock, migration history, demo seed idempotent và reset chỉ cho local/testing với
`--force`. Fresh reset–migrate–seed được chạy trong full DB test.

### Q29. Audit log ghi những gì?

Admin point adjustment, booking exception, tier/perk, promotion và service/price config ghi actor/action,
before/after/reason cùng transaction. Không ghi password, token hoặc secret.

### Q30. Hạn chế lớn nhất của bản release là gì?

Một chi nhánh; không payment/refund; LPR production chưa tích hợp; workload local không phải SLA; survey/ML/
paper là bonus deferred. Các giới hạn này được công khai trong `docs/KNOWN_LIMITATIONS.md`.
