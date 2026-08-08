<?php $fmt = fn($n) => number_format((int) round((float)$n)); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-bar-chart me-2"></i>銷售統計 / 報表</h1>
    <form method="get" class="d-flex gap-2">
        <select name="ym" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:130px;">
            <option value="">全部月份</option>
            <?php foreach ($months as $mo): ?><option value="<?= $mo ?>" <?= $ym===$mo?'selected':'' ?>><?= $mo ?></option><?php endforeach; ?>
        </select>
    </form>
</div>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>依客戶彙整訂單金額（不含取消單）。</div>

<div class="card shadow-sm"><div class="card-body">
    <?php if (empty($rows)): ?>
        <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">此期間尚無銷售資料</p></div>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-hover align-middle text-end">
            <thead class="table-light"><tr><th class="text-start">客戶</th><th class="text-center">訂單數</th><th>銷售金額</th></tr></thead>
            <tbody>
                <?php $tc=0;$tt=0; foreach ($rows as $r): $tc+=$r['cnt'];$tt+=$r['total']; ?>
                    <tr><td class="text-start"><strong><?= esc($r['c_name'] ?: '(未指定客戶)') ?></strong></td><td class="text-center"><?= (int)$r['cnt'] ?></td><td class="fw-semibold"><?= $fmt($r['total']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot><tr class="table-light fw-bold"><td class="text-start">合計</td><td class="text-center"><?= $tc ?></td><td style="color:var(--navy);"><?= $fmt($tt) ?></td></tr></tfoot>
        </table></div>
    <?php endif; ?>
</div></div>

<?= $this->endSection() ?>
