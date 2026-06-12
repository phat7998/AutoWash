<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhãn A5</title>
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
            margin: 0rem auto;
            background-color: #fff;
            border: 1px solid #000; /* Viền đen chính */
            overflow: hidden;
        }

        /* --- Header --- */
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
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
            width: 200px; /* Cùng chiều rộng với logo để căn giữa h1 */
        }

        /* --- Cấu trúc Lưới (Grid) --- */
        .form-grid {
            /* Bỏ display: table, vì các hàng có số cột khác nhau */
            width: 100%;
            border-top: 1px solid #000; /* Viền đầu tiên */
        }

        .form-row {
            display: flex; /* Sử dụng FLEXBOX cho mỗi hàng */
            width: 100%;
        }

        .form-cell {
            /* Bỏ display: table-cell */
            padding: 0.5rem 1rem; /* Giảm padding một chút */
            box-shadow: 0 0 0 0.2px #000;
            border: none;
            vertical-align: top;
            flex-grow: 0;
            flex-shrink: 0;
            box-sizing: border-box; /* Quan trọng để padding không ảnh hưởng width */
        }

        /* Thêm viền trái cho các ô không phải ô đầu tiên */
        .form-row .form-cell:not(:first-child) {
            /*border-left: 1px solid #000;*/
        }

        /* Định nghĩa chiều rộng cột theo phân số (sử dụng flex-basis) */
        .col-2-4 { flex-basis: 50%; } /* 2/4 = 50% */
        .col-1-4 { flex-basis: 25%; } /* 1/4 = 25% */
        .col-3-4 { flex-basis: 75%; } /* 3/4 = 75% */

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
            /*font-weight: 700; /* Dữ liệu in đậm */
            margin-bottom: 0.5rem;
        }

        .data-block {
            /* Đã thay đổi từ min-height để cố định chiều cao */
            /*height: 100px; /* Chiều cao cố định cho hàng 1, 2 */
        }

        .height-fixed {
            /*height: 140px; /* Chiều cao cố định hàng 3 */
        }

        .height-footer {
            /*height: 160px; /* Chiều cao cố định footer */
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
            padding-left: 2px;
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
            padding-top: 0.5rem; /* Căn giữa thủ công */
        }

        .footer-contact svg {
            width: 15px;
            height: 15px;
            fill: currentColor;
            margin-bottom: 0.25rem;
        }

        .contact-phone {
            font-weight: 700;
            font-size: 0.8rem;
            color: #e60023; /* Màu đỏ Logship */
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
            font-size: 0.5rem;
            font-weight: 500;
        }
        .footer-notes li {
            margin-bottom: 0.35rem;
        }

    </style>
</head>
<body>
<?php echo isset($content) ? $content : ''; ?>
</body>
</html>