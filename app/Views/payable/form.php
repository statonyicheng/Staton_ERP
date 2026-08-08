<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?> me-2"></i><?= $isEdit ? '編輯' : '新增' ?>應付憑單</h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="<?= $isEdit ? url_to('PayableController::update', $data['ap_id']) : url_to('PayableController::store') ?>" method="post">
        <?= \App\Libraries\EditGuard::field($data['ap_updated_at'] ?? null) ?>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">應付單號</label>
                    <input type="text" class="form-control" name="ap_no" value="<?= esc($isEdit ? ($data['ap_no'] ?? '') : ($defaultNo ?? '')) ?>" <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">廠商</label>
                    <select class="form-select" name="ap_s_id">
                        <option value="">— 請選擇 —</option>
                        <?php foreach ($suppliers as $s): ?><option value="<?= $s['s_id'] ?>" <?= (string)($data['ap_s_id'] ?? '')===(string)$s['s_id']?'selected':'' ?>><?= esc($s['s_code']) ?> <?= esc($s['s_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">單據日期</label>
                    <input type="date" class="form-control" name="ap_date" value="<?= old('ap_date', $data['ap_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">到期日</label>
                    <input type="date" class="form-control" name="ap_due_date" value="<?= old('ap_due_date', $data['ap_due_date'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">應付金額(含稅) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="ap_amount" value="<?= old('ap_amount', $data['ap_amount'] ?? 0) ?>" required>
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">備註</label>
                    <input type="text" class="form-control" name="ap_note" value="<?= old('ap_note', $data['ap_note'] ?? '') ?>" maxlength="255">
                </div>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= url_to('PayableController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>儲存</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
