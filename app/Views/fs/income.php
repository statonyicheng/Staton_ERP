<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-file-earmark-bar-graph me-2"></i>損益表</h1>
    <form method="get" class="d-flex gap-2 align-items-center"><input type="number" name="year" class="form-control form-control-sm" value="<?= $year ?>" style="width:110px;" onchange="this.form.submit()"><span class="text-muted small">年度</span></form>
</div>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>Income Statement · 由複式簿記分錄自動編製(收入 − 各項費用 ＝ 本期損益)。</div>

<div class="card shadow-sm"><div class="card-body">
    <h5 class="mb-3"><?= $year ?> 年度損益表</h5>
    <table class="table align-middle">
        <tbody>
            <tr class="table-light fw-bold"><td>營業收入</td><td class="text-end"><?= $fmt($totRev) ?></td></tr>
            <?php foreach ($revenue as $r): ?><tr><td class="ps-4 text-muted"><?= esc($r['name']) ?></td><td class="text-end text-muted"><?= $fmt($r['amt']) ?></td></tr><?php endforeach; ?>

            <?php
            $tierLabel = ['一階成本'=>'營業成本','二階費用'=>'營業費用-業務','三階費用'=>'營業費用-人事','四階費用'=>'營業費用-管理'];
            foreach (['一階成本','二階費用','三階費用','四階費用'] as $tier):
                if (empty($expenseByTier[$tier])) continue;
                $sub = array_sum(array_column($expenseByTier[$tier], 'amt')); ?>
                <tr class="fw-semibold"><td><?= $tierLabel[$tier] ?? $tier ?></td><td class="text-end">(<?= $fmt($sub) ?>)</td></tr>
                <?php foreach ($expenseByTier[$tier] as $e): ?><tr><td class="ps-4 text-muted"><?= esc($e['name']) ?></td><td class="text-end text-muted">(<?= $fmt($e['amt']) ?>)</td></tr><?php endforeach; ?>
            <?php endforeach; ?>

            <tr class="fw-bold" style="background:rgba(244,183,2,0.14);"><td>本期損益</td><td class="text-end fs-5" style="color:var(--navy);"><?= $fmt($net) ?></td></tr>
        </tbody>
    </table>
    <p class="small text-muted mb-0">資料來源:分錄傳票(借貸)。淨額 = 收入 <?= $fmt($totRev) ?> − 費用 <?= $fmt($totExp) ?> = <?= $fmt($net) ?>。</p>
</div></div>

<?= $this->endSection() ?>
