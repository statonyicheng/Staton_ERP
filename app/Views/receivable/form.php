<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?> me-2"></i><?= $isEdit ? '編輯' : '新增' ?>應收憑單</h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="<?= $isEdit ? url_to('ReceivableController::update', $data['ar_id']) : url_to('ReceivableController::store') ?>" method="post">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">應收單號</label>
                    <input type="text" class="form-control" name="ar_no" value="<?= esc($isEdit ? ($data['ar_no'] ?? '') : ($defaultNo ?? '')) ?>" <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">客戶</label>
                    <select class="form-select" name="ar_c_id">
                        <option value="">— 請選擇 —</option>
                        <?php foreach ($customers as $c): ?><option value="<?= $c['c_id'] ?>" <?= (string)($data['ar_c_id'] ?? '')===(string)$c['c_id']?'selected':'' ?>><?= esc($c['c_code']) ?> <?= esc($c['c_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">單據日期</label>
                    <input type="date" class="form-control" name="ar_date" value="<?= old('ar_date', $data['ar_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">到期日</label>
                    <input type="date" class="form-control" name="ar_due_date" value="<?= old('ar_due_date', $data['ar_due_date'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">應收金額(含稅) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="ar_amount" value="<?= old('ar_amount', $data['ar_amount'] ?? 0) ?>" required>
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">備註</label>
                    <input type="text" class="form-control" name="ar_note" value="<?= old('ar_note', $data['ar_note'] ?? '') ?>" maxlength="255">
                </div>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= url_to('ReceivableController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>儲存</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
