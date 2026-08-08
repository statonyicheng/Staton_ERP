<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-list-check me-2"></i>批次需求計劃 MRP</h1>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>輸入目標母件與生產數量，系統展開 BOM 一階、扣除現有庫存（全倉合計），計算各元件短缺並提出建議。</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form action="<?= url_to('MrpController::index') ?>" method="get" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label">目標母件</label>
                <select name="p" class="form-select">
                    <option value="">— 請選擇 —</option>
                    <?php foreach ($products as $p): ?><option value="<?= $p['p_id'] ?>" <?= (string)$pId===(string)$p['p_id']?'selected':'' ?>><?= esc($p['p_code']) ?> <?= esc($p['p_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">生產數量</label>
                <input type="number" min="1" class="form-control" name="qty" value="<?= $qty ?: 1 ?>">
            </div>
            <div class="col-md-3"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-calculator me-1"></i>需求試算</button></div>
        </form>
    </div>
</div>

<?php if ($parent && $qty > 0): ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">母件：<?= esc($parent['p_name']) ?> ×<?= $fmt($qty) ?> 的物料需求</h5>
            <?php if (empty($rows)): ?>
                <div class="alert alert-warning py-2"><i class="bi bi-exclamation-triangle me-1"></i>此母件尚未建立 BOM。請先至「產品結構 BOM」建立用料結構。</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light"><tr><th>子件品號</th><th>子件品名</th><th class="text-end">單位用量</th><th class="text-end">需求量</th><th class="text-end">現有庫存</th><th class="text-end">短缺</th><th>建議</th></tr></thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><code><?= esc($r['child_code'] ?? '') ?></code></td>
                                    <td><?= esc($r['child_name'] ?? '—') ?></td>
                                    <td class="text-end"><?= $fmt($r['unit_qty']) ?> <?= esc($r['unit'] ?? '') ?></td>
                                    <td class="text-end fw-semibold"><?= $fmt($r['required']) ?></td>
                                    <td class="text-end"><?= $fmt($r['onhand']) ?></td>
                                    <td class="text-end <?= $r['short']>0?'text-danger fw-bold':'text-success' ?>"><?= $r['short']>0 ? $fmt($r['short']) : '足夠' ?></td>
                                    <td><?= $r['short']>0 ? '<span class="badge bg-warning text-dark">建議請購 '.$fmt($r['short']).'</span>' : '<span class="badge bg-success">庫存充足</span>' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
