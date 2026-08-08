<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-pie-chart me-2"></i>權益變動表</h1>
    <form method="get" class="d-flex gap-2 align-items-center"><input type="number" name="year" class="form-control form-control-sm" value="<?= $year ?>" style="width:110px;" onchange="this.form.submit()"><span class="text-muted small">年度</span></form>
</div>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>Statement of Changes in Equity · 期初權益 ＋ 本期損益 ＋ 增減資 ＝ 期末權益。</div>

<div class="card shadow-sm"><div class="card-body">
    <h5 class="mb-3"><?= $year ?> 年度權益變動表</h5>
    <div class="table-responsive"><table class="table align-middle text-end">
        <thead class="table-light"><tr><th class="text-start">項目</th><th>股本/資本</th><th>保留盈餘</th><th>權益合計</th></tr></thead>
        <tbody>
            <tr><td class="text-start fw-semibold">期初餘額</td><td><?= $fmt($capOpen) ?></td><td><?= $fmt($reOpen) ?></td><td class="fw-semibold"><?= $fmt($capOpen + $reOpen) ?></td></tr>
            <tr><td class="text-start ps-4 text-muted">本期損益</td><td class="text-muted">—</td><td class="<?= $curNet<0?'text-danger':'' ?>"><?= $fmt($curNet) ?></td><td class="<?= $curNet<0?'text-danger':'' ?>"><?= $fmt($curNet) ?></td></tr>
            <tr><td class="text-start ps-4 text-muted">增(減)資 / 股東往來</td><td class="<?= $capChange<0?'text-danger':'' ?>"><?= $fmt($capChange) ?></td><td class="text-muted">—</td><td class="<?= $capChange<0?'text-danger':'' ?>"><?= $fmt($capChange) ?></td></tr>
        </tbody>
        <tfoot><tr class="fw-bold" style="background:rgba(244,183,2,0.14);">
            <td class="text-start">期末餘額</td>
            <td><?= $fmt($capClose) ?></td>
            <td><?= $fmt($reClose) ?></td>
            <td class="fs-5" style="color:var(--navy);"><?= $fmt($capClose + $reClose) ?></td>
        </tr></tfoot>
    </table></div>
</div></div>

<?= $this->endSection() ?>
