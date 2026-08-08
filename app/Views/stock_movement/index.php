<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-arrow-left-right me-2"></i>日常異動處理</h1>
    <a href="<?= url_to('StockMovementController::create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>新增異動</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form action="<?= url_to('StockMovementController::index') ?>" method="get" class="row g-2">
            <div class="col-md-5"><input type="text" class="form-control" name="keyword" placeholder="搜尋品名 / 品號 / 來源單號..." value="<?= esc($keyword ?? '') ?>"></div>
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">全部類別</option>
                    <?php foreach ($types as $t): ?><option value="<?= $t ?>" <?= ($type ?? '')===$t?'selected':'' ?>><?= $t ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="w" class="form-select">
                    <option value="">全部倉庫</option>
                    <?php foreach ($warehouses as $w): ?><option value="<?= $w['w_id'] ?>" <?= (string)($wId ?? '')===(string)$w['w_id']?'selected':'' ?>><?= esc($w['w_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>查詢</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($data)): ?>
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無異動紀錄</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>日期</th><th>類別</th><th class="text-center">入/出</th><th>品號</th><th>品名</th><th>倉庫</th><th class="text-end">數量</th><th>來源</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $m): ?>
                            <tr>
                                <td><small><?= esc($m['sm_date']) ?></small></td>
                                <td><?= esc($m['sm_type']) ?></td>
                                <td class="text-center"><?= $m['sm_direction']==='入' ? '<span class="badge bg-primary">入</span>' : '<span class="badge bg-secondary">出</span>' ?></td>
                                <td><code><?= esc($m['p_code'] ?? '') ?></code></td>
                                <td><?= esc($m['p_name'] ?? '—') ?></td>
                                <td><?= esc($m['w_name'] ?? '—') ?></td>
                                <td class="text-end fw-semibold <?= $m['sm_direction']==='出'?'text-danger':'text-success' ?>"><?= $m['sm_direction']==='出'?'-':'+' ?><?= $fmt($m['sm_qty']) ?></td>
                                <td><small class="text-muted"><?= esc($m['sm_ref_no'] ?: '—') ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pager['totalPages'] > 1): ?>
                <?= view('components/pagination', ['pager' => $pager, 'baseUrl' => url_to('StockMovementController::index'), 'params' => ['keyword' => $keyword ?? '', 'type' => $type ?? '', 'w' => $wId ?? '']]) ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
