<?php $fmt = fn($n) => number_format((float) $n, 2); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-calculator-fill me-2"></i>成本展算 — <?= esc($prod['p_name']) ?></h1>
    <a href="<?= url_to('CostController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>返回</a>
</div>

<div class="card shadow-sm"><div class="card-body">
    <div class="table-responsive"><table class="table align-middle">
        <thead class="table-light"><tr><th>子件品號</th><th>子件品名</th><th class="text-end">用量</th><th class="text-end">子件成本</th><th class="text-end">小計</th></tr></thead>
        <tbody>
            <?php foreach ($calc['lines'] as $l): ?>
                <tr>
                    <td><code><?= esc($l['code'] ?? '') ?></code></td>
                    <td><?= esc($l['name'] ?? '—') ?></td>
                    <td class="text-end"><?= number_format((int)$l['qty']) ?></td>
                    <td class="text-end"><?= $fmt($l['unit_cost']) ?></td>
                    <td class="text-end fw-semibold"><?= $fmt($l['line']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($calc['lines'])): ?><tr><td colspan="5" class="text-center text-muted py-3">此母件無 BOM 元件</td></tr><?php endif; ?>
        </tbody>
        <tfoot><tr class="table-light fw-bold"><td colspan="4" class="text-end">BOM 展算標準成本</td><td class="text-end fs-5" style="color:var(--navy);"><?= $fmt($calc['total']) ?></td></tr></tfoot>
    </table></div>
    <div class="d-flex justify-content-end">
        <a href="<?= url_to('CostController::apply', $prod['p_id']) ?>" class="btn btn-gold" onclick="return confirm('確定回寫標準成本？')"><i class="bi bi-arrow-down-circle me-1"></i>回寫商品成本</a>
    </div>
</div></div>

<?= $this->endSection() ?>
