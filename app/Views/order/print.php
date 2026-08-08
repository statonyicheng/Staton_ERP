<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>訂單列印 - <?= esc($data['o_number']) ?></title>
    <?php
    // 檢查是否有任何商品有折扣，同時計算數量總計
    $hasDiscount = false;
    $totalQuantity = 0;
    if (!empty($data['items'])) {
        foreach ($data['items'] as $item) {
            if (!empty($item['oi_discount']) && $item['oi_discount'] > 0) {
                $hasDiscount = true;
            }
            $totalQuantity += (int)($item['oi_quantity'] ?? 0);
        }
    }
    ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Microsoft JhengHei", "微軟正黑體", Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.3;
            padding: 15px;
            background: #fff;
        }

        .container {
            width: 210mm;
            max-width: 210mm;
            margin: 0 auto;
            background: #fff;
        }

        /* 頁首 */
        .header {
            text-align: center;
            padding: 8px 10px;
        }

        .company-name {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .document-title {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .document-title-en {
            font-size: 9pt;
            color: #333;
        }

        /* 基本資訊區 */
        .info-section {
            display: table;
            width: 100%;
        }

        .info-row {
            display: table-row;
        }

        .info-cell {
            display: table-cell;
            padding: 6px 8px;
            vertical-align: top;
            font-size: 10pt;
        }

        .info-cell:last-child {
            border-right: none;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            min-width: 75px;
        }

        .info-right {
            text-align: right;
        }

        /* 表格 */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-size: 10pt;
            vertical-align: middle;
        }

        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 10pt;
        }

        .items-table td.text-left {
            text-align: left;
        }

        .items-table td.text-right {
            text-align: right;
        }

        .items-table td.text-center {
            text-align: center;
        }

        .items-table .discount-cell {
            color: #d9534f;
            font-weight: bold;
        }

        .items-table .image-cell {
            width: 80px;
            padding: 4px;
        }

        .items-table .image-cell img {
            max-width: 70px;
            max-height: 70px;
            object-fit: contain;
        }

        /* 備註 */
        .notes-section {
            padding: 8px 10px;
            border-top: 1px solid #000;
            margin-top: -1px;
        }

        .notes-title {
            font-weight: bold;
            margin-bottom: 4px;
            font-size: 10pt;
        }

        .notes-list {
            padding-left: 0;
            list-style-position: inside;
        }

        .notes-list li {
            margin-bottom: 2px;
            font-size: 9pt;
            line-height: 1.3;
        }

        /* 列印樣式 */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                padding: 0;
                margin: 0;
                background: white;
            }

            .container {
                width: 99%;
                max-width: 100%;
                margin: 0;
                background: white;
            }

            .no-print {
                display: none !important;
            }

            @page {
                margin: 10mm;
                size: A4 portrait;
            }

            /* 確保表格正確顯示 */
            .items-table {
                page-break-inside: auto;
                border-collapse: collapse;
                width: 100%;
            }

            .items-table th,
            .items-table td {
                border: 1px solid #000 !important;
                padding: 6px 4px;
            }

            .items-table th {
                background-color: #f5f5f5 !important;
            }

            .items-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            .summary-table {
                border-collapse: collapse;
                width: 100%;
            }

            .summary-table td {
                border: 1px solid #000 !important;
            }

            /* 圖片列印優化 */
            .items-table .image-cell img {
                max-width: 70px !important;
                max-height: 70px !important;
                display: block;
                margin: 0 auto;
            }

            /* 防止內容溢出 */
            .header,
            .info-section,
            .notes-section {
                page-break-inside: avoid;
            }

            /* 備註區域 */
            .notes-section {
                border-top: 1px solid #000 !important;
            }
        }

        /* 列印按鈕 */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14pt;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }

        .print-button:hover {
            background-color: #0b5ed7;
        }

        .empty-row {
            height: 35px;
        }

        .empty-row td {
            padding: 4px;
        }

        /* 預覽時的優化 */
        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>

<body>
    <!-- 列印按鈕 -->
    <button class="print-button no-print" onclick="window.print()">
        🖨️ 列印
    </button>

    <div class="container">
        <!-- 頁首 -->
        <div class="header">
            <div class="company-name">嵐石事業有限公司</div>
            <div class="document-title">採購單</div>
            <div class="document-title-en">Purchase Order Form</div>
        </div>

        <!-- 基本資訊 -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-cell" style="width: 70%;">
                    <div><span class="info-label">供應商名稱：</span><?= $data['o_vendor'] ?></div>
                </div>
                <div class="info-cell info-right" style="width: 30%;">
                    <div><span class="info-label">日期：</span><?= esc($data['o_date']) ?></div>
                </div>
            </div>
        </div>

        <div class="info-section">
            <div class="info-row">
                <div class="info-cell" style="width: 70%;">
                    <div>
                        <span class="info-label">聯絡人：</span>
                        <?= $data['o_vendor_contect'] ?? '' ?>
                    </div>
                </div>
                <div class="info-cell info-right" style="width: 30%;">
                    <div><span class="info-label">單號：</span><?= esc($data['o_number']) ?></div>
                </div>
            </div>
        </div>

        <div class="info-section">
            <div class="info-row">
                <div class="info-cell">
                    <div><span class="info-label">地址：</span><?= $data['o_vendor_address'] ?? '' ?></div>
                </div>
            </div>
        </div>

        <div class="info-section">
            <div class="info-row">
                <div class="info-cell">
                    <div><span class="info-label">送貨地址：</span>
                        <?= esc($data['o_shipping_address'] ?? '') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 商品明細表格 -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40%;">品名</th>
                    <th style="width: 10%;">顏色</th>
                    <th style="width: 10%;">數量/件</th>
                    <th style="width: 20%;">尺寸/mm</th>
                    <th style="width: 10%;">單價/CNY¥</th>
                    <th style="width: 10%;">小計</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data['items'])): ?>
                    <?php foreach ($data['items'] as $item): ?>
                        <?php
                        // 圖片檔名就是顏色/花色
                        $colorSpec = !empty($item['pi_name']) ? pathinfo($item['pi_name'], PATHINFO_FILENAME) : '';
                        ?>
                        <tr>
                            <td class="text-left"> <?= $item['p_name']; ?></td>
                            <td><?= esc($colorSpec) ?></td>
                            <td><?= esc($item['oi_quantity']) ?></td>
                            <td><?= esc($item['oi_size'] ?? '') ?></td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- 空白行 -->
                <?php for ($i = 0; $i < 3; $i++): ?>
                    <tr class="empty-row">
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                <?php endfor; ?>

                <!-- 數量與金額小計 -->
                <tr style="font-weight: bold; background-color: #f5f5f5;">
                    <td class="text-right"></td>
                    <td class="text-center">數量小計</td>
                    <td class="text-center"><?= $totalQuantity ?></td>
                    <td></td>
                    <td class="text-center">金額小計</td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <!-- 備註 -->
        <div class="notes-section">
            <div class="notes-title">備註：</div>
            <div class="notes-content" style="font-size: 9pt; line-height: 1.4; white-space: pre-wrap;">
                <?= nl2br(esc($data['o_notes'] ?? '')) ?>
            </div>
        </div>
    </div>

    <script>
        // 自動聚焦以便快速列印
        window.onload = function() {
            // 可選：自動開啟列印對話框
            // window.print();
        };
    </script>
</body>

</html>