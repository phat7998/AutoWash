# AUTO WASH PRO — DESIGN SYSTEM

> Phiên bản 1.1 — UI/UX Productization Pass, 2026-07-17
> Trạng thái: nguồn thiết kế bắt buộc trước mọi UI. Đây là tài liệu, không phải CSS/HTML sản phẩm.

## 1. Mục tiêu và nguyên tắc

AutoWash Pro dùng phong cách hiện đại, sạch, chuyên nghiệp, phù hợp thao tác nhanh tại quầy và trên điện thoại. Ưu tiên khả năng đọc, thứ bậc thông tin, trạng thái nghiệp vụ và thao tác ít bước.

- Không lạm dụng gradient, animation, glassmorphism hoặc shadow.
- Không sao chép nguyên phong cách template admin mặc định.
- Không hy sinh contrast để tạo cảm giác “nhẹ”.
- Mọi giá trị nghiệp vụ quan trọng phải có label, đơn vị và trạng thái rõ.
- Customer và Admin khác layout/mật độ nhưng dùng chung token và component foundation.
- Tiếng Việt có dấu là ngôn ngữ UI chính.

### Customer interface

- Thân thiện, khoảng trắng thoáng, CTA đặt lịch nổi bật.
- Ưu tiên booking tiếp theo, loyalty, xe và reward.
- Navigation ít mục, dễ dùng một tay trên mobile.
- Không hiển thị thuật ngữ kỹ thuật hoặc dashboard quá dày.

### Admin interface

- Mật độ thông tin cao hơn nhưng vẫn đọc được.
- Ưu tiên filter, table, KPI, trạng thái slot/booking và bulk scanning.
- Destructive/config actions phải phân biệt rõ và có confirmation.
- Desktop-first cho vận hành, nhưng chức năng cốt lõi vẫn dùng được trên tablet/mobile.

## 2. Design tokens

Tên token là hợp đồng giữa các slice. Không dùng màu HEX trực tiếp trong component khi đã có token.

### 2.1. Color tokens

| Token | HEX | Vai trò/chú ý |
|---|---|---|
| `--color-primary-700` | `#0B3A63` | Primary pressed/dark surface |
| `--color-primary-600` | `#0F4C81` | Primary action, link đậm; dùng chữ trắng |
| `--color-primary-500` | `#1769AA` | Hover/accent graphic |
| `--color-primary-100` | `#DCECF8` | Selected/soft background |
| `--color-secondary-700` | `#075B6B` | Secondary pressed |
| `--color-secondary-600` | `#0B7285` | Secondary action; dùng chữ trắng |
| `--color-secondary-100` | `#DDF3F5` | Secondary soft background |
| `--color-accent-600` | `#C77800` | Accent đậm; warning icon |
| `--color-accent-500` | `#F59F00` | Highlight/loyalty accent; dùng chữ `#17212B` |
| `--color-accent-100` | `#FFF1CC` | Loyalty/promotion soft background |
| `--color-neutral-950` | `#111827` | Text rất đậm |
| `--color-neutral-900` | `#17212B` | Text primary |
| `--color-neutral-700` | `#364152` | Heading phụ |
| `--color-neutral-600` | `#52606D` | Text secondary |
| `--color-neutral-500` | `#697586` | Metadata trên nền sáng |
| `--color-neutral-400` | `#9AA4B2` | Placeholder/disabled icon |
| `--color-neutral-300` | `#CBD5E1` | Border |
| `--color-neutral-200` | `#E2E8F0` | Divider |
| `--color-neutral-100` | `#F1F5F9` | Muted surface |
| `--color-neutral-50` | `#F8FAFC` | Page section |
| `--color-background` | `#F5F7FA` | App background |
| `--color-surface` | `#FFFFFF` | Card/form/table surface |
| `--color-text-primary` | `#17212B` | Default text |
| `--color-text-secondary` | `#52606D` | Supporting text |
| `--color-border` | `#CBD5E1` | Default border |
| `--color-success-700` | `#12613F` | Success text |
| `--color-success-600` | `#18794E` | Success icon/badge |
| `--color-success-100` | `#DDF5E9` | Success background |
| `--color-warning-700` | `#7A4300` | Warning text |
| `--color-warning-600` | `#A15C00` | Warning icon |
| `--color-warning-100` | `#FFF0D5` | Warning background |
| `--color-danger-700` | `#912018` | Danger pressed/text |
| `--color-danger-600` | `#B42318` | Danger action |
| `--color-danger-100` | `#FEE4E2` | Danger background |
| `--color-info-700` | `#1849A9` | Info text |
| `--color-info-600` | `#175CD3` | Info icon/link |
| `--color-info-100` | `#DCEBFE` | Info background |
| `--color-focus-ring` | `#2563EB` | 3px focus ring, offset 2px |
| `--color-disabled-bg` | `#E5E7EB` | Disabled control background |
| `--color-disabled-text` | `#697586` | Disabled text trên nền disabled |
| `--color-overlay` | `#111827B8` | Modal overlay |

