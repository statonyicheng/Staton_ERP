<?php $fmt = fn($n) => number_format((float) $n, 2); $fmt0 = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-calculator me-2"></i>存貨計價 / 結轉</h1>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>存貨金額＝在庫量 × 標準成本(可於「成本計算」以 BOM 展算回寫)。供月結存貨評價與結轉。</div>

<div class="row g-3 mb-3">
    <div class="col-md-6"><div class="card shadow-sm"><div class="card-body d-flex justify-content-between align-items-center">
        <span class="text-muted"><i class="bi bi-cash-stack me-1" style="color:var(--gold);"></i>存貨總價值</span>
        <strong class="fs-4" style="color:var(--navy);"><?= $fmt($totalValue) ?></strong>
    </div></div></div>
    <div class="col-md-6"><div class="card shadow-sm"><div class="card-body">
        <form method="get" class="d-flex gap-2 align-items-center">
            <label class="text-muted mb-0" style="white-space:nowrap;">倉庫</label>
            <select name="w" class="form-select" onchange="this.form.submit()">
                <option value="">全部倉庫</option>
                <?php foreach ($warehouses as $w): ?><option value="<?= $w['w_id'] ?>" <?= (string)($wId??'')===(string)$w['w_id']?'selected':'' ?>><?= esc($w['w_name']) ?></option><?php endforeach; ?>
            </select>
        </form>
    </div></div></div>
</div>

<div class="card shadow-sm"><div class="card-body">
    <?php if (empty($rows)): ?>
        <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無存貨</p></div>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-hover align-middle text-end">
            <thead class="table-light"><tr><th class="text-start">品號</th><th class="text-start">品名</th><th class="text-start">倉庫</th><th>在庫量</th><th>標準成本</th><th>存貨金額</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-start"><code><?= esc($r['p_code'] ?? '') ?></code></td>
                        <td class="text-start"><strong><?= esc($r['p_name'] ?? '—') ?></strong></td>
                        <td class="text-start"><?= esc($r['w_name'] ?? '—') ?></td>
                        <td><?= $fmt0($r['ps_qty']) ?></td>
                        <td class="text-muted"><?= $fmt($r['p_cost_price']) ?></td>
                        <td class="fw-semibold"><?= $fmt($r['value']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot><tr class="table-light fw-bold"><td colspan="5" class="text-end">存貨總價值</td><td style="color:var(--navy);"><?= $fmt($totalValue) ?></td></tr></tfoot>
        </table></div>
    <?php endif; ?>
</div></div>

<?= $this->endSection() ?>
