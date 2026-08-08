<?php
if (!function_exists('showFieldError')) {
    function showFieldError($fieldName)
    {
        $errors = session()->getFlashdata('errors');
        if (isset($errors[$fieldName])) {
            return '<div class="invalid-feedback d-block"><i class="bi bi-exclamation-circle me-1"></i>' . esc($errors[$fieldName]) . '</div>';
        }
        return '';
    }
}
if (!function_exists('getFieldClass')) {
    function getFieldClass($fieldName)
    {
        $errors = session()->getFlashdata('errors');
        return isset($errors[$fieldName]) ? 'is-invalid' : '';
    }
}
?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?> me-2"></i><?= $isEdit ? '編輯' : '新增' ?>請購單</h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="<?= $isEdit ? url_to('PurchaseRequisitionController::update', $data['pr_id']) : url_to('PurchaseRequisitionController::store') ?>" method="post" novalidate>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">請購單號</label>
                    <input type="text" class="form-control" name="pr_no" value="<?= esc($isEdit ? ($data['pr_no'] ?? '') : ($defaultNo ?? '')) ?>" <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">請購日期</label>
                    <input type="date" class="form-control" name="pr_date" value="<?= old('pr_date', $data['pr_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">請購單位</label>
                    <input type="text" class="form-control" name="pr_dept" value="<?= old('pr_dept', $data['pr_dept'] ?? '') ?>" maxlength="50">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">狀態</label>
                    <select class="form-select" name="pr_status">
                        <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= old('pr_status', $data['pr_status'] ?? '待處理')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">品名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= getFieldClass('pr_name') ?>" name="pr_name" value="<?= old('pr_name', $data['pr_name'] ?? '') ?>" required maxlength="150">
                    <?= showFieldError('pr_name') ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">規格</label>
                    <input type="text" class="form-control" name="pr_spec" value="<?= old('pr_spec', $data['pr_spec'] ?? '') ?>" maxlength="150">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">數量</label>
                    <input type="number" class="form-control" name="pr_qty" value="<?= old('pr_qty', $data['pr_qty'] ?? 0) ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">單位</label>
                    <input type="text" class="form-control" name="pr_unit" value="<?= old('pr_unit', $data['pr_unit'] ?? '') ?>" maxlength="20">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">需求日</label>
                    <input type="date" class="form-control" name="pr_need_date" value="<?= old('pr_need_date', $data['pr_need_date'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">備註</label>
                    <input type="text" class="form-control" name="pr_note" value="<?= old('pr_note', $data['pr_note'] ?? '') ?>" maxlength="255">
                </div>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= url_to('PurchaseRequisitionController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>儲存</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
