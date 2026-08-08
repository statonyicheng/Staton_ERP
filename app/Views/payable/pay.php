<?php $fmt = fn($n) => number_format((int) $n); $remain = (int)$ap['ap_amount'] - (int)$ap['ap_paid']; ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-cash me-2"></i>付款 — <?= esc($ap['ap_no']) ?></h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-2"><small class="text-muted">廠商</small><div class="fw-semibold"><?= esc($ap['s_name'] ?: '—') ?></div></div>
                    <div class="col-6 mb-2"><small class="text-muted">應付金額</small><div><?= $fmt($ap['ap_amount']) ?></div></div>
                    <div class="col-6"><small class="text-muted">已付</small><div class="text-success"><?= $fmt($ap['ap_paid']) ?></div></div>
                    <div class="col-6"><small class="text-muted">未付餘額</small><div class="fw-bold text-danger"><?= $fmt($remain) ?></div></div>
                </div>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="<?= url_to('PayableController::doPay', $ap['ap_id']) ?>" method="post">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">付款金額 <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" min="1" max="<?= $remain ?>" value="<?= $remain ?>" required>
                            <div class="form-text">最多可付 <?= $fmt($remain) ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">付款日期</label>
                            <input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">付款方式</label>
                            <select class="form-select" name="method"><?php foreach ($methods as $m): ?><option value="<?= $m ?>"><?= $m ?></option><?php endforeach; ?></select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">備註</label>
                            <input type="text" class="form-control" name="note" maxlength="255">
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="<?= url_to('PayableController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>確認付款</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
