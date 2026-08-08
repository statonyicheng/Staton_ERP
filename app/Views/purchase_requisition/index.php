<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-clipboard-plus me-2"></i>請購作業</h1>
    <a href="<?= url_to('PurchaseRequisitionController::create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>新增請購</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form action="<?= url_to('PurchaseRequisitionController::index') ?>" method="get" class="row g-2">
            <div class="col-md-7"><input type="text" class="form-control" name="keyword" placeholder="搜尋品名 / 單號 / 單位..." value="<?= esc($keyword ?? '') ?>"></div>
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
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無請購單</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>請購單號</th><th>日期</th><th>單位</th><th>品名</th><th class="text-end">數量</th><th>需求日</th><th>狀態</th><th style="width:120px;" class="text-center">操作</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $pr):
                            $cls = ['待處理'=>'bg-primary','已轉採購'=>'bg-success','已取消'=>'bg-secondary'][$pr['pr_status']] ?? 'bg-secondary';
                        ?>
                            <tr>
                                <td><code><?= esc($pr['pr_no']) ?></code></td>
                                <td><?= esc($pr['pr_date']) ?></td>
                                <td><?= esc($pr['pr_dept'] ?: '—') ?></td>
                                <td><strong><?= esc($pr['pr_name']) ?></strong><?php if ($pr['pr_spec']): ?> <small class="text-muted"><?= esc($pr['pr_spec']) ?></small><?php endif; ?></td>
                                <td class="text-end"><?= $fmt($pr['pr_qty']) ?> <?= esc($pr['pr_unit'] ?? '') ?></td>
                                <td><?= esc($pr['pr_need_date'] ?: '—') ?></td>
                                <td><span class="badge <?= $cls ?>"><?= esc($pr['pr_status']) ?></span></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url_to('PurchaseRequisitionController::edit', $pr['pr_id']) ?>" class="btn btn-outline-primary" title="編輯"><i class="bi bi-pencil"></i></a>
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete('<?= url_to('PurchaseRequisitionController::delete', $pr['pr_id']) ?>')" title="刪除"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pager['totalPages'] > 1): ?>
                <?= view('components/pagination', ['pager' => $pager, 'baseUrl' => url_to('PurchaseRequisitionController::index'), 'params' => ['keyword' => $keyword ?? '', 'status' => $status ?? '']]) ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
