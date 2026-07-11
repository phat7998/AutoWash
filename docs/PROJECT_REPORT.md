# AutoWash Pro — Báo Cáo Tóm Tắt Đồ Án

## 1. Nêu vấn đề

Các điểm rửa xe truyền thống thường quản lý khách hàng bằng sổ tay, tin nhắn hoặc các chương trình đơn giản như "rửa 5 lần tặng 1 lần". Cách làm này có ba hạn chế chính:

- Không theo dõi được lịch sử rửa xe, số lần ghé lại và tổng chi tiêu của từng khách.
- Không có chính sách ưu tiên rõ ràng cho khách hàng thân thiết.
- Không có dữ liệu đủ tốt để phân tích hành vi và thiết kế chương trình khuyến mãi.

AutoWash Pro giải quyết vấn đề bằng một hệ thống đặt lịch rửa xe có loyalty program nhiều hạng. Hệ thống vừa phục vụ nghiệp vụ đặt lịch, vừa thu thập dữ liệu hành vi để phân tích và dự đoán yếu tố ảnh hưởng đến việc khách hàng lên hạng.

## 2. Lý thuyết liên quan

### Loyalty Program

Loyalty program là chương trình giữ chân khách hàng bằng điểm thưởng, ưu đãi và quyền lợi theo hạng. Trong đề tài này, khách hàng được chia thành bốn tier:

| Tier | Điều kiện mẫu | Quyền đặt trước |
|---|---:|---:|
| Member | 0đ, 0 lượt | 7 ngày |
| Silver | 500.000đ, 5 lượt | 10 ngày |
| Gold | 1.500.000đ, 15 lượt | 12 ngày |
| Platinum | 3.000.000đ, 30 lượt | 14 ngày |

### Priority Queue

Priority queue là cơ chế sắp xếp hàng đợi theo độ ưu tiên. Trong AutoWash, booking được ưu tiên theo tier:

```text
Platinum > Gold > Silver > Member
```

Nếu hai khách cùng tier, hệ thống ưu tiên người có thời gian đặt sớm hơn.

### Data Analytics & Machine Learning

Phần phân tích dữ liệu dùng các biến:

- `lifetime_spend`: tổng chi tiêu tích lũy
- `wash_count`: số lần rửa xe
- `avg_service_amount`: chi tiêu trung bình/lần
- `cancel_rate`: tỉ lệ hủy
- `promo_usage_rate`: tỉ lệ dùng khuyến mãi

Các mô hình đã dùng:

- Logistic Regression
- Random Forest
- Feature Importance

Mục tiêu là trả lời Research Question:

> What factors most influence customer loyalty tier progression?

## 3. Nội dung hệ thống

### Customer Flow

1. Khách hàng đăng ký bằng số điện thoại, mật khẩu và biển số xe.
2. Hệ thống tạo user, customer, vehicle và loyalty account mặc định hạng Member.
3. Khách hàng đăng nhập và xem dashboard loyalty.
4. Khách hàng đặt lịch trong giới hạn booking window của tier hiện tại.
5. Khi booking hoàn thành, hệ thống cộng điểm, tăng wash count, cộng lifetime spend.
6. Hệ thống review tier để nâng/hạ hạng.
7. Khách hàng có thể redeem điểm để nhận ưu đãi.

### Admin Flow

1. Admin đăng nhập.
2. Admin xem danh sách booking.
3. Admin hoàn thành booking để kích hoạt tích điểm.
4. Admin xem priority queue theo ngày.
5. Admin xem tier rules, promotion và analytics.

### Data/ML Flow

1. Seed hoặc generate synthetic data.
2. Export dữ liệu ra CSV.
3. Chạy exploratory data analysis.
4. Chạy hypothesis testing.
5. Train Logistic Regression và Random Forest.
6. Xuất biểu đồ, bảng kết quả và feature importance.

## 4. Chức năng đã hoàn thành

| Nhóm | Chức năng | Trạng thái |
|---|---|---|
| Customer | Register/Login/Profile | Hoàn thành |
| Customer | Vehicle tự tạo khi đăng ký | Hoàn thành |
| Customer | Booking theo booking window | Hoàn thành |
| Customer | Loyalty balance và next-tier progress | Hoàn thành |
| Customer | Redeem điểm | Hoàn thành |
| Admin | Danh sách booking | Hoàn thành |
| Admin | Complete booking để cộng điểm | Hoàn thành |
| Admin | Priority queue | Hoàn thành |
| Admin | Analytics API | Hoàn thành |
| Data | Generate fake data | Hoàn thành |
| Data | Export CSV | Hoàn thành |
| ML | EDA, hypothesis testing, LR, RF | Hoàn thành |

## 5. Kết quả phân tích

Theo dữ liệu hiện tại:

| Tier | Số khách | Chi tiêu TB | Số lần rửa TB |
|---|---:|---:|---:|
| Member | 168 | 193.214đ | 4.1 |
| Silver | 88 | 875.682đ | 15.8 |
| Gold | 26 | 2.115.769đ | 31.5 |
| Platinum | 5 | 3.130.000đ | 43.8 |

Hypothesis testing:

| Giả thuyết | Kết quả |
|---|---|
| Số lần rửa ảnh hưởng đến tier | Bác bỏ H0, có ảnh hưởng |
| Chi tiêu tích lũy tương quan với tier | Bác bỏ H0, tương quan mạnh |

Machine learning:

| Model | Accuracy | Precision | Recall | F1 | AUC |
|---|---:|---:|---:|---:|---:|
| Logistic Regression | 0.983 | 1.000 | 0.958 | 0.979 | 1.000 |
| Random Forest | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 |

Top 5 yếu tố ảnh hưởng đến tier progression:

| Rank | Yếu tố | Importance |
|---|---|---:|
| 1 | Tổng chi tiêu tích lũy | 0.6070 |
| 2 | Số lần rửa xe | 0.2936 |
| 3 | Tỉ lệ hủy đặt lịch | 0.0529 |
| 4 | Chi tiêu trung bình/lần | 0.0465 |
| 5 | Tỉ lệ sử dụng khuyến mãi | 0.0000 |

Kết luận: yếu tố ảnh hưởng mạnh nhất là **tổng chi tiêu tích lũy**, sau đó là **số lần rửa xe**.

## 6. Kết quả đạt được

- Xây dựng được prototype hệ thống đặt lịch rửa xe.
- Có loyalty engine nhiều hạng.
- Có chính sách booking window theo tier.
- Có priority queue cho admin.
- Có redeem điểm.
- Có synthetic behavioral dataset.
- Có phân tích thống kê, biểu đồ và mô hình ML.
- Trả lời được Research Question bằng kết quả định lượng.

## 7. Chưa đạt được

- Chưa tích hợp online payment/refund vì đề tài cho phép bỏ qua.
- AI personalization chưa chạy real-time trong hệ thống, mới dừng ở phân tích và ML offline.
- Dataset chủ yếu là synthetic data, chưa có khảo sát thực tế quy mô lớn.
- Admin frontend mới là bản demo nhẹ, chưa phải dashboard production.

## 8. Hướng phát triển

- Thu thập thêm dữ liệu thật từ khảo sát và điểm rửa xe.
- Tích hợp recommendation/promotion cá nhân hóa theo hành vi khách.
- Thêm notification nhắc lịch, nhắc điểm sắp hết hạn.
- Thêm dashboard biểu đồ trực tiếp trên admin.
- Thêm phân quyền chi tiết cho owner, manager, staff.
- Triển khai production bằng Docker hoặc hosting PHP/MySQL.
