<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-hdd-stack me-2"></i>固定資產管理</h1>
    <a href="<?= url_to('FixedAssetController::create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>新增資產</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form action="<?= url_to('FixedAssetController::index') ?>" method="get" class="row g-2">
            <div class="col-md-10"><input type="text" class="form-control" name="keyword" placeholder="搜尋資產名稱 / 編號 / 類別..." value="<?= esc($keyword ?? '') ?>"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>搜尋</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($data)): ?>
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無固定資產資料</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>資產編號</th><th>資產名稱</th><th>類別</th><th>取得日期</th><th class="text-end">取得成本</th><th class="text-end">年折舊</th><th>狀態</th><th style="width:120px;" class="text-center">操作</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $item): $depr = \App\Models\FixedAssetModel::annualDepreciation($item); ?>
                            <tr>
                                <td><code><?= esc($item['fa_code']) ?></code></td>
                                <td><strong><?= esc($item['fa_name']) ?></strong></td>
                                <td><?= esc($item['fa_category'] ?: '—') ?></td>
                                <td><?= esc($item['fa_acquire_date'] ?: '—') ?></td>
                                <td class="text-end"><?= number_format((int)$item['fa_cost']) ?></td>
                                <td class="text-end text-muted"><?= number_format($depr) ?></td>
                                <td>
                                    <?php $st = $item['fa_status']; $cls = $st==='使用中'?'bg-primary':($st==='報廢'?'bg-danger':'bg-secondary'); ?>
                                    <span class="badge <?= $cls ?>"><?= esc($st) ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url_to('FixedAssetController::edit', $item['fa_id']) ?>" class="btn btn-outline-primary" title="編輯"><i class="bi bi-pencil"></i></a>
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete('<?= url_to('FixedAssetController::delete', $item['fa_id']) ?>')" title="刪除"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1"></i>年折舊採直線法 =（取得成本 − 殘值）÷ 耐用年數</p>
<?= view('components/pagination', ['pager' => $pager, 'baseUrl' => url_to('FixedAssetController::index'), 'params' => ['keyword' => $keyword ?? '']]) ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
