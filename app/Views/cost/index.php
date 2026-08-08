<?php $fmt = fn($n) => number_format((float) $n, 2); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-calculator-fill me-2"></i>成本計算</h1>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>依 BOM 展算母件標準成本＝Σ(子件標準成本 × 用量)。可「回寫」更新商品成本，供毛利與存貨計價使用。</div>

<div class="card shadow-sm"><div class="card-body">
    <?php if (empty($rows)): ?>
        <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無建立 BOM 的商品</p></div>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>品號</th><th>品名</th><th class="text-end">目前成本</th><th class="text-end">BOM 展算成本</th><th class="text-end">差異</th><th style="width:170px;" class="text-center">操作</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r): $diff = $r['calc_cost'] - $r['current_cost']; ?>
                    <tr>
                        <td><code><?= esc($r['p_code']) ?></code></td>
                        <td><strong><?= esc($r['p_name']) ?></strong></td>
                        <td class="text-end text-muted"><?= $fmt($r['current_cost']) ?></td>
                        <td class="text-end fw-semibold" style="color:var(--navy);"><?= $fmt($r['calc_cost']) ?></td>
                        <td class="text-end <?= abs($diff)>0.001?'text-danger':'text-success' ?>"><?= $fmt($diff) ?></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= url_to('CostController::view', $r['p_id']) ?>" class="btn btn-outline-info"><i class="bi bi-eye"></i> 明細</a>
                                <a href="<?= url_to('CostController::apply', $r['p_id']) ?>" class="btn btn-gold" onclick="return confirm('確定回寫標準成本？')"><i class="bi bi-arrow-down-circle"></i> 回寫</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div></div>

<?= $this->endSection() ?>
