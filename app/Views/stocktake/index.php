<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-clipboard-data me-2"></i>庫存盤點</h1>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>輸入實際盤點數量,系統會對差異自動產生「盤盈(入)/盤虧(出)」異動並更新在庫。</div>

<div class="card shadow-sm mb-3"><div class="card-body">
    <form method="get" class="row g-2">
        <div class="col-md-4"><select name="w" class="form-select" onchange="this.form.submit()">
            <option value="">全部倉庫</option>
            <?php foreach ($warehouses as $w): ?><option value="<?= $w['w_id'] ?>" <?= (string)($wId??'')===(string)$w['w_id']?'selected':'' ?>><?= esc($w['w_name']) ?></option><?php endforeach; ?>
        </select></div>
    </form>
</div></div>

<form action="<?= url_to('StocktakeController::save') ?>" method="post">
    <div class="card shadow-sm"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>" style="width:180px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>確認盤點調整</button>
        </div>
        <?php if (empty($rows)): ?>
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無庫存資料可盤點</p></div>
        <?php else: ?>
            <div class="table-responsive"><table class="table align-middle">
                <thead class="table-light"><tr><th>品號</th><th>品名</th><th>倉庫</th><th class="text-end">帳面在庫</th><th style="width:150px;" class="text-end">實盤數量</th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><code><?= esc($r['p_code'] ?? '') ?></code></td>
                            <td><strong><?= esc($r['p_name'] ?? '—') ?></strong></td>
                            <td><?= esc($r['w_name'] ?? '—') ?></td>
                            <td class="text-end"><?= $fmt($r['ps_qty']) ?></td>
                            <td><input type="number" class="form-control form-control-sm text-end" name="counted[<?= $r['ps_id'] ?>]" value="<?= (int)$r['ps_qty'] ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div></div>
</form>

<?= $this->endSection() ?>
