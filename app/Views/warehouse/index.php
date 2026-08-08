<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-house-gear me-2"></i>倉庫資料管理</h1>
    <a href="<?= url_to('WarehouseController::create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>新增倉庫</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form action="<?= url_to('WarehouseController::index') ?>" method="get" class="row g-2">
            <div class="col-md-10"><input type="text" class="form-control" name="keyword" placeholder="搜尋倉庫名稱 / 代號 / 倉管..." value="<?= esc($keyword ?? '') ?>"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>搜尋</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($data)): ?>
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無倉庫資料</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>倉庫代號</th><th>倉庫名稱</th><th>存放位置</th><th>倉管人員</th><th>狀態</th><th style="width:120px;" class="text-center">操作</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $item): ?>
                            <tr>
                                <td><code><?= esc($item['w_code']) ?></code></td>
                                <td><strong><?= esc($item['w_name']) ?></strong></td>
                                <td><?= esc($item['w_location'] ?: '—') ?></td>
                                <td><?= esc($item['w_manager'] ?: '—') ?></td>
                                <td><?= $item['w_is_active'] ? '<span class="badge bg-primary">啟用</span>' : '<span class="badge bg-secondary">停用</span>' ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url_to('WarehouseController::edit', $item['w_id']) ?>" class="btn btn-outline-primary" title="編輯"><i class="bi bi-pencil"></i></a>
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete('<?= url_to('WarehouseController::delete', $item['w_id']) ?>')" title="刪除"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pager['totalPages'] > 1): ?>
                <?= view('components/pagination', ['pager' => $pager, 'baseUrl' => url_to('WarehouseController::index'), 'params' => ['keyword' => $keyword ?? '']]) ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
