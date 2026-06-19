<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhãn A7</title>
    <style>
        @page {
            size: 105mm 74mm;
            margin: 0 !important;
            /* đảm bảo landscape khi trình duyệt hỗ trợ */
            size: 105mm 74mm landscape;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            width: 105mm;
            height: 74mm;
            overflow: hidden;
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.35;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .label {
            position: relative;
            width: 105mm;
            height: 74mm;
            padding: 2mm;
            border: 1px dashed #999;
            background-color: #fff;
            /* tránh bị ngắt trang khi in */
            page-break-inside: avoid;
            page-break-after: avoid;
            page-break-before: avoid;
        }

        /* Đảm bảo ảnh không tràn kích thước nhãn */
        .label img {
            max-width: 100%;
            max-height: 100%;
            height: auto;
        }

        /* SỐ 1 */
        .num {
            position: absolute;
            left: 2mm;
            top: 2mm;
            width: 6mm;
            height: 6mm;
            border: 1px solid #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
        }

        /* TỈNH/TP - DƯỚI SỐ 1 */
        .city {
            position: absolute;
            top: 9mm;
            font-size: 14px;
        }

        .ward {
            position: absolute;
            top: 15mm;
            font-size: 14px;
        }

        /* LOGO + INFO PHẢI */
        .info {
            position: absolute;
            right: 2mm;
            top: 2mm;
            width: 50mm;
            text-align: right;
        }

        .info img {
            height: 8mm;
            margin-bottom: 0.8mm;
            display: block;
            margin-left: auto;
        }

        .info div {
            font-size: 14px;
        }

        /* ĐƯỜNG KẺ 1 */
        .line1 {
            position: absolute;
            left: 2mm;
            right: 2mm;
            top: 20mm;
            border-top: 1.5px dashed #000;
            margin-top: 5px;
        }

        /* BARCODE */
        .barcode {
            position: absolute;
            left: 2mm;
            top: 24mm;
            width: 45mm;
            text-align: left;
        }

        .barcode img {
            height: 12mm;
            margin-bottom: 0.8mm;
        }

        .barcode-id {
            font-size: 9.5px;
            letter-spacing: 0.3px;
        }

        /* MÃ ĐƠN + NÚT */
        .order {
            position: absolute;
            right: 2mm;
            top: 24mm;
            width: 50mm;
            text-align: right;
        }

        .order-code {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 1mm;
        }

        .btn {
            display: inline-block;
            border: 1px solid #000;
            padding: 1.2mm 3mm;
            font-size: 14px;
            background: white;
        }

        /* ĐƯỜNG KẺ 2 */
        .line2 {
            position: absolute;
            left: 2mm;
            right: 2mm;
            top: 42mm;
            border-top: 1.5px dashed #000;
        }

        /* NGƯỜI NHẬN */
        .receiver {
            position: absolute;
            left: 2mm;
            top: 44mm;
            width: 68mm;
            font-size: 13px;
            line-height: 1.4;
        }

        .receiver strong {
            font-weight: bold;
        }

        /* QR */
        .qr {
            position: absolute;
            right: 2mm;
            top: 44mm;
            width: 25mm;
        }

        .qr img {
            width: 23mm;
            height: 23mm;
            border: 1px solid #000;
        }

        /* === MÀN HÌNH: NHÃN RA GIỮA === */
        @media screen {
            html, body {
                width: 100vw;
                height: 100vh;
                background: #f0f0f0;
                display: flex;
                justify-content: center;
                align-items: center;
                margin: 0;
                padding: 20px;
            }

            .label {
                border: 1px dashed #ccc;
                background: white;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }
        }

        /* === IN: KHÔNG MARGIN, 1 TRANG === */
        @media print {
            html, body {
                width: 105mm !important;
                height: 74mm !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: visible !important;
                -webkit-transform: none !important;
                transform: none !important;
                zoom: 1 !important;
            }

            /* Ẩn tất cả phần tử con trực tiếp của body, chỉ hiện body > .label */
            body > * { visibility: hidden !important; }
            body > .label, body > .label * { visibility: visible !important; }

            /* loại bỏ box-shadow/border khi in để tránh gây overflow */
            .label { box-shadow: none !important; border: none !important; }

            /* đảm bảo .label nằm độc lập, phù hợp kích thước A7 */
            .label {
                position: relative !important;
                display: block !important;
                width: 105mm !important;
                height: 74mm !important;
                max-width: 105mm !important;
                max-height: 74mm !important;
                margin: 0 !important;
                padding: 2mm !important;
                box-sizing: border-box !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            /* reset nội dung bên trong nhãn để nằm đúng layout */
            .label > * { box-sizing: border-box !important; }

            /* các hình ảnh giảm kích thước để đảm bảo không tràn */
            .qr img { width: 20mm !important; height: 20mm !important; }
            .barcode img { height: 13mm !important; }

            /* loại bỏ pseudo elements */
            body::before, body::after { content: none !important; }
        }
    </style>
</head>
<body>
<?php echo isset($content) ? $content : ''; ?>
</body>
</html>