<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-journal-bookmark me-2"></i>會計總帳</h1>
    <form method="get" class="d-flex gap-2">
        <select name="ym" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:130px;">
            <option value="">全部月份</option>
            <?php foreach ($months as $mo): ?>
                <option value="<?= $mo ?>" <?= $ym === $mo ? 'selected' : '' ?>><?= $mo ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>依會計科目彙總各期收（流入）付（流出）金額（含稅）。</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($rows)): ?>
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">此期間尚無交易</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle text-end">
                    <thead class="table-light">
                        <tr><th class="text-start">科目代號</th><th class="text-start">科目名稱</th><th class="text-start">損益歸屬</th><th>收(流入)</th><th>付(流出)</th><th>淨額</th><th class="text-center">筆數</th></tr>
                    </thead>
                    <tbody>
                        <?php $ti = 0; $to = 0; foreach ($rows as $r): $ti += $r['debit_in']; $to += $r['credit_out']; ?>
                            <tr>
                                <td class="text-start"><code><?= esc($r['ac_code'] ?? '') ?></code></td>
                                <td class="text-start"><strong><?= esc($r['ac_name'] ?? '—') ?></strong></td>
                                <td class="text-start"><span class="badge bg-light text-dark border"><?= esc($r['ac_tier'] ?? '') ?></span></td>
                                <td class="text-success"><?= $fmt($r['debit_in']) ?></td>
                                <td class="text-danger"><?= $fmt($r['credit_out']) ?></td>
                                <td class="fw-semibold"><?= $fmt((int)$r['debit_in'] - (int)$r['credit_out']) ?></td>
                                <td class="text-center text-muted"><?= (int) $r['cnt'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td class="text-start" colspan="3">合計</td>
                            <td class="text-success"><?= $fmt($ti) ?></td>
                            <td class="text-danger"><?= $fmt($to) ?></td>
                            <td><?= $fmt($ti - $to) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
