<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phiếu Gửi Logship</title>
    <style>
        /* --- CSS SETUP --- */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Times New Roman', serif; /* Font giống văn bản hành chính */
            font-size: 11pt;
            background: #e0e0e0;
            color: #000;
        }

        .container {
            width: 210mm; /* A5 Landscape Width */
            height: 148mm; /* A5 Landscape Height */
            background: #fff;
            margin: 0px auto;
            position: relative;
            padding: 1mm;
            overflow: hidden;
            border: 1px solid #ccc;
        }

        /* --- UTILS --- */
        .bold {
            font-weight: bold;
        }

        .flex {
            display: flex;
        }

        .row {
            display: flex;
            width: 100%;
        }

        .col {
            display: flex;
            flex-direction: column;
        }

        /* Borders */
        .b-top {
            border-top: 1px solid #000;
        }

        .b-bottom {
            border-bottom: 1px solid #000;
        }

        .b-left {
            border-left: 1px solid #000;
        }

        .b-right {
            border-right: 1px solid #000;
        }

        .b-all {
            border: 1px solid #000;
        }

        /* Số thứ tự đen (1, 2, 3...) */
        .idx-box {
            background: #000;
            color: #fff;
            font-family: sans-serif;
            font-size: 10px;
            font-weight: bold;
            width: 14px;
            height: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 4px;
            vertical-align: text-top;
        }

        .section-title {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 2px;
            display: flex;
        }

        /* --- HEADER --- */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 5px;
            position: relative;
        }

        .header-left {
            width: 30%;
            text-align: center;
        }

        .barcode {
            height: 35px;
            margin: 0 2px 2px auto;
            width: 80%;
        }

        .header-center {
            width: 40%;
            text-align: center;
            padding-top: 5px;
        }

        .header-title-vn {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header-title-en {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header-right {
            width: 30%;
            text-align: right;
        }

        .logo-img {
            width: 120px;
        }

        .top-idx {
            position: absolute;
            top: 0;
            left: 0;
            border: 1px solid #000;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            text-align: center;
            line-height: 18px;
            font-size: 12px;
        }

        /* --- MAIN GRID --- */
        .main-grid {
            border: 2px solid #000;
            display: flex;
            flex-direction: column;
            height: 110mm;
        }

        /* Row 1 & 2 Combined Area */
        .upper-section {
            display: flex;
            height: 50%;
        }

        /* Cột Trái (Người gửi + Người nhận) */
        .col-sender-receiver {
            width: 40%;
            display: flex;
            flex-direction: column;
        }

        .box-sender {
            flex: 1;
            padding: 4px;
            border-bottom: 1px solid #000;
        }

        .box-receiver {
            flex: 1;
            padding: 4px;
        }

        .info-line {
            font-size: 9pt;
            line-height: 1.3;
            margin-bottom: 2px;
        }

        .info-label {
            font-weight: bold;
        }

        /* Cột Phải (Địa chỉ to) */
        .col-destination {
            width: 60%;
            border-left: 1px solid #000;
            padding: 4px;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .watermark-text {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 9pt;
            font-weight: bold;
            color: #555;
            text-align: right;
        }

        .dest-province {
            font-size: 8pt;
            font-weight: bold;
            margin-top: 10px;
            line-height: 1;
        }

        .dest-district {
            font-size: 8pt;
            font-weight: bold;
            margin-top: 5px;
        }

        .dest-ward {
            font-size: 8pt;
            font-weight: bold;
            margin-top: 5px;
        }

        /* Khung mã vùng & Bưu kiện */
        .dest-codes {
            margin-top: auto; /* Đẩy xuống dưới */
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-bottom: 5px;
        }

        .box-hanoi {
            border: 2px dashed red;
            color: red;
            font-weight: bold;
            padding: 5px 15px;
            font-size: 12pt;
        }

        .box-routing-code {
            border: 3px solid #000;
            padding: 5px 5px;
            font-size: 14pt;
            font-weight: bold;
        }

        /* Row 3: Nội dung, Dịch vụ, Cước */
        .middle-section {
            display: flex;
            border-top: 1px solid #000;
            height: 30%;
        }

        .box-content {
            width: 40%;
            border-right: 1px solid #000;
            padding: 4px;
        }

        .box-service {
            width: 30%;
            border-right: 1px solid #000;
            padding: 4px;
            position: relative;
        }

        .box-fee {
            width: 30%;
            padding: 4px;
            font-size: 9pt;
        }

        .fee-line {
            display: flex;
            justify-content: space-between;
        }

        /* Watermark chéo */
        .watermark-logo {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            opacity: 0.1;
            font-size: 30pt;
            font-weight: bold;
            color: red;
            pointer-events: none;
            z-index: 0;
            white-space: nowrap;
        }

        /* Row 4: Thu tiền, QR, Ký tên */
        .bottom-section {
            display: flex;
            border-top: 1px solid #000;
            height: 30%;
        }

        .box-cod {
            width: 40%;
            border-right: 1px solid #000;
            padding: 4px;
            position: relative;
        }

        .cod-amount {
            font-size: 36pt;
            font-weight: bold;
            text-align: center;
        }

        .box-qr-sender {
            width: 20%;
            border-right: 1px solid #000;
            padding: 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .qr-placeholder {
            width: 60px;
            height: 60px;
            background: #000;
            margin-bottom: 5px;
        }

        .box-receiver-sign {
            width: 20%;
            padding: 4px;
            text-align: center;
        }

        /* --- FOOTER --- */
        .footer {
            margin-top: 5px;
            display: flex;
            align-items: flex-start;
            font-size: 6pt;
        }

        .footer-left {
            width: 20%;
            display: flex;
            align-items: center;
            border-right: 1px dashed #ccc;
            padding-right: 5px;
        }

        .hotline-box {
            font-weight: bold;
            color: #d32f2f;
            font-size: 10pt;
        }

        .footer-right {
            width: 80%;
            padding-left: 10px;
        }

        .footer-right p {
            margin-bottom: 2px;
        }

    </style>
</head>
<body>
<?php echo isset($content) ? $content : ''; ?>
</body>
</html>