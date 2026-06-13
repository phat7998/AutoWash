# AutoWash API Docs cho Frontend

Tài liệu này mô tả API production hiện tại để FE tích hợp trực tiếp.

## Base URLs

```text
Customer API: https://api-customer.wp-fl-demo.xyz
Admin API:    https://api-admin.wp-fl-demo.xyz
```

- api khách hàng: `https://api-customer.wp-fl-demo.xyz`
- api admin: `https://api-admin.wp-fl-demo.xyz`

## Quy Ước Chung

### Headers

Các request `POST`, `PUT`, `PATCH` bắt buộc gửi JSON:

```http
Content-Type: application/json
```

Các endpoint cần đăng nhập bắt buộc gửi token:

```http
Authorization: Bearer <access_token>
```

Nếu thiếu `Content-Type: application/json`, API trả:

```json
{
  "isSuccessful": false,
  "statusCode": 400,
  "message": "Strict security: Request must contain Content-Type: application/json",
  "data": {
    "message": "Strict security: Request must contain Content-Type: application/json"
  }
}
```

### CORS

API đã hỗ trợ browser preflight:

```text
OPTIONS -> 204 No Content
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With
```

### Response Format

Customer API luôn bọc response dạng:

```json
{
  "isSuccessful": true,
  "statusCode": 200,
  "message": null,
  "data": {}
}
```

Admin API hiện trả trực tiếp:

```json
{
  "data": {},
  "message": "..."
}
```

FE không nên hard-code chỉ một format. Nên đọc:

- `response.data.data` nếu có wrapper.
- `response.data` nếu endpoint trả trực tiếp.
- `message` để hiển thị lỗi/thành công.

### HTTP Status Thường Gặp

```text
200 OK: Thành công
201 Created: Tạo mới thành công
204 No Content: OPTIONS preflight
400 Bad Request: Payload sai, thiếu Content-Type, hoặc logic không hợp lệ
401 Unauthorized: Thiếu/sai Bearer token
403 Forbidden: Token đúng nhưng không đủ quyền
404 Not Found: Không tìm thấy resource/route
422 Unprocessable Entity: Validate form/model fail
500 Internal Server Error: Lỗi server
```

## Customer API

Base URL:

```text
https://api-customer.wp-fl-demo.xyz
```

### 1. Health / Version

#### GET `/version`

Không cần token.

Response:

```json
{
  "isSuccessful": true,
  "statusCode": 200,
  "message": null,
  "data": {
    "version": "0.1.0",
    "module": "root"
  }
}
```

## Customer Auth

### 2. Đăng Ký

#### POST `/auth/register`

Không cần token.

Body:

```json
{
  "username": "customer01",
  "password": "123456",
  "full_name": "Nguyen Van A",
  "phone": "0900000001",
  "license_plate": "59A1-12345",
  "device_token": "optional-push-token"
}
```

Field:

```text
username: string, required, unique
password: string, required, min 6 chars
full_name: string, required
phone: string, required
license_plate: string, optional
device_token: string, optional
```

Response thành công:

```json
{
  "isSuccessful": true,
  "statusCode": 200,
  "message": "Đăng ký thành công",
  "data": {
    "access_token": "token...",
    "user": {
      "id": 2,
      "username": "customer01",
      "full_name": "Nguyen Van A",
      "phone": "0900000001"
    }
  }
}
```

Lưu ý cho FE:

- Sau đăng ký có thể lưu `access_token`.
- Nếu gọi `/auth/login` sau đó, token sẽ được tạo lại. FE phải thay token cũ bằng token mới.

### 3. Đăng Nhập

#### POST `/auth/login`

Không cần token.

Body:

```json
{
  "username": "customer01",
  "password": "123456",
  "device_token": "optional-push-token"
}
```

Response thành công:

```json
{
  "isSuccessful": true,
  "statusCode": 200,
  "message": "Đăng nhập thành công",
  "data": {
    "access_token": "token...",
    "user": {
      "id": 2,
      "username": "customer01",
      "full_name": "Nguyen Van A",
      "phone": "0900000001"
    }
  }
}
```

FE lưu token:

```js
localStorage.setItem("customerToken", response.data.data.access_token);
```

### 4. Profile

#### GET `/auth/profile`

Cần token.

Headers:

```http
Authorization: Bearer <customer_access_token>
```

Response:

