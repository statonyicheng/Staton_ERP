<?php
// 目前路徑，用於標記側邊欄 active 與自動展開群組
$cur = trim(uri_string(), '/');
$isActive = function (string $path) use ($cur): bool {
    $p = trim($path, '/');
    if ($p === '') return $cur === '';
    return $cur === $p || strpos($cur, $p . '/') === 0;
};

// 完整 ERP 模組架構（label, 路徑, icon, 建置中?）
$adminOnly = (bool) session()->get('isAdmin');
$groups = [
    ['id' => 'grpBase', 'label' => '基本資料管理', 'icon' => 'bi-folder2-open', 'items' => [
        ['客戶資料管理', 'customer', 'bi-people', false],
        ['廠商資料管理', 'supplier', 'bi-truck', false],
        ['商品資料管理', 'product', 'bi-box-seam', false],
        ['產品分類管理', 'product-category', 'bi-tags', false],
        ['商品價格管理', 'pricing', 'bi-currency-exchange', false],
        ['結帳方式管理', 'payment-method', 'bi-cash-coin', false],
        ['會計科目設定', 'account', 'bi-list-ol', false],
    ]],
    ['id' => 'grpSales', 'label' => '銷售管理', 'icon' => 'bi-graph-up-arrow', 'items' => [
        ['報價單管理', 'quote', 'bi-file-earmark-text', false],
        ['訂單管理', 'order', 'bi-receipt', false],
        ['出貨管理', 'shipment', 'bi-box-arrow-right', false],
        ['銷售統計 / 報表', 'sales-report', 'bi-bar-chart', false],
    ]],
    ['id' => 'grpPurchase', 'label' => '採購管理', 'icon' => 'bi-cart-plus', 'items' => [
        ['請購作業', 'purchase-requisition', 'bi-clipboard-plus', false],
        ['採購單管理', 'purchase-order', 'bi-clipboard-check', false],
        ['進貨 / 退貨管理', 'goods-receipt', 'bi-box-arrow-in-down', false],
        ['採購報表', 'purchase-report', 'bi-bar-chart', false],
    ]],
    ['id' => 'grpInventory', 'label' => '庫存管理', 'icon' => 'bi-boxes', 'items' => [
        ['倉庫資料管理', 'warehouse', 'bi-house-gear', false],
        ['庫存查詢', 'inventory', 'bi-search', false],
        ['日常異動處理', 'stock-movement', 'bi-arrow-left-right', false],
        ['批號 / 序號管理', 'batch', 'bi-upc-scan', false],
        ['庫存盤點', 'stocktake', 'bi-clipboard-data', false],
        ['存貨計價 / 結轉', 'inventory-valuation', 'bi-calculator', false],
    ]],
    ['id' => 'grpProd', 'label' => '生產管理', 'icon' => 'bi-diagram-3', 'items' => [
        ['產品結構 BOM', 'bom', 'bi-diagram-2', false],
        ['製令管理', 'work-order', 'bi-hammer', false],
        ['批次需求計劃 MRP', 'mrp', 'bi-list-check', false],
    ]],
    ['id' => 'grpFinance', 'label' => '會計財務', 'icon' => 'bi-bank', 'items' => [
        ['會計總帳', 'ledger', 'bi-journal-bookmark', false],
        ['分錄傳票（借貸）', 'journal', 'bi-journal-richtext', false],
        ['交易登錄（收付）', 'transaction', 'bi-journal-text', false],
        ['四階損益分析', 'pnl', 'bi-graph-up', false],
        ['資金餘額表', 'cashflow', 'bi-wallet2', false],
        ['自動分錄', 'auto-journal', 'bi-lightning-charge', false],
        ['立沖帳作業', 'open-item/match', 'bi-check2-square', false],
        ['立沖帳餘額表', 'open-item/balance', 'bi-clipboard2-check', false],
        ['會計科目設定', 'account', 'bi-list-ol', false],
    ]],
    ['id' => 'grpFS', 'label' => '財務報表', 'icon' => 'bi-clipboard2-data', 'items' => [
        ['資產負債表', 'fs/balance', 'bi-columns-gap', false],
        ['損益表', 'fs/income', 'bi-file-earmark-bar-graph', false],
        ['現金流量表', 'fs/cashflow', 'bi-cash-coin', false],
        ['權益變動表', 'fs/equity', 'bi-pie-chart', false],
    ]],
    ['id' => 'grpArAp', 'label' => '應收 / 應付', 'icon' => 'bi-arrow-left-right', 'items' => [
        ['應收帳款管理', 'receivable', 'bi-cash-stack', false],
        ['應付帳款管理', 'payable', 'bi-credit-card', false],
        ['收付款作業', 'settlement', 'bi-bank2', false],
    ]],
    ['id' => 'grpAsset', 'label' => '成本與資產', 'icon' => 'bi-buildings', 'items' => [
        ['成本計算', 'cost', 'bi-calculator-fill', false],
        ['固定資產管理', 'fixed-asset', 'bi-hdd-stack', false],
    ]],
    ['id' => 'grpInvoice', 'label' => '電子發票', 'icon' => 'bi-receipt-cutoff', 'items' => [
        ['電子發票管理', 'invoice', 'bi-receipt-cutoff', false],
    ]],
];

