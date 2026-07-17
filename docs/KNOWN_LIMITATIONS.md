# AUTO WASH PRO — HẠN CHẾ ĐÃ BIẾT

> Release Slice 15, 2026-07-17. Không có blocker/high severity mở trong phạm vi MUST đã khóa.

| Mức | Hạn chế | Ảnh hưởng / cách xử lý hiện tại |
|---|---|---|
| Medium | `MockLprProvider` không phải OCR/model production. | Manual input/confirm luôn hoạt động; external adapter cần endpoint, credential và evidence riêng. |
| Medium | Performance chỉ đo trên một máy local, 20 VU/10.000 booking. | Đạt target đồ án nhưng không phải SLA thương mại hoặc bằng chứng scale-out. |
| Medium | Chỉ một chi nhánh theo DEC-001. | Slot/report không có branch scope; multi-branch cần requirement, ERD và migration mới. |
| Medium | Không có payment, refund hoặc hậu kiểm reversal booking completed. | Nằm ngoài phạm vi DEC-007/030; correction dùng admin adjustment có audit. |
| Low | Rate limit login phân tán/CAPTCHA chưa triển khai. | Login dùng generic error, dummy hash và log failure; production internet-facing cần gateway/rate limiter. |
| Low | Log lưu file local, chưa có SIEM/rotation tập trung. | Request ID và event quan trọng đã có; vận hành thật cần retention/rotation/monitoring. |
| Low | Special monthly-review rerun không có UI/command riêng. | Completed run cố ý bị chặn; failed run resume an toàn. Nếu thêm rerun phải có authorization/reason/audit. |
| Low | UI responsive được kiểm tra foundation và viewport cơ bản, chưa có visual regression đa trình duyệt. | CSS có breakpoint 1023/639, table overflow, focus/reduced-motion; nên bổ sung Playwright khi CI hỗ trợ browser. |
| Low | Hai service group hiện là cấu hình hệ thống seed, chưa có full group CRUD hoặc promotion/reward group targeting. | Chủ đích giới hạn bugfix theo DEC-035; admin service chỉ chọn active group, benefit vẫn target service ID để không đổi semantics/lịch sử. |
| Info | Survey thật, ML, kiểm định chuyên sâu và paper chưa thực hiện. | Deferred bonus work theo DEC-034; không chặn release và không được suy diễn từ descriptive analytics. |
| Info | Không có email/SMS thật, native app, multi-branch, inventory/accounting hoặc microservice. | Ngoài phạm vi đồ án; không có button/placeholder giả trong luồng MUST. |

Không có secret production, external LPR credential hay dữ liệu survey thật trong repository. Demo password chỉ
dùng cho local/testing và phải thay nếu triển khai ra môi trường khác.