```json
{
  "isSuccessful": true,
  "statusCode": 200,
  "message": null,
  "data": {
    "full_name": "Nguyen Van A",
    "phone": "0900000001",
    "license_plate": "59A1-12345",
    "loyalty": {
      "point_balance": 0,
      "tier": "Member"
    }
  }
}
```

## Vehicle

### 5. Danh Sách Xe

#### GET `/vehicles`

Cần token.

Response:

```json
{
  "isSuccessful": true,
  "statusCode": 200,
  "message": null,
  "data": [
    {
      "id": 1,
      "license_plate": "59A1-12345",
      "vehicle_type": "MOTORBIKE",
      "brand_name": "Honda"
    }
  ]
}
```

### 6. Thêm Xe

#### POST `/vehicles`

Cần token.

Body:

```json
{
  "license_plate": "59A1-67890",
  "vehicle_type": "MOTORBIKE",
  "brand_name": "Yamaha"
}
```

Field:

```text
license_plate: string, required, max 20
vehicle_type: string, optional, default tùy DB/model
brand_name: string, optional, max 100
```

Response thành công:

```json
{
  "isSuccessful": true,
  "statusCode": 201,
  "message": "Thêm xe thành công",
  "data": {
    "id": 1,
    "customer_id": 1,
    "license_plate": "59A1-67890",
    "vehicle_type": "MOTORBIKE",
    "brand_name": "Yamaha",
    "status": "ACTIVE"
  }
}
```

### 7. Xóa Xe

#### DELETE `/vehicles/{id}`

Cần token.

Ví dụ:

```text
DELETE /vehicles/1
```

Response:

```json
{
  "isSuccessful": true,
  "statusCode": 200,
  "message": "Xóa xe thành công",
  "data": {
    "message": "Xóa xe thành công"
  }
}
```

Lưu ý:

- API không xóa cứng DB, chỉ đổi `status` sang `INACTIVE`.

## Booking

### 8. Đặt Lịch

#### POST `/bookings`

Cần token.

Body:

```json
{
  "vehicle_id": 1,
  "scheduled_at": 1781318880,
  "service_amount": 75000
}
```

Field:

```text
vehicle_id: integer, required
scheduled_at: integer Unix timestamp, required, phải là thời điểm tương lai
service_amount: number, optional, default 50000
```

Rule đặt lịch:

```text
Member: tối đa 7 ngày trước
Silver: tối đa 10 ngày trước
Gold: tối đa 12 ngày trước
Platinum: tối đa 14 ngày trước
```

Nếu quá giới hạn hoặc thời gian không hợp lệ:

```json
{
  "isSuccessful": false,
  "statusCode": 400,
  "message": "Bạn chỉ được đặt trước tối đa 7 ngày theo hạng thẻ hiện tại.",
  "data": {
    "message": "Bạn chỉ được đặt trước tối đa 7 ngày theo hạng thẻ hiện tại."
  }
}
```

Response thành công:

```json
{
  "isSuccessful": true,
  "statusCode": 201,
  "message": "Đặt lịch thành công",
  "data": {
    "id": 1,
    "customer_id": 1,
    "vehicle_id": 1,
    "booking_code": "AW6A2B736DB4671",
    "scheduled_at": 1781318880,
    "status": "PENDING",
    "service_amount": "75000.00",
    "reward_point_earned": 0,
    "reward_point_redeemed": 0
  }
}
```

### 9. Lịch Sử Booking

#### GET `/bookings`

Cần token.

Response:

```json
{
  "isSuccessful": true,
  "statusCode": 200,
  "message": null,
  "data": [
    {
      "id": 1,
      "booking_code": "AW6A2B736DB4671",
      "scheduled_at": 1781318880,
      "status": "PENDING",
      "service_amount": "75000.00"
    }
  ]
}
```

## Loyalty

### 10. Điểm Và Hạng

#### GET `/loyalty/balance`

Cần token.

Response:

```json
{
  "isSuccessful": true,
  "statusCode": 200,
  "message": null,
  "data": {
    "point_balance": 0,
    "lifetime_spend": "0.00",
    "wash_count": 0,
    "tier": "Member",
    "next_tier_progress": "Chưa triển khai logic tính toán next tier progress"
  }
}
```

### 11. Lịch Sử Điểm

#### GET `/loyalty/history`

Cần token.

Response:

```json
{
  "isSuccessful": true,
  "statusCode": 200,
  "message": null,
  "data": [
    {
      "id": 1,
      "transaction_type": "EARN",
      "points": 7500,
      "description": "Hoàn thành dịch vụ rửa xe: AW...",
      "created_at": 1781232480
    }
  ]
}
```

