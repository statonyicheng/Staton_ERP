<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-box-arrow-in-down me-2"></i>進貨 / 退貨管理</h1>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>選擇待進貨的採購單辦理收貨入庫；進貨後將自動增加對應倉庫在庫量並結案採購單。</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h5 class="mb-3">待進貨採購單</h5>
        <?php if (empty($pending)): ?>
            <div class="text-center py-5 text-muted"><i class="bi bi-check2-all" style="font-size:3rem;"></i><p class="mt-3">目前沒有待進貨的採購單</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>採購單號</th><th>採購日期</th><th>廠商</th><th class="text-end">含稅總計</th><th style="width:120px;" class="text-center">操作</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $po): ?>
                            <tr>
                                <td><code><?= esc($po['po_no']) ?></code></td>
                                <td><?= esc($po['po_date']) ?></td>
                                <td><?= esc($po['s_name'] ?: '—') ?></td>
                                <td class="text-end"><?= $fmt($po['po_total']) ?></td>
                                <td class="text-center">
                                    <a href="<?= url_to('GoodsReceiptController::receive', $po['po_id']) ?>" class="btn btn-sm btn-gold"><i class="bi bi-box-arrow-in-down me-1"></i>進貨</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
