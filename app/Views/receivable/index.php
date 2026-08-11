<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<?php
$fmt = fn($n) => number_format((int) $n);
$acctNames = implode('、', array_map(fn($a) => $a['ac_code'] . ' ' . $a['ac_name'], $accounts));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="mb-0"><i class="bi bi-cash-stack me-2"></i>應收帳款管理</h1>
    <div class="d-flex gap-2">
        <a href="<?= site_url('journal/create') ?>" class="btn btn-outline-primary">
            <i class="bi bi-plus-circle me-1"></i>開立分錄傳票
        </a>
        <a href="<?= site_url('open-item/match') ?>" class="btn btn-primary">
            <i class="bi bi-check2-square me-1"></i>收款沖銷
        </a>
    </div>
</div>

<div class="alert alert-info py-2">
    <i class="bi bi-info-circle me-2"></i>
    本頁直接讀<strong>會計帳上尚未沖銷的應收分錄</strong>，不是另一張獨立的表 ——
    只要開了「借 應收…／貸 收入」的傳票就會出現在這裡。
    納入的科目：<strong><?= esc($acctNames ?: '尚未設定') ?></strong>
    （可在 <a href="<?= site_url('account') ?>">會計科目設定</a> 調整哪些科目屬於應收）。
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card shadow-sm h-100"><div class="card-body d-flex justify-content-between align-items-center">
            <span class="text-muted">應收總額</span>
            <span class="fs-3 fw-bold"><?= $fmt($summary['total']) ?></span>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100"><div class="card-body d-flex justify-content-between align-items-center">
            <span class="text-muted">已收（已沖銷）</span>
            <span class="fs-3 fw-bold text-success"><?= $fmt($summary['settled']) ?></span>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100"><div class="card-body d-flex justify-content-between align-items-center">
            <span class="text-muted">未收餘額</span>
            <span class="fs-3 fw-bold text-danger"><?= $fmt($summary['open']) ?></span>
        </div></div>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="d-flex gap-2 flex-wrap align-items-center">
            <input type="text" class="form-control" style="max-width: 360px;" name="keyword"
                   placeholder="搜尋傳票號／摘要／科目…" value="<?= esc($keyword ?? '') ?>">
            <div class="form-check form-switch ms-2">
                <input class="form-check-input" type="checkbox" id="showAll" name="all" value="1"
                       <?= !empty($showAll) ? 'checked' : '' ?> onchange="this.form.submit()">
                <label class="form-check-label" for="showAll">含已收清</label>
            </div>
            <button class="btn btn-dark"><i class="bi bi-search me-1"></i>查詢</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 11%;">日期</th>
                        <th style="width: 14%;">傳票編號</th>
                        <th>摘要</th>
                        <th style="width: 16%;">會計科目</th>
                        <th class="text-end" style="width: 11%;">應收金額</th>
                        <th class="text-end" style="width: 11%;">已收</th>
                        <th class="text-end" style="width: 11%;">未收</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                            尚無未收的應收帳款<br>
                            <small>開一張「借 應收帳款／貸 收入」的分錄傳票，就會出現在這裡</small>
                        </td></tr>
                    <?php else: foreach ($items as $it): ?>
                        <tr>
                            <td><?= esc($it['jv_date']) ?></td>
                            <td>
                                <a href="<?= site_url('journal/view/' . $it['jv_id']) ?>" class="text-decoration-none">
                                    <?= esc($it['jv_no']) ?>
                                </a>
                            </td>
                            <td><?= esc($it['je_summary'] ?: $it['jv_summary']) ?></td>
                            <td><small class="text-muted"><?= esc($it['ac_code']) ?></small> <?= esc($it['ac_name']) ?></td>
                            <td class="text-end"><?= $fmt($it['amount']) ?></td>
                            <td class="text-end text-success"><?= $fmt($it['je_offset']) ?></td>
                            <td class="text-end fw-bold <?= (int) $it['open_amt'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                <?= $fmt($it['open_amt']) ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-light border mt-3 mb-4">
    <i class="bi bi-lightbulb me-2"></i>
    <strong>收款怎麼做：</strong>
    開一張「借 銀行存款／貸 應收帳款」的分錄傳票，再到
    <a href="<?= site_url('open-item/match') ?>">立沖帳作業</a>把它與原本那筆應收勾銷。
    沖銷完這裡的未收餘額就會減少，<strong>資金餘額表也會在實際收款的月份認列這筆現金流入</strong>。
</div>

<?= $this->endSection() ?>