## Customer Promotion

### 12. Khuyến Mãi Đang Active

#### GET `/promotions/active`

Cần token.

Hiện endpoint là placeholder:

```json
{
  "isSuccessful": true,
  "statusCode": 200,
  "message": null,
  "data": {
    "items": []
  }
}
```

## Admin API

Base URL:

```text
https://api-admin.wp-fl-demo.xyz
```

Legacy URL vẫn dùng được:

```text
https://admin.wp-fl-demo.xyz/api
```

## Admin Auth

### 13. Đăng Nhập Admin

#### POST `/auth/login`

Không cần token.

Body:

```json
{
  "username": "admin",
  "password": "your-password"
}
```

Response:

```json
{
  "data": {
    "access_token": "token...",
    "user": {
      "id": 1,
      "username": "admin",
      "role": "ADMIN"
    }
  },
  "message": "Đăng nhập thành công"
}
```

FE lưu token:

```js
localStorage.setItem("adminToken", response.data.data.access_token);
```

Lưu ý:

- Chỉ user có `role = ADMIN` hoặc `role = MANAGER` được login Admin API.
- Token admin dùng chung header `Authorization: Bearer <admin_access_token>`.

## Tier Rules

### 14. Danh Sách Hạng

#### GET `/tier-rules`

Cần admin token.

Response:

```json
{
  "data": [
    {
      "id": 1,
      "code": "MEMBER",
      "name": "Member",
      "minimum_spend": "0.00",
      "minimum_visits": 0,
      "booking_window_days": 7,
      "priority_order": 0,
      "created_at": 1781210043,
      "updated_at": 1781210043
    }
  ]
}
```

### 15. Cập Nhật Hạng

#### PUT `/tier-rules/{id}`

Cần admin token.

Body có thể gửi một hoặc nhiều field:

```json
{
  "minimum_spend": 1000000,
  "minimum_visits": 10,
  "booking_window_days": 10,
  "priority_order": 1
}
```

Field:

```text
minimum_spend: number
minimum_visits: integer
booking_window_days: integer
priority_order: integer
name: string, optional
code: string, optional nhưng phải unique nếu đổi
```

Response:

```json
{
  "data": {
    "id": 2,
    "code": "SILVER",
    "name": "Silver",
    "minimum_spend": "1000000.00",
    "minimum_visits": 10,
    "booking_window_days": 10,
    "priority_order": 1
  },
  "message": "Cập nhật thành công"
}
```

## Admin Promotions

### 16. Danh Sách Khuyến Mãi

#### GET `/promotions`

Cần admin token.

Response:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Summer discount",
      "target_tier": "MEMBER",
      "promotion_type": "DISCOUNT",
      "value": "10000.00",
      "status": "DRAFT",
      "starts_at": 1781230000,
      "ends_at": 1781834800,
      "created_at": 1781230000,
      "updated_at": 1781230000
    }
  ]
}
```

### 17. Tạo Khuyến Mãi

#### POST `/promotions`

Cần admin token.

Body:

```json
{
  "name": "Summer discount",
  "target_tier": "MEMBER",
  "promotion_type": "DISCOUNT",
  "value": 10000,
  "status": "DRAFT",
  "starts_at": 1781230000,
  "ends_at": 1781834800
}
```

Field:

```text
name: string, required
target_tier: string, optional. Ví dụ MEMBER, SILVER, GOLD, PLATINUM
promotion_type: string, optional. Default DISCOUNT
value: number, optional
status: string, optional. Ví dụ DRAFT, ACTIVE, INACTIVE
starts_at: integer Unix timestamp, optional
ends_at: integer Unix timestamp, optional
```

Response:

```json
{
  "data": {
    "id": 1,
    "name": "Summer discount",
    "target_tier": "MEMBER",
    "promotion_type": "DISCOUNT",
    "value": "10000.00",
    "status": "DRAFT"
  },
  "message": "Tạo thành công"
}
```

## Admin Booking

### 18. Danh Sách Booking

#### GET `/bookings`

Cần admin token.

Response:

```json
{
  "data": [
    {
      "id": 1,
      "customer_id": 1,
      "vehicle_id": 1,
      "booking_code": "AW6A2B736DB4671",
      "scheduled_at": 1781318880,
      "status": "PENDING",
      "service_amount": "75000.00",
      "reward_point_earned": 0,
      "reward_point_redeemed": 0,
      "customer": {},
      "vehicle": {}
    }
  ]
}
```

### 19. Hoàn Thành Booking Và Tích Điểm

#### POST `/bookings/complete?id={id}`

Cần admin token.

Không cần body, nhưng vì method là `POST`, vẫn gửi JSON object rỗng:

```json
{}
```

Ví dụ:

```text
POST /bookings/complete?id=1
```

Logic:

- Đổi `booking.status` sang `COMPLETED`.
- Tính điểm bằng `service_amount * 0.1`.
- Ghi `reward_point_earned` vào booking.
- Cộng điểm vào `loyalty_account.point_balance`.
- Tăng `loyalty_account.wash_count`.
- Tăng `loyalty_account.lifetime_spend`.
- Tạo `point_transaction` loại `EARN`.
- Điểm hết hạn sau 12 tháng.

Response:

```json
{
  "data": {
    "id": 1,
    "status": "COMPLETED",
    "reward_point_earned": 7500
  },
  "message": "Hoàn thành lịch đặt và tích điểm thành công"
}
```

Nếu booking đã hoàn thành:

```json
{
  "message": "Lịch đặt đã được hoàn thành trước đó"
}
```

Status: `400`

## Ví Dụ Axios

### Customer Axios Instance

```js
import axios from "axios";

