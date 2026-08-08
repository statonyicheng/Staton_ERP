<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-columns-gap me-2"></i>資產負債表</h1>
    <form method="get" class="d-flex gap-2 align-items-center"><input type="number" name="year" class="form-control form-control-sm" value="<?= $year ?>" style="width:110px;" onchange="this.form.submit()"><span class="text-muted small">年底</span></form>
</div>

<div class="alert <?= $balanced?'alert-success':'alert-danger' ?> py-2">
    <i class="bi bi-<?= $balanced?'check-circle':'exclamation-triangle' ?> me-1"></i>
    Balance Sheet · <?= $year ?> 年底 ｜ 資產 <?= $fmt($tA) ?> <?= $balanced?'=':'≠' ?> 負債 <?= $fmt($tL) ?> + 權益 <?= $fmt($tE) ?> <?= $balanced?'(借貸平衡)':'(不平衡,請檢查分錄)' ?>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100"><div class="card-body">
            <h5 class="mb-3" style="color:var(--navy);"><i class="bi bi-box me-1" style="color:var(--gold);"></i>資產</h5>
            <table class="table align-middle mb-0">
                <tbody>
                    <?php foreach ($assets as $a): ?><tr><td><?= esc($a['name']) ?></td><td class="text-end"><?= $fmt($a['amt']) ?></td></tr><?php endforeach; ?>
                    <?php if (empty($assets)): ?><tr><td colspan="2" class="text-muted text-center py-3">無</td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light fw-bold"><td>資產總計</td><td class="text-end fs-5" style="color:var(--navy);"><?= $fmt($tA) ?></td></tr></tfoot>
            </table>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm mb-3"><div class="card-body">
            <h5 class="mb-3" style="color:var(--navy);"><i class="bi bi-credit-card me-1" style="color:var(--gold);"></i>負債</h5>
            <table class="table align-middle mb-0"><tbody>
                <?php foreach ($liab as $l): ?><tr><td><?= esc($l['name']) ?></td><td class="text-end"><?= $fmt($l['amt']) ?></td></tr><?php endforeach; ?>
                <?php if (empty($liab)): ?><tr><td colspan="2" class="text-muted text-center py-3">無</td></tr><?php endif; ?>
            </tbody><tfoot><tr class="table-light fw-bold"><td>負債總計</td><td class="text-end"><?= $fmt($tL) ?></td></tr></tfoot></table>
        </div></div>
        <div class="card shadow-sm"><div class="card-body">
            <h5 class="mb-3" style="color:var(--navy);"><i class="bi bi-pie-chart me-1" style="color:var(--gold);"></i>權益</h5>
            <table class="table align-middle mb-0"><tbody>
                <?php foreach ($equity as $e): ?><tr><td><?= esc($e['name']) ?></td><td class="text-end"><?= $fmt($e['amt']) ?></td></tr><?php endforeach; ?>
            </tbody><tfoot>
                <tr class="table-light fw-bold"><td>權益總計</td><td class="text-end"><?= $fmt($tE) ?></td></tr>
                <tr class="fw-bold" style="background:rgba(244,183,2,0.14);"><td>負債 + 權益</td><td class="text-end fs-5" style="color:var(--navy);"><?= $fmt($tL + $tE) ?></td></tr>
            </tfoot></table>
        </div></div>
    </div>
</div>

<?= $this->endSection() ?>