Text trắng chỉ dùng trên primary/secondary/danger đủ đậm. Accent 500 dùng text tối. Status luôn kèm icon/text, không chỉ dựa vào màu.

### 2.2. Typography

Font stack:

```text
"Inter", "Noto Sans", "Segoe UI", Roboto, Arial, sans-serif
```

Nếu không tải webfont, dùng fallback hệ thống; không chặn render. Inter/Noto Sans hỗ trợ tiếng Việt tốt.

| Style token | Size/line-height | Weight | Dùng cho |
|---|---|---:|---|
| `--text-display` | 32px/40px | 700 | Hero/page title hiếm dùng |
| `--text-h1` | 28px/36px | 700 | Tiêu đề trang |
| `--text-h2` | 22px/30px | 700 | Section chính |
| `--text-h3` | 18px/26px | 600 | Card/section nhỏ |
| `--text-body-lg` | 16px/24px | 400 | Nội dung nổi bật/form |
| `--text-body` | 14px/22px | 400 | Nội dung mặc định |
| `--text-small` | 12px/18px | 400 | Metadata/help |
| `--text-label` | 14px/20px | 600 | Form label |
| `--text-button` | 14px/20px | 600 | Button |
| `--text-table` | 13px/20px | 400 | Table body |
| `--text-table-head` | 12px/18px | 700 | Table header, không all-caps dài |

- Paragraph tối đa khoảng 70 ký tự mỗi dòng ở trang nội dung.
- Không dùng font weight dưới 400 cho text nghiệp vụ.
- Giá tiền dùng tabular numbers nếu font hỗ trợ.

### 2.3. Spacing

| Token | Value |
|---|---:|
| `--space-1` | 4px |
| `--space-2` | 8px |
| `--space-3` | 12px |
| `--space-4` | 16px |
| `--space-6` | 24px |
| `--space-8` | 32px |
| `--space-12` | 48px |
| `--space-16` | 64px |

Default control gap 8px; form field gap 16px; card padding 16/24px; section gap 24/32px; page vertical padding 24/32px.

### 2.4. Border, radius, shadow

| Token | Value | Dùng cho |
|---|---|---|
| `--border-width` | 1px | Control/card/table |
| `--border-strong` | 2px | Selected/error emphasis |
| `--radius-sm` | 6px | Badge/small control |
| `--radius-md` | 10px | Input/button/card |
| `--radius-lg` | 16px | Modal/feature card |
| `--radius-pill` | 999px | Status badge/avatar only |
| `--shadow-card` | `0 1px 2px #1018280F, 0 4px 12px #1018280A` | Elevated card, sparingly |
| `--shadow-modal` | `0 20px 40px #1018282E` | Modal/dialog |

- Default cards dùng border, không bắt buộc shadow.
- Không shadow cho table row, form field, badge hoặc mọi nested card.
- Không dùng nhiều hơn hai cấp elevation trên một màn hình.

### 2.5. Motion

- Duration: 120ms cho hover, 180ms cho expand/modal, tối đa 240ms.
- Easing: `ease-out` khi xuất hiện, `ease-in` khi biến mất.
- Không animation trang trí liên tục.
- Với `prefers-reduced-motion: reduce`, bỏ chuyển động không thiết yếu.

## 3. Component foundation

### 3.1. Button

Chiều cao mặc định 40px, compact 32px, large 48px; vùng bấm tối thiểu 44×44px trên touch.

| Variant | Style | Dùng khi |
|---|---|---|
| Primary | Primary 600, white text | Một CTA chính/section |
| Secondary | Secondary 600, white text | CTA quan trọng thứ hai |
| Outline | White, primary border/text | Alternative action |
| Ghost | Transparent, neutral/primary text | Toolbar/low emphasis |
| Danger | Danger 600, white text | Destructive confirmed action |
| Icon | 40×40, aria-label/tooltip | Action có icon quen thuộc |