export const customerApi = axios.create({
  baseURL: "https://api-customer.wp-fl-demo.xyz",
  headers: {
    "Content-Type": "application/json",
  },
});

customerApi.interceptors.request.use((config) => {
  const token = localStorage.getItem("customerToken");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
```

### Admin Axios Instance

```js
import axios from "axios";

export const adminApi = axios.create({
  baseURL: "https://api-admin.wp-fl-demo.xyz",
  headers: {
    "Content-Type": "application/json",
  },
});

adminApi.interceptors.request.use((config) => {
  const token = localStorage.getItem("adminToken");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
```

### Customer Login Example

```js
const res = await customerApi.post("/auth/login", {
  username: "customer01",
  password: "123456",
  device_token: "optional-push-token",
});

localStorage.setItem("customerToken", res.data.data.access_token);
```

### Admin Login Example

```js
const res = await adminApi.post("/auth/login", {
  username: "admin",
  password: "your-password",
});

localStorage.setItem("adminToken", res.data.data.access_token);
```

### Create Booking Example

```js
const scheduledAt = Math.floor(Date.now() / 1000) + 86400;

const res = await customerApi.post("/bookings", {
  vehicle_id: 1,
  scheduled_at: scheduledAt,
  service_amount: 75000,
});
```

## Flow Gợi Ý Cho Web FE

### Web Khách Hàng

1. Register hoặc login.
2. Lưu `customerToken`.
3. Gọi `/auth/profile` để lấy thông tin user + loyalty.
4. Gọi `/vehicles` để lấy xe.
5. Nếu chưa có xe, gọi `POST /vehicles`.
6. Tạo lịch bằng `POST /bookings`.
7. Xem lịch bằng `GET /bookings`.
8. Xem điểm bằng `GET /loyalty/balance`.
9. Xem lịch sử điểm bằng `GET /loyalty/history`.

### Web Admin

1. Login admin.
2. Lưu `adminToken`.
3. Gọi `/tier-rules` để hiển thị cấu hình hạng.
4. Gọi `/promotions` để quản lý khuyến mãi.
5. Gọi `/bookings` để xem danh sách booking.
6. Khi hoàn thành dịch vụ, gọi `POST /bookings/complete?id={id}`.

## Các Lưu Ý Quan Trọng

- Token customer bị rotate mỗi lần gọi `/auth/login`; FE phải cập nhật token mới.
- `scheduled_at`, `starts_at`, `ends_at`, `created_at`, `updated_at` đều là Unix timestamp dạng giây.
- `service_amount`, `minimum_spend`, `value` có thể trả về dạng string decimal từ MySQL, FE nên parse khi cần tính toán.
- Customer API có wrapper `isSuccessful/statusCode/message/data`; Admin API hiện chưa wrapper đồng nhất.
- Không gửi form-data cho `POST`, `PUT`, `PATCH`; hãy gửi JSON.
- Với `POST /bookings/complete?id={id}`, body có thể là `{}` nhưng vẫn phải có `Content-Type: application/json`.
