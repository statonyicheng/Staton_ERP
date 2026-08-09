<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-upc-scan me-2"></i>批號 / 序號管理</h1>
    <a href="<?= url_to('BatchController::create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>新增批號/序號</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm mb-3"><div class="card-body">
    <form action="<?= url_to('BatchController::index') ?>" method="get" class="row g-2">
        <div class="col-md-10"><input type="text" class="form-control" name="keyword" placeholder="搜尋批號 / 序號 / 品名..." value="<?= esc($keyword ?? '') ?>"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>查詢</button></div>
    </form>
</div></div>

<div class="card shadow-sm"><div class="card-body">
    <?php if (empty($data)): ?>
        <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無批號/序號資料</p></div>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>品號</th><th>品名</th><th>批號</th><th>序號</th><th>倉庫</th><th class="text-end">數量</th><th>有效期限</th><th style="width:120px;" class="text-center">操作</th></tr></thead>
            <tbody>
                <?php foreach ($data as $b): ?>
                    <tr>
                        <td><code><?= esc($b['p_code'] ?? '') ?></code></td>
                        <td><?= esc($b['p_name'] ?? '—') ?></td>
                        <td><?= esc($b['b_batch_no'] ?: '—') ?></td>
                        <td><?= esc($b['b_serial'] ?: '—') ?></td>
                        <td><?= esc($b['w_name'] ?: '—') ?></td>
                        <td class="text-end"><?= $fmt($b['b_qty']) ?></td>
                        <td><?= esc($b['b_exp_date'] ?: '—') ?></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= url_to('BatchController::edit', $b['b_id']) ?>" class="btn btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <button type="button" class="btn btn-outline-danger" onclick="confirmDelete('<?= url_to('BatchController::delete', $b['b_id']) ?>')"><i class="bi bi-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
<?= view('components/pagination', ['pager' => $pager, 'baseUrl' => url_to('BatchController::index'), 'params' => ['keyword' => $keyword ?? '']]) ?>
    <?php endif; ?>
</div></div>

<?= $this->endSection() ?>