- Hover dùng shade đậm hơn; active thêm pressed state; focus dùng focus ring.
- Loading giữ nguyên width, spinner + text “Đang xử lý…”, ngăn double submit.
- Disabled dùng disabled tokens và không nhận pointer/keyboard activation.
- Không có hai primary buttons cạnh nhau nếu không có lý do nghiệp vụ.

### 3.2. Form controls

Input/select/textarea cao tối thiểu 40px, border neutral 300, radius md. Cấu trúc field:

1. Label.
2. Required marker “*” + mô tả “Bắt buộc” cho screen reader.
3. Control.
4. Help text nếu cần.
5. Error text gắn `aria-describedby`.

States: default, hover, focus, filled, disabled, readonly, error, success khi thật sự hữu ích.

- Input: không dùng placeholder thay label.
- Select: có option rỗng “Chọn …” khi chưa chọn.
- Textarea: min 3 rows, có character count khi giới hạn.
- Checkbox/radio: label click được, hit area ≥44px.
- Date picker: cho nhập bàn phím; hiển thị định dạng `dd/mm/yyyy`.
- Time slot selector: card/radio hiển thị giờ, remaining capacity và trạng thái; full/closed không selectable.
- File upload: MIME/size guidance, preview có alt, progress/error và nút chọn lại.
- Search box: label/aria-label, clear button, debounce chỉ khi cần.
- Validation: hiển thị sau blur/submit; giữ dữ liệu đã nhập khi lỗi.

### 3.3. Cards

| Card | Nội dung |
|---|---|
| Standard | Header tùy chọn, body, footer action |
| Summary | Label, value, supporting text |
| KPI | Metric, period, trend có text/icon; không chỉ màu |
| Booking | Code, time, vehicle, services, final price, status, action hợp lệ |
| Reward | Name, points, restriction, expiry/status, redeem CTA |
| Vehicle | Plate nổi bật, vehicle type badge, brand/model, active status, actions |

Không lồng card quá hai cấp. Booking/reward/vehicle card phải có toàn bộ context để tránh bấm nhầm.

### 3.4. Table

- Header surface neutral 50, text table-head, có scope/label.
- Row height tối thiểu 48px; hover neutral 50; selected primary 100 + indicator.
- Actions column ở cuối, tên “Thao tác”, không chỉ dùng ba dấu chấm nếu action chính cần thấy.
- Pagination hiển thị tổng record, page, previous/next; filter đổi thì về page 1.
- Empty table có title, mô tả, CTA phù hợp; no-results khác no-data.
- Mobile: ưu tiên chuyển mỗi row thành card có label–value. Chỉ dùng horizontal scroll cho bảng cần so sánh cột, kèm sticky first/action column nếu khả thi.

### 3.5. Badge

Badge luôn có text; icon là bổ trợ.

| Trạng thái | Token nền/text |
|---|---|
| Booking pending | warning 100 / warning 700 |
| Booking confirmed | info 100 / info 700 |
| Booking in progress (nếu sau này có) | secondary 100 / secondary 700 |
| Booking completed | success 100 / success 700 |
| Booking cancelled | neutral 200 / neutral 700 |
| Late cancelled (analytics/admin exception nếu được thêm) | danger 100 / danger 700 |
| No-show | danger 100 / danger 700 |
| Member | neutral 200 / neutral 700 |
| Silver | `#E8EDF3` / `#344054` |
| Gold | accent 100 / `#7A4300` |
| Platinum | primary 100 / primary 700 |
| Promotion active | success 100 / success 700 |
| Promotion inactive/expired | neutral 200 / neutral 700 |
| Motorbike | info 100 / info 700 |
| Car | primary 100 / primary 700 |
| Truck | warning 100 / warning 700 |
| Bus | secondary 100 / secondary 700 |

`in progress` và `late cancelled` chỉ là token dự phòng; không tự thêm domain status nếu chưa có decision.

### 3.6. Modal và dialog

- Width 480px confirmation, 640px form/detail; mobile full-width với margin 16px.
- Có title, mô tả hậu quả, close button, primary/secondary actions.
- Confirmation: hành động thường.
- Destructive: danger action, nêu đối tượng cụ thể; không dùng “Bạn có chắc?” đơn độc.
- Reward redemption: reward, points cost, balance trước/sau, expiry.
- Booking cancellation: booking/time, cutoff, capacity/reward consequence, reason nếu admin.
- Error dialog chỉ dùng khi lỗi chặn toàn flow; lỗi field ở inline.
- Trap focus, focus vào heading/first control, Escape đóng khi an toàn, trả focus về trigger.

