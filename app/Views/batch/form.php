<?php
if (!function_exists('showFieldError')) { function showFieldError($f) { $e = session()->getFlashdata('errors'); return isset($e[$f]) ? '<div class="invalid-feedback d-block">' . esc($e[$f]) . '</div>' : ''; } }
if (!function_exists('getFieldClass')) { function getFieldClass($f) { $e = session()->getFlashdata('errors'); return isset($e[$f]) ? 'is-invalid' : ''; } }
?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-upc-scan me-2"></i><?= $isEdit ? '編輯' : '新增' ?>批號/序號</h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm"><div class="card-body p-4">
    <form action="<?= $isEdit ? url_to('BatchController::update', $data['b_id']) : url_to('BatchController::store') ?>" method="post" novalidate>
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">商品 <span class="text-danger">*</span></label>
                <select class="form-select <?= getFieldClass('b_p_id') ?>" name="b_p_id" required>
                    <option value="">— 請選擇 —</option>
                    <?php foreach ($products as $p): ?><option value="<?= $p['p_id'] ?>" <?= (string)($data['b_p_id'] ?? '')===(string)$p['p_id']?'selected':'' ?>><?= esc($p['p_code']) ?> <?= esc($p['p_name']) ?></option><?php endforeach; ?>
                </select><?= showFieldError('b_p_id') ?></div>
            <div class="col-md-3 mb-3"><label class="form-label">倉庫</label>
                <select class="form-select" name="b_w_id"><option value="">—</option>
                    <?php foreach ($warehouses as $w): ?><option value="<?= $w['w_id'] ?>" <?= (string)($data['b_w_id'] ?? '')===(string)$w['w_id']?'selected':'' ?>><?= esc($w['w_name']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-md-3 mb-3"><label class="form-label">數量</label><input type="number" class="form-control" name="b_qty" value="<?= (int)($data['b_qty'] ?? 0) ?>"></div>
            <div class="col-md-4 mb-3"><label class="form-label">批號</label><input type="text" class="form-control" name="b_batch_no" value="<?= esc($data['b_batch_no'] ?? '') ?>"></div>
            <div class="col-md-4 mb-3"><label class="form-label">序號</label><input type="text" class="form-control" name="b_serial" value="<?= esc($data['b_serial'] ?? '') ?>"></div>
            <div class="col-md-2 mb-3"><label class="form-label">製造日</label><input type="date" class="form-control" name="b_mfg_date" value="<?= esc($data['b_mfg_date'] ?? '') ?>"></div>
            <div class="col-md-2 mb-3"><label class="form-label">有效期限</label><input type="date" class="form-control" name="b_exp_date" value="<?= esc($data['b_exp_date'] ?? '') ?>"></div>
            <div class="col-md-12 mb-3"><label class="form-label">備註</label><input type="text" class="form-control" name="b_note" value="<?= esc($data['b_note'] ?? '') ?>"></div>
        </div>
        <div class="d-flex gap-2 justify-content-end">
            <a href="<?= url_to('BatchController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>儲存</button>
        </div>
    </form>
</div></div>

<?= $this->endSection() ?>
