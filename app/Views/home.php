<?= $this->extend('_layout') ?>

<?= $this->section('content') ?>
<style>
    .erp-hero {
        background: linear-gradient(120deg, #000e2f 0%, #0a1c44 100%);
        color: #fff; border-radius: 16px; padding: 34px 38px; margin-bottom: 28px;
        position: relative; overflow: hidden; border-left: 5px solid var(--gold);
    }
    .erp-hero::after {
        content: ""; position: absolute; right: -40px; top: -40px; width: 220px; height: 220px;
        background: radial-gradient(circle, rgba(244,183,2,0.16), transparent 70%);
    }
    .erp-hero h2 { font-weight: 700; letter-spacing: 2px; margin: 0 0 6px; }
    .erp-hero .sub { color: var(--gold); letter-spacing: 4px; font-size: .75rem; text-transform: uppercase; }
    .erp-hero .welcome { margin-top: 14px; color: rgba(255,255,255,0.82); }

    .quick-card {
        display: block; background: #fff; border: 1px solid var(--line); border-radius: 12px;
        padding: 20px; text-decoration: none; height: 100%;
        transition: all .2s ease;
    }
    .quick-card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(0,14,47,.10); border-color: var(--gold); }
    .quick-card .qi {
        width: 46px; height: 46px; border-radius: 10px; background: rgba(0,14,47,.06);
        color: var(--navy); display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; margin-bottom: 12px;
    }
    .quick-card:hover .qi { background: var(--gold); color: var(--navy); }
    .quick-card .qt { color: var(--navy); font-weight: 600; font-size: 1.02rem; }
    .quick-card .qd { color: var(--muted); font-size: .82rem; margin-top: 2px; }

    .subsys-card { background:#fff; border:1px solid var(--line); border-radius:12px; padding:18px 20px; height:100%; }
    .subsys-card .st { color:var(--navy); font-weight:700; display:flex; align-items:center; gap:8px; }
    .subsys-card .st i { color: var(--gold); }
    .subsys-card ul { list-style:none; padding:0; margin:12px 0 0; }
    .subsys-card li { font-size:.85rem; color:#4a5160; padding:4px 0; border-bottom:1px dashed var(--line); }
    .subsys-card li:last-child { border-bottom:none; }
    .subsys-card li .dot { display:inline-block; width:6px; height:6px; border-radius:50%; background:var(--gold); margin-right:8px; vertical-align:middle; }
    .section-title { color:var(--navy); font-weight:700; font-size:1.05rem; margin:6px 0 14px; padding-left:10px; border-left:3px solid var(--gold); }
</style>

<div class="erp-hero">
    <div class="sub">Staton Enterprise ERP</div>
    <h2>仕坦登 ERP 企業管理系統</h2>
    <div class="welcome">
        <i class="bi bi-hand-index-thumb"></i>
        歡迎回來，<?= esc(session()->get('displayName') ?? session()->get('username')) ?>。整合銷售、採購、庫存、生產、財務會計的一站式企業管理平台。
    </div>
</div>

<div class="section-title">常用作業</div>
<div class="row g-3 mb-4">
    <?php
    $quick = [
        ['客戶資料', '維護客戶主檔', 'bi-people', site_url('customer')],
        ['商品資料', '維護商品與規格', 'bi-box-seam', site_url('product')],
        ['報價單', '建立與追蹤報價', 'bi-file-earmark-text', site_url('quote')],
        ['訂單管理', '訂單與交付', 'bi-receipt', site_url('order')],
        ['出貨管理', '出貨與物流', 'bi-box-arrow-right', site_url('shipment')],
    ];
    foreach ($quick as $q): ?>
        <div class="col-6 col-md-4 col-xl">
            <a class="quick-card" href="<?= $q[3] ?>">
                <div class="qi"><i class="bi <?= $q[2] ?>"></i></div>
                <div class="qt"><?= $q[0] ?></div>
                <div class="qd"><?= $q[1] ?></div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="section-title">ERP 系統模組總覽</div>
<div class="row g-3">
    <?php
    $subsystems = [
        ['基本資料管理', 'bi-folder2-open', ['客戶 / 廠商主檔', '商品與分類', '商品價格', '結帳方式']],
        ['銷售管理', 'bi-graph-up-arrow', ['報價單', '訂單', '出貨', '銷售統計報表']],
        ['採購管理', 'bi-cart-plus', ['請購作業', '採購單', '進貨 / 退貨', '採購報表']],
        ['庫存管理', 'bi-boxes', ['庫存查詢', '日常異動', '批號序號', '盤點 / 計價結轉']],
        ['生產管理', 'bi-diagram-3', ['產品結構 BOM', '製令管理', '批次需求 MRP']],
        ['會計財務', 'bi-bank', ['會計總帳 / 傳票', '四階損益分析', '資金餘額表', '自動分錄 / 科目']],
        ['應收 / 應付', 'bi-arrow-left-right', ['應收帳款', '應付帳款', '收付款作業']],
        ['成本與資產', 'bi-buildings', ['成本計算', '固定資產']],
        ['電子發票', 'bi-receipt-cutoff', ['發票開立 / 作廢', '字軌管理', '平台上傳']],
    ];
    foreach ($subsystems as $s): ?>
        <div class="col-md-6 col-xl-4">
            <div class="subsys-card">
                <div class="st"><i class="bi <?= $s[1] ?>"></i> <?= $s[0] ?></div>
                <ul>
                    <?php foreach ($s[2] as $li): ?>
                        <li><span class="dot"></span><?= $li ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<p class="text-muted small mt-4 mb-2">
    <i class="bi bi-shield-check" style="color:var(--gold);"></i>
    仕坦登企業管理顧問有限公司 · STATON Enterprise Management Consulting Co., Ltd.
</p>
<?= $this->endSection() ?>
