<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-bar-chart me-2"></i>採購報表</h1>
    <form method="get" class="d-flex gap-2">
        <select name="ym" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:130px;">
            <option value="">全部月份</option>
            <?php foreach ($months as $mo): ?><option value="<?= $mo ?>" <?= $ym===$mo?'selected':'' ?>><?= $mo ?></option><?php endforeach; ?>
        </select>
    </form>
</div>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>依廠商彙總採購金額（不含作廢單）。</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($rows)): ?>
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">此期間尚無採購資料</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle text-end">
                    <thead class="table-light">
                        <tr><th class="text-start">廠商</th><th class="text-center">採購單數</th><th>未稅小計</th><th>營業稅</th><th>含稅總計</th></tr>
                    </thead>
                    <tbody>
                        <?php $tc=0;$ts=0;$tt=0;$tg=0; foreach ($rows as $r): $tc+=$r['cnt'];$ts+=$r['subtotal'];$tt+=$r['tax'];$tg+=$r['total']; ?>
                            <tr>
                                <td class="text-start"><strong><?= esc($r['s_name'] ?: '(未指定廠商)') ?></strong></td>
                                <td class="text-center"><?= (int)$r['cnt'] ?></td>
                                <td><?= $fmt($r['subtotal']) ?></td>
                                <td class="text-muted"><?= $fmt($r['tax']) ?></td>
                                <td class="fw-semibold"><?= $fmt($r['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td class="text-start">合計</td>
                            <td class="text-center"><?= $tc ?></td>
                            <td><?= $fmt($ts) ?></td>
                            <td><?= $fmt($tt) ?></td>
                            <td style="color:var(--navy);"><?= $fmt($tg) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
