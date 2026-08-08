<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-cash-coin me-2"></i>現金流量表</h1>
    <form method="get" class="d-flex gap-2 align-items-center"><input type="number" name="year" class="form-control form-control-sm" value="<?= $year ?>" style="width:110px;" onchange="this.form.submit()"><span class="text-muted small">年度</span></form>
</div>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>Cash Flow Statement · 依含現金/銀行存款之分錄,按對方科目歸類營業/投資/籌資活動。</div>

<div class="card shadow-sm"><div class="card-body">
    <h5 class="mb-3"><?= $year ?> 年度現金流量表</h5>
    <table class="table align-middle">
        <tbody>
            <tr><td class="fw-semibold"><i class="bi bi-arrow-repeat me-1"></i>營業活動之現金流量</td><td class="text-end fw-semibold <?= $op<0?'text-danger':'' ?>"><?= $fmt($op) ?></td></tr>
            <tr><td class="fw-semibold"><i class="bi bi-building me-1"></i>投資活動之現金流量</td><td class="text-end fw-semibold <?= $inv<0?'text-danger':'' ?>"><?= $fmt($inv) ?></td></tr>
            <tr><td class="fw-semibold"><i class="bi bi-bank me-1"></i>籌資活動之現金流量</td><td class="text-end fw-semibold <?= $fin<0?'text-danger':'' ?>"><?= $fmt($fin) ?></td></tr>
            <tr class="table-light fw-bold"><td>本期現金淨增減</td><td class="text-end <?= $netCash<0?'text-danger':'' ?>"><?= $fmt($netCash) ?></td></tr>
            <tr><td>期初現金及約當現金</td><td class="text-end text-muted"><?= $fmt($openCash) ?></td></tr>
            <tr class="fw-bold" style="background:rgba(244,183,2,0.14);"><td>期末現金及約當現金</td><td class="text-end fs-5" style="color:var(--navy);"><?= $fmt($closeCash) ?></td></tr>
        </tbody>
    </table>
    <p class="small text-muted mb-0">現金類科目:現金、銀行存款。歸類為簡化版(資產類對方歸投資、收入支出歸營業、權益歸籌資),供管理參考。</p>
</div></div>

<?= $this->endSection() ?>
