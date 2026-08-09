<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-truck me-2"></i>廠商資料管理</h1>
    <a href="<?= url_to('SupplierController::create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>新增廠商
    </a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form action="<?= url_to('SupplierController::index') ?>" method="get" class="row g-2">
            <div class="col-md-10">
                <input type="text" class="form-control" name="keyword" placeholder="搜尋廠商名稱 / 編號 / 聯絡人 / 統編..." value="<?= esc($keyword ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>搜尋</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($data)): ?>
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無廠商資料</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>廠商編號</th><th>廠商名稱</th><th>統一編號</th><th>聯絡人</th><th>電話</th>
                            <th style="width:120px;" class="text-center">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $item): ?>
                            <tr>
                                <td><code><?= esc($item['s_code']) ?></code></td>
                                <td><strong><?= esc($item['s_name']) ?></strong></td>
                                <td><?= esc($item['s_tax_id'] ?: '—') ?></td>
                                <td><?= esc($item['s_contact'] ?: '—') ?></td>
                                <td><?= esc($item['s_phone'] ?: '—') ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url_to('SupplierController::edit', $item['s_id']) ?>" class="btn btn-outline-primary" title="編輯"><i class="bi bi-pencil"></i></a>
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete('<?= url_to('SupplierController::delete', $item['s_id']) ?>')" title="刪除"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
<?= view('components/pagination', ['pager' => $pager, 'baseUrl' => url_to('SupplierController::index'), 'params' => ['keyword' => $keyword ?? '']]) ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