### 3.7. Notification

| Loại | Cách dùng |
|---|---|
| Success | Kết quả mutation thành công, có action tiếp theo nếu cần |
| Error | Lỗi cần sửa/không hoàn tất; nêu cách khắc phục |
| Warning | Nguy cơ/cutoff/low confidence |
| Info | Trạng thái trung tính/giải thích rule |
| Flash | Sau redirect; persistent đến khi đọc/đóng hoặc sang navigation kế |
| Toast | Chỉ feedback ngắn không quan trọng; không dùng cho validation/destructive failure |

Notification có icon + title/text, `role=status` hoặc `alert` phù hợp, không auto-dismiss lỗi nghiêm trọng.

## 4. Layout

### 4.1. Customer layout

- Header: logo/brand, nav chính, account/notification.
- Desktop navigation: Trang chủ, Đặt lịch, Lịch sử, Phương tiện, Ưu đãi/Điểm.
- Main content max-width 1200px, horizontal padding 16/24/32px.
- Mobile bottom navigation tối đa 5 mục; account nằm trong menu/header.
- Booking flow: stepper ngắn Chọn xe → Dịch vụ → Khung giờ → Xác nhận; mỗi bước giữ dữ liệu và lỗi.
- Loyalty summary gần đầu dashboard: tier, points, expiry sắp tới, progress rule giải thích được.
- Vehicle management dùng card mobile và table/card desktop.

### 4.2. Admin layout

- Desktop sidebar 240px; collapsed 72px; icon luôn kèm tooltip/accessible name.
- Header: page context, account, environment indicator nếu cần.
- Breadcrumb xuất hiện từ cấp 2, không lặp h1 vô ích.
- Main content max-width 1600px.
- Page pattern: title/actions → KPI cards → filter bar → table/chart/detail.
- Filter bar sticky chỉ khi danh sách dài; filter active có chip/text và “Xóa bộ lọc”.
- Dashboard card grid ưu tiên 4/3/2/1 cột theo breakpoint.
- Mobile sidebar thành drawer; đóng sau navigation; action quan trọng không bị giấu hoàn toàn.

## 5. Responsive breakpoints

| Name | Range | Quy tắc chính |
|---|---|---|
| Mobile | 0–639px | 1 cột, padding 16px, bottom nav/customer, admin drawer |
| Tablet | 640–1023px | 2 cột card, compact sidebar/drawer, form 1–2 cột |
| Desktop | 1024–1279px | Sidebar đầy đủ, 3 cột card, table chuẩn |
| Large desktop | ≥1280px | 4 cột KPI, content max width; không kéo text quá dài |

- Booking form mobile là một cột và CTA sticky bottom nếu không che nội dung.
- Table mobile đổi sang card label–value hoặc scroll có dấu hiệu rõ.
- Admin sidebar collapse dưới 1024px.
- Action group wrap theo hàng; primary action không tràn viewport.
- Modal mobile không vượt viewport, body scroll độc lập.

## 6. Accessibility

- Tất cả chức năng dùng được bằng keyboard theo thứ tự DOM hợp lý.
- Focus visible 3px, không xóa outline mà không thay thế.
- Mọi control có label programmatic; icon button có accessible name.
- Không chỉ dùng màu: thêm text/icon/pattern.
- Contrast mục tiêu WCAG 2.1 AA: 4.5:1 text thường, 3:1 text lớn/UI boundary.
- Error gắn control bằng `aria-invalid` và `aria-describedby`; focus summary sau submit nếu nhiều lỗi.
- Target touch tối thiểu 44×44px.
- Ảnh nội dung có alt; ảnh trang trí alt rỗng.
- Modal trap/restore focus và công bố role/name.
- Dynamic status dùng live region phù hợp, tránh đọc lặp.
- Hỗ trợ zoom 200% và reduced motion.
- Heading không nhảy cấp; table dùng caption/header semantics.

## 7. UI states bắt buộc

