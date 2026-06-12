<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tem A6 Shopee Style</title>
    <style>
        @page {
            size: 105mm 148mm;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f5; /* Màu nền khi xem trên web */
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            -webkit-print-color-adjust: exact;
        }

        .label {
            width: 105mm;
            height: 148mm;
            background: #fff;
            margin: 0 auto;
            position: relative;
            border: 1px solid #ddd; /* Viền mờ khi xem web */
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* === CÁC KHỐI CHÍNH === */
        .section {
            border-bottom: 2px solid #000; /* Đường kẻ phân cách đậm */
            padding: 5px 8px;
        }
        .section:last-child {
            border-bottom: none;
        }

        /* --- HEADER: LOGO & ROUTING --- */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 25mm;
            padding: 0 8px;
        }
        .routing-code {
            font-size: 32px;
            font-weight: 900;
            text-transform: uppercase;
            line-height: 1;
        }
        .logo-area {
            text-align: right;
        }
        .logo-area img {
            height: 12mm;
            display: block;
            margin-bottom: 2px;
        }
        .service-type {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #000;
            padding: 1px 4px;
            display: inline-block;
        }

        /* --- BARCODE --- */
        .barcode-section {
            text-align: center;
            padding: 8px 0;
            height: 28mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .barcode-section img {
            height: 16mm;
            width: 80%; /* Giới hạn chiều rộng barcode */
            object-fit: contain;
        }
        .tracking-number {
            font-family: monospace; /* Font giống máy đánh chữ */
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-top: 4px;
        }
        .order-ref {
            font-size: 10px;
            margin-top: 2px;
        }

        /* --- INFO NGƯỜI GỬI / NHẬN --- */
        .info-grid {
            display: flex;
            flex-direction: column;
            flex-grow: 1; /* Chiếm phần không gian còn lại */
        }

        .sender-row {
            padding: 5px 8px;
            border-bottom: 1px dashed #999; /* Nét đứt phân cách gửi/nhận */
            font-size: 11px;
            color: #333;
        }

        .receiver-row {
            padding: 8px;
            flex-grow: 1;
        }
        .label-title {
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 2px;
        }
        .receiver-name {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .receiver-phone {
            font-size: 15px;
            font-weight: bold;
            margin-left: 5px;
        }
        .receiver-address {
            font-size: 13px;
            margin-top: 4px;
            line-height: 1.3;
            max-height: 38px;
        }

        /* --- COD & CHI TIẾT --- */
        .footer-grid {
            display: flex;
            height: 35mm;
            border-top: 2px solid #000;
        }
        .col-left {
            width: 70%;
            border-right: 2px solid #000;
            display: flex;
            flex-direction: column;
        }
        .col-right {
            width: 30%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
        }

        .cod-area {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border-bottom: 1px solid #000;
            background: #f9f9f9; /* Nền nhẹ cho COD */
        }
        .cod-label {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .cod-amount {
            font-size: 24px;
            font-weight: 900;
            margin-top: 2px;
        }

        .package-details {
            padding: 5px;
            font-size: 11px;
            display: flex;
            justify-content: space-between;
        }

        /* --- QR CODE GÓC --- */
        .qr-img {
            width: 24mm;
            height: 24mm;
        }

        /* --- FOOTER NOTE --- */
        .note-area {
            font-size: 10px;
            text-align: center;
            padding: 4px;
            font-style: italic;
            border-top: 1px dashed #ccc;
        }

        /* === PRINT MODE === */
        @media print {
            body { background: none; }
            .label {
                border: none;
                width: 105mm;
                height: 148mm;
                page-break-after: always;
            }
            .cod-area { background: none !important; } /* Bỏ màu nền khi in nhiệt */
        }
    </style>
</head>
<body>
<?php echo isset($content) ? $content : ''; ?>
</body>
</html>