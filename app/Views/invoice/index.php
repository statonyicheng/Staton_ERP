<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-receipt-cutoff me-2"></i>電子發票管理</h1>
    <a href="<?= url_to('InvoiceController::create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>開立發票</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm mb-3"><div class="card-body">
    <form action="<?= url_to('InvoiceController::index') ?>" method="get" class="row g-2">
        <div class="col-md-7"><input type="text" class="form-control" name="keyword" placeholder="搜尋發票號碼 / 買方..." value="<?= esc($keyword ?? '') ?>"></div>
        <div class="col-md-3"><select name="status" class="form-select"><option value="">全部狀態</option><option value="已開立" <?= ($status??'')==='已開立'?'selected':'' ?>>已開立</option><option value="作廢" <?= ($status??'')==='作廢'?'selected':'' ?>>作廢</option></select></div>
        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>查詢</button></div>
    </form>
</div></div>

<div class="card shadow-sm"><div class="card-body">
    <?php if (empty($data)): ?>
        <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無發票</p></div>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>發票號碼</th><th>日期</th><th>買方</th><th class="text-end">未稅</th><th class="text-end">稅額</th><th class="text-end">含稅</th><th>狀態</th><th style="width:150px;" class="text-center">操作</th></tr></thead>
            <tbody>
                <?php foreach ($data as $inv): $void = $inv['inv_status']==='作廢'; ?>
                    <tr class="<?= $void?'text-muted':'' ?>">
                        <td><code><?= esc($inv['inv_number']) ?></code></td>
                        <td><small><?= esc($inv['inv_date']) ?></small></td>
                        <td><?= esc($inv['c_name'] ?: $inv['inv_buyer'] ?: '—') ?></td>
                        <td class="text-end"><?= $fmt($inv['inv_amount']) ?></td>
                        <td class="text-end"><?= $fmt($inv['inv_tax']) ?></td>
                        <td class="text-end fw-semibold"><?= $fmt($inv['inv_total']) ?></td>
                        <td><?= $void?'<span class="badge bg-secondary">作廢</span>':'<span class="badge bg-success">已開立</span>' ?></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <?php if (!$void): ?>
                                    <a href="<?= url_to('InvoiceController::edit', $inv['inv_id']) ?>" class="btn btn-outline-primary" title="編輯"><i class="bi bi-pencil"></i></a>
                                    <a href="<?= url_to('InvoiceController::void', $inv['inv_id']) ?>" class="btn btn-outline-warning" title="作廢" onclick="return confirm('確定作廢此發票？')"><i class="bi bi-x-octagon"></i></a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-outline-danger" onclick="confirmDelete('<?= url_to('InvoiceController::delete', $inv['inv_id']) ?>')" title="刪除"><i class="bi bi-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
<?= view('components/pagination', ['pager' => $pager, 'baseUrl' => url_to('InvoiceController::index'), 'params' => ['keyword' => $keyword ?? '', 'status' => $status ?? '']]) ?>
    <?php endif; ?>
</div></div>

<?= $this->endSection() ?>
