<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-clipboard-check me-2"></i>採購單管理</h1>
    <a href="<?= url_to('PurchaseOrderController::create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>新增採購單</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form action="<?= url_to('PurchaseOrderController::index') ?>" method="get" class="row g-2">
            <div class="col-md-7"><input type="text" class="form-control" name="keyword" placeholder="搜尋單號 / 廠商 / 備註..." value="<?= esc($keyword ?? '') ?>"></div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">全部狀態</option>
                    <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= ($status ?? '')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>查詢</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($data)): ?>
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無採購單</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>採購單號</th><th>採購日期</th><th>廠商</th><th class="text-end">含稅總計</th><th>狀態</th><th style="width:150px;" class="text-center">操作</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $po):
                            $st = $po['po_status']; $cls = ['未結案'=>'bg-primary','部分進貨'=>'bg-info text-dark','已結案'=>'bg-success','作廢'=>'bg-secondary'][$st] ?? 'bg-secondary';
                        ?>
                            <tr>
                                <td><code><?= esc($po['po_no']) ?></code></td>
                                <td><?= esc($po['po_date']) ?></td>
                                <td><?= esc($po['s_name'] ?: '—') ?></td>
                                <td class="text-end fw-semibold"><?= $fmt($po['po_total']) ?></td>
                                <td><span class="badge <?= $cls ?>"><?= esc($st) ?></span></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url_to('PurchaseOrderController::view', $po['po_id']) ?>" class="btn btn-outline-info" title="檢視"><i class="bi bi-eye"></i></a>
                                        <a href="<?= url_to('PurchaseOrderController::edit', $po['po_id']) ?>" class="btn btn-outline-primary" title="編輯"><i class="bi bi-pencil"></i></a>
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete('<?= url_to('PurchaseOrderController::delete', $po['po_id']) ?>')" title="刪除"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
<?= view('components/pagination', ['pager' => $pager, 'baseUrl' => url_to('PurchaseOrderController::index'), 'params' => ['keyword' => $keyword ?? '', 'status' => $status ?? '']]) ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