// 系統管理群組（含權限判斷）
$sysItems = [];
if ($adminOnly) {
    $sysItems[] = ['使用者管理', 'user', 'bi-people-fill', false];
}
$sysItems[] = ['個人資料', 'profile', 'bi-person', false];
$groups[] = ['id' => 'grpSystem', 'label' => '系統管理', 'icon' => 'bi-gear', 'items' => $sysItems];

// 計算每個群組是否需展開（含 active 項目，或預設展開前兩個營運群組）
$defaultOpen = ['grpBase', 'grpSales'];
?>
<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>仕坦登 ERP · 企業管理系統</title>
    <?php $icoV = @filemtime(FCPATH . 'favicon.ico'); $pngV = @filemtime(FCPATH . 'staton-icon.png'); ?>
    <link rel="icon" href="<?= base_url('favicon.ico') . '?v=' . $icoV ?>" sizes="any">
    <link rel="icon" type="image/png" href="<?= base_url('staton-icon.png') . '?v=' . $pngV ?>" sizes="256x256">
    <link rel="apple-touch-icon" href="<?= base_url('staton-icon.png') . '?v=' . $pngV ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+TC:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('css/custom.css') . '?v=' . @filemtime(FCPATH . 'css/custom.css') ?>">
</head>

<body>
    <!-- header -->
    <nav class="navbar navbar-expand-lg navbar-bg-color">
        <div class="container-fluid d-flex align-items-center">
            <button class="btn btn-outline-dark" id="sidebarToggle">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand ms-3" href="<?= url_to('HomeController::index') ?>">
                <span class="brand-mark"></span>
                <span>仕坦登 ERP<small>STATON ENTERPRISE ERP</small></span>
            </a>
            <?php if (session()->get('isLoggedIn')) : ?>
                <div class="ms-auto d-flex align-items-center gap-3">
                    <span class="fw-semibold">
                        <i class="bi bi-person-circle me-1"></i><?= esc(session()->get('displayName') ?? session()->get('username')) ?>
                    </span>
                    <a href="<?= site_url('logout') ?>" class="btn btn-outline-danger btn-sm">登出</a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <!-- content -->
    <div id="wrapper">
        <!-- Sidebar -->
        <div class="border-right" id="sidebar-wrapper">
            <div class="sidebar-heading">功能模組</div>

            <a href="<?= url_to('HomeController::index') ?>"
               class="erp-group-toggle <?= $isActive('') ? 'active' : '' ?>" style="text-decoration:none;">
                <span class="group-label"><i class="bi bi-speedometer2"></i> 儀表板</span>
            </a>

            <?php foreach ($groups as $g):
                $hasActive = false;
                foreach ($g['items'] as $it) { if ($isActive($it[1])) { $hasActive = true; break; } }
                $open = $hasActive || in_array($g['id'], $defaultOpen, true);
            ?>
                <div class="erp-group-toggle" data-bs-toggle="collapse" data-bs-target="#<?= $g['id'] ?>"
                     role="button" aria-expanded="<?= $open ? 'true' : 'false' ?>" aria-controls="<?= $g['id'] ?>">
                    <span class="group-label"><i class="bi <?= $g['icon'] ?>"></i> <?= esc($g['label']) ?></span>
                    <i class="bi bi-chevron-down caret"></i>
                </div>
                <div class="collapse <?= $open ? 'show' : '' ?>" id="<?= $g['id'] ?>">
                    <div class="list-group list-group-flush">
                        <?php foreach ($g['items'] as $it): [$label, $path, $icon, $soon] = $it; ?>
                            <a href="<?= site_url($path) ?>"
                               class="list-group-item list-group-item-action <?= $isActive($path) ? 'active' : '' ?>">
                                <i class="bi <?= $icon ?>"></i> <?= esc($label) ?>
                                <?php if ($soon): ?><span class="soon">建置中</span><?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div style="height: 24px;"></div>
        </div>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <div class="container-fluid">
                <?php
                // 匯出按鈕：目前路徑若有登錄於 ExportController，就自動顯示 Excel / PDF
                // （'fs/balance' → 'fs-balance'）。只在清單/報表頁出現，新增/編輯頁不會。
                $exportKey = str_replace('/', '-', trim(uri_string(), '/'));
                $canExport = $exportKey !== '' && in_array($exportKey, \App\Controllers\ExportController::exportableKeys(), true);
                ?>
                <?php if ($canExport): ?>
                    <div class="d-flex justify-content-end mb-2">
                        <?= view('components/export_buttons', ['key' => $exportKey]) ?>
                    </div>
                <?php endif; ?>
                <?= $this->renderSection('content') ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?= base_url('js/script.js') ?>"></script>

    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.getElementById('wrapper').classList.toggle('toggled');
        });
    </script>
</body>

</html>
