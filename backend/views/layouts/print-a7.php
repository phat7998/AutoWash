<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu Gửi Hàng - LOGSHIP</title>
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        /* CSS Tùy chỉnh (Không có Bootstrap) */

        /* Reset cơ bản */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            font-size: 14px; /* Cỡ chữ cơ bản gần với ảnh gốc */
            line-height: 1.25; /* Khoảng cách dòng nhỏ */
            background-color: #f4f4f4;
            color: #000;
        }

        .container {
            width: 960px;
            max-width: 100%;
            margin: 2rem auto;
            background-color: #fff;
            border: 1px solid #000; /* Viền đen chính */
            overflow: hidden;
        }

        /* --- Header --- */
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
        }

        .form-header img {
            width: 150px;
            height: auto;
        }

        .form-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-align: center;
            flex-grow: 1; /* Cho phép h1 chiếm không gian */
        }

        .header-spacer {
            width: 150px; /* Cùng chiều rộng với logo để căn giữa h1 */
        }

        /* --- Cấu trúc Lưới (Grid) --- */
        .form-grid {
            display: table;
            width: 100%;
            border-collapse: collapse; /* Gộp viền */
            border-top: 1px solid #000; /* Viền đầu tiên */
        }

        .form-row {
            display: table-row;
        }

        .form-cell {
            display: table-cell;
            padding: 0.75rem 1rem; /* Giảm padding một chút */
            border-bottom: 1px solid #000;
            vertical-align: top;
        }

        /* Thêm viền trái cho các ô không phải ô đầu tiên */
        .form-row .form-cell:not(:first-child) {
            border-left: 1px solid #000;
        }

        /* Định nghĩa chiều rộng cột theo phân số */
        .col-2-4 { width: 50%; } /* 2/4 = 50% */
        .col-1-4 { width: 25%; } /* 1/4 = 25% */
        .col-3-4 { width: 75%; } /* 3/4 = 75% */

        /* --- Nội dung trong ô --- */
        .col-title {
            font-weight: 700;
            display: block;
            margin-bottom: 0.5rem;
        }

        .col-title-inline {
            font-weight: 700;
            display: inline-block;
            width: 80px; /* Cố định chiều rộng nhãn */
        }

        .col-data {
            font-weight: 700; /* Dữ liệu in đậm */
            margin-bottom: 0.5rem;
        }

        .data-block {
            min-height: 50px; /* Chiều cao tối thiểu cho hàng 1, 2 */
        }

        .height-fixed {
            height: 140px; /* Chiều cao cố định hàng 3 */
        }

        .height-footer {
            height: 160px; /* Chiều cao cố định footer */
        }

        /* Checkbox tùy chỉnh */
        .checkbox-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-weight: 500; /* Chữ cạnh checkbox */
        }

        .checkbox-box {
            width: 16px;
            height: 16px;
            border: 1px solid #000;
            margin-right: 8px;
            flex-shrink: 0;
        }

        .small-note {
            font-size: 0.75rem;
            font-style: italic;
            display: block;
            margin-top: 0.25rem;
            font-weight: 400;
        }

        /* --- Footer --- */
        .footer-contact {
            text-align: center;
            padding-top: 1rem; /* Căn giữa thủ công */
        }

        .footer-contact svg {
            width: 32px;
            height: 32px;
            fill: currentColor;
            margin-bottom: 0.25rem;
        }

        .contact-phone {
            font-weight: 700;
            font-size: 1.2rem;
            color: #e60023; /* Màu đỏ Logship */
            margin-top: 0.5rem;
        }

        .contact-web {
            font-size: 0.8rem;
            color: #000;
            font-weight: 500;
            display: block; /* Hiển thị website bên dưới */
        }

        .footer-notes {
            list-style-type: none;
            padding-left: 0;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .footer-notes li {
            margin-bottom: 0.35rem;
        }

    </style>
</head>
<body>

<div class="container">
    <!-- Hàng Tiêu Đề -->
    <div class="form-header">
        <img src="https://logship.vn/wp-content/uploads/2021/08/logo-logship-02-1.png" alt="Logship Logo">
        <h1>PHIẾU GỬI HÀNG</h1>
        <div class="header-spacer"></div> <!-- Spacer để căn giữa -->
    </div>

    <!-- Bảng nội dung chính -->
    <div class="form-grid">
        <!-- Hàng 1: Người gửi | Nội dung hàng hóa | Thanh toán cước -->
        <div class="form-row">
            <!-- Cột 1: Người gửi (2/4) -->
            <div class="form-cell col-2-4">
                <span class="col-title">Người gửi: YÊN XUÂN</span>
                <div class="col-data">
                    <span class="col-title-inline">Điện thoại:</span>
                    0909177197
                </div>
                <div class="col-data">
                    <span class="col-title-inline">Địa chỉ:</span>
                    55 ĐẶNG DUNG, TÂN ĐỊNH, QUẬN 1, HCM
                </div>
            </div>
            <!-- Cột 2: Nội dung hàng hóa (1/4) -->
            <div class="form-cell col-1-4 data-block">
                <span class="col-title">Nội dung hàng hóa:</span>
                <span class="small-note">(Tên hàng hóa, số lượng)</span>
            </div>
            <!-- Cột 3: Thanh toán cước (1/4) -->
            <div class="form-cell col-1-4 data-block">
                <span class="col-title">Thanh toán cước:</span>
                <div class="checkbox-item">
                    <div class="checkbox-box"></div>
                    <span>Người gửi</span>
                </div>
                <div class="checkbox-item">
                    <div class="checkbox-box"></div>
                    <span>Người nhận</span>
                </div>
            </div>
        </div>

        <!-- Hàng 2: Người nhận | Cước phí | Trọng lượng -->
        <div class="form-row">
            <!-- Cột 1: Người nhận (2/4) -->
            <div class="form-cell col-2-4">
                <span class="col-title">Người nhận: CHỊ THƯ</span>
                <div class="col-data">
                    <span class="col-title-inline">Điện thoại:</span>
                    0918790015
                </div>
                <div class="col-data">
                    <span class="col-title-inline">Địa chỉ:</span>
                    55 ĐẶNG DUNG, TÂN ĐỊNH, QUẬN 1, HCM
                </div>
            </div>
            <!-- Cột 2: Cước phí (1/4) -->
            <div class="form-cell col-1-4 data-block">
                <span class="col-title">Cước phí:</span>
                <div style="text-align: right; font-weight: 700; padding-right: 1rem;">
                    /VNĐ
                </div>
            </div>
            <!-- Cột 3: Trọng lượng/kích thước (1/4) -->
            <div class="form-cell col-1-4 data-block">
                <span class="col-title">Trọng lượng/ kích thước:</span>
            </div>
        </div>

        <!-- Hàng 3: Dịch vụ | Dịch vụ cộng thêm | NV chấp nhận | NV giao -->
        <div class="form-row">
            <!-- Cột 1: Dịch vụ (1/4) -->
            <div class="form-cell col-1-4 height-fixed">
                <span class="col-title">Dịch vụ:</span>
                <div class="checkbox-item">
                    <div class="checkbox-box"></div>
                    <span>Chuyển phát nhanh</span>
                </div>
                <div class="checkbox-item">
                    <div class="checkbox-box"></div>
                    <span>Chuyển phát tiết kiệm</span>
                </div>
                <div class="checkbox-item">
                    <div class="checkbox-box"></div>
                    <span>Hoả tốc, hẹn giờ</span>
                </div>
            </div>
            <!-- Cột 2: Dịch vụ cộng thêm (1/4) -->
            <div class="form-cell col-1-4 height-fixed">
                <span class="col-title">Dịch vụ cộng thêm:</span>
                <div class="checkbox-item">
                    <div class="checkbox-box"></div>
                    <span>Bảo hiểm</span>
                </div>
                <div class="checkbox-item">
                    <div class="checkbox-box"></div>
                    <span>Đồng kiểm</span>
                </div>
            </div>
            <!-- Cột 3: Nhân viên chấp nhận (1/4) -->
            <div class="form-cell col-1-4 height-fixed">
                <span class="col-title">Nhân viên chấp nhận:</span>
                <span class="small-note">(Ngày nhận)</span>
            </div>
            <!-- Cột 4: Nhân viên giao (1/4) -->
            <div class="form-cell col-1-4 height-fixed">
                <span class="col-title">Nhân viên giao:</span>
                <span class="small-note">(Ngày giao)</span>
            </div>
        </div>

        <!-- Hàng 4: Footer (Liên hệ | Lưu ý) -->
        <div class="form-row">
            <!-- Cột 1: Liên hệ (1/4) -->
            <div class="form-cell col-1-4 height-footer">
                <div class="footer-contact">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.363-1.03-.038-2.137.703-2.877L1.885.511z"/>
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
                        <path d="M4.715 6.542 3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5L8 6.086a1.002 1.002 0 0 0-.154.199 2 2 0 0 1 .861 3.337L6.88 11.45a2 2 0 1 1-2.83-2.83l.793-.792a4.018 4.018 0 0 1-.128-1.287z"/>
                        <path d="M6.586 4.672A3 3 0 0 0 7.414 9.5l.775-.776a2 2 0 0 1-1.72-3.337L8.58 3.51a2 2 0 1 1 2.83 2.83l-.793.792c.112.42.155.855.128 1.287l1.372-1.372a3 3 0 1 0-4.243-4.243L6.586 4.672z"/>
                    </svg>

                    <div class="contact-phone">
                        028 6653 8616
                        <span class="contact-web">www.logship.vn</span>
                    </div>
                </div>
            </div>
            <!-- Cột 2: Lưu ý (3/4) -->
            <div class="form-cell col-3-4 height-footer">
                <span class="col-title">Lưu ý:</span>
                <ul class="footer-notes">
                    <li>1. Logship là đơn vị trung gian vận chuyển, không chịu trách nhiệm về chất lượng hàng hóa.</li>
                    <li>2. Để kiểm tra tình trạng đơn giá và quy định trách nhiệm vận chuyển, vui lòng tham khảo Website: https://logship.vn/ hoặc liên hệ hotline 0918790015.</li>
                    <li>3. Người gửi cam kết nội dung bên gửi là tài sản hợp pháp, có đầy đủ hóa đơn, chứng từ theo quy định pháp luật.</li>
                </ul>
            </div>
        </div>

    </div> <!-- .form-grid -->

</div> <!-- .container -->

</body>
</html>