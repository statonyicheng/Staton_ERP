<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-search me-2"></i>庫存查詢</h1>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-md-6"><div class="card shadow-sm"><div class="card-body d-flex justify-content-between align-items-center">
        <span class="text-muted"><i class="bi bi-boxes me-1" style="color:var(--gold);"></i>有庫存品項數</span>
        <strong class="fs-4" style="color:var(--navy);"><?= $fmt($summary['sku'] ?? 0) ?></strong>
    </div></div></div>
    <div class="col-md-6"><div class="card shadow-sm"><div class="card-body d-flex justify-content-between align-items-center">
        <span class="text-muted"><i class="bi bi-stack me-1" style="color:var(--gold);"></i>總在庫數量</span>
        <strong class="fs-4" style="color:var(--navy);"><?= $fmt($summary['total_qty'] ?? 0) ?></strong>
    </div></div></div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form action="<?= url_to('InventoryController::index') ?>" method="get" class="row g-2 align-items-center">
            <div class="col-md-5"><input type="text" class="form-control" name="keyword" placeholder="搜尋品號 / 品名..." value="<?= esc($keyword ?? '') ?>"></div>
            <div class="col-md-3">
                <select name="w" class="form-select">
                    <option value="">全部倉庫</option>
                    <?php foreach ($warehouses as $w): ?><option value="<?= $w['w_id'] ?>" <?= (string)($wId ?? '')===(string)$w['w_id']?'selected':'' ?>><?= esc($w['w_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="hidezero" value="1" id="hz" <?= $hideZero ? 'checked' : '' ?>>
                    <label class="form-check-label" for="hz">隱藏零庫存</label>
                </div>
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>查詢</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($data)): ?>
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無庫存資料</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>品號</th><th>品名</th><th>規格</th><th>倉庫</th><th class="text-end">在庫量</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $s): ?>
                            <tr>
                                <td><code><?= esc($s['p_code'] ?? '') ?></code></td>
                                <td><strong><?= esc($s['p_name'] ?? '—') ?></strong></td>
                                <td><small class="text-muted"><?= esc($s['p_specifications'] ?: '—') ?></small></td>
                                <td><?= esc($s['w_name'] ?? '—') ?></td>
                                <td class="text-end fw-semibold <?= (int)$s['ps_qty'] < 0 ? 'text-danger' : '' ?>"><?= $fmt($s['ps_qty']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
<?= view('components/pagination', ['pager' => $pager, 'baseUrl' => url_to('InventoryController::index'), 'params' => ['keyword' => $keyword ?? '', 'w' => $wId ?? '']]) ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