| State | Pattern |
|---|---|
| Loading | Skeleton cho list/card, spinner cho action; không hiển thị số liệu giả |
| Empty | Title + nguyên nhân + CTA; phân biệt chưa có dữ liệu |
| Error | Thông báo người dùng hiểu, retry/cách sửa; correlation ID khi hỗ trợ |
| Permission denied | 403 rõ ràng, link về vùng hợp lệ; không lộ resource |
| Offline/provider unavailable | Giữ input; LPR chuyển manual fallback |
| No search results | Hiển thị query/filter và nút clear |
| Slot full | Disabled + badge “Đã đầy”; gợi ý slot khác |
| Service unavailable | Nêu service không hỗ trợ loại xe; không chỉ ẩn |
| Promotion expired/not started | Badge + thời gian; không áp discount |
| LPR low confidence | Warning + text/confidence + input cho xác nhận/sửa |
| Validation | Inline error + summary nếu nhiều field |
| Saving | Disable submit, loading label, chống double submit |
| Success | Flash/confirmation có next step |

## 8. Iconography và nội dung

- Dùng một icon set thống nhất nếu dependency được duyệt; stroke/fill style không trộn.
- Không dùng emoji thay icon nghiệp vụ.
- Icon không thay text ở action khó đoán.
- Dùng câu ngắn, chủ động: “Hủy lịch đặt”, “Không đủ điểm”, “Khung giờ vừa hết chỗ”.
- Format tiền: `250.000 ₫`; ngày `15/07/2026`; giờ `14:30`.
- Không dùng Lorem Ipsum, placeholder giả hoặc số liệu không gắn nguồn trong bản hoàn thiện.

## 9. Mandatory Rules for AI Agents

1. Đọc `docs/DESIGN_SYSTEM.md` trước khi tạo hoặc sửa giao diện.
2. Không tạo màu mới ngoài token nếu chưa cập nhật Design System.
3. Không tạo component trùng chức năng nhưng khác phong cách.
4. Không dùng inline style trừ trường hợp đặc biệt có giải thích.
5. Không hard-code spacing tùy ý.
6. Không thay đổi typography trong một feature slice.
7. Không dùng emoji thay thế icon nghiệp vụ.
8. Không dùng màu làm tín hiệu duy nhất.
9. Không tạo trang theo phong cách khác chỉ vì sang session mới.
10. Tái sử dụng layout và component hiện có.
11. Mọi thay đổi Design System phải được thực hiện trong một commit hoặc quyết định rõ ràng.
12. Không tự ý viết lại toàn bộ CSS khi chỉ sửa một chức năng.
13. Giao diện phải hỗ trợ tiếng Việt có dấu.
14. Không để text placeholder kiểu Lorem Ipsum trong bản hoàn thiện.
15. Không để button hoặc menu không hoạt động trong luồng chính.
16. Phải có loading, empty, error và validation state cho tính năng phù hợp.
17. Customer và Admin có thể khác layout nhưng phải dùng chung token nền tảng.

## 10. Checklist UI cho mỗi slice

- [ ] Đúng Customer/Admin layout.
- [ ] Chỉ dùng token và spacing scale.
- [ ] Component/state đã có được tái sử dụng.
- [ ] Responsive mobile/tablet/desktop.
- [ ] Keyboard/focus/label/contrast/error association.
- [ ] Loading/empty/error/success/permission state phù hợp.
- [ ] Tiếng Việt có dấu; không placeholder/dead button.
- [ ] Không đổi Design System âm thầm.

Design System chỉ được sửa khi requirement hoặc quyết định thiết kế thay đổi; feature slice không được tự tạo “mini design system” riêng.

## 11. Product shell và landing page

UI/UX Productization Pass chuẩn hóa ba lớp điều hướng mà không tạo thêm token màu:

- Guest dùng header gọn với Trang chủ, Dịch vụ, Đăng nhập và CTA Đặt lịch ngay; landing page có hero, dịch vụ,
  bốn loại phương tiện, quy trình, quyền lợi thành viên, lý do lựa chọn, FAQ và CTA cuối trang.
- Customer dùng header sản phẩm riêng với Tổng quan, Đặt lịch, Lịch sử, Điểm thưởng, Quà tặng, Phương tiện,
  Khung giờ và Đăng xuất. Không render route quản trị trong navigation.
- Admin dùng sidebar 250px ở desktop và drawer có overlay ở viewport dưới 1024px. Nhóm điều hướng gồm Vận
  hành, Khách hàng thân thiết và Phân tích; bảng admin trên mobile nằm trong container cuộn ngang có chủ đích.

Landing page dùng illustration SVG nội tuyến, không tải asset ngoài và không hiển thị số liệu, đánh giá hoặc
testimonial giả. Confirmation cho thao tác hủy/ngừng/hoàn thành dùng dialog trình duyệt có keyboard support;
focus visible, skip link, heading hierarchy và `prefers-reduced-motion` tiếp tục là yêu cầu bắt buộc.
