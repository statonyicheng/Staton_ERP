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

<h1><i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?> me-2"></i><?= $isEdit ? '編輯' : '新增' ?>廠商</h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="<?= $isEdit ? url_to('SupplierController::update', $data['s_id']) : url_to('SupplierController::store') ?>" method="post" novalidate>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">廠商編號</label>
                    <input type="text" class="form-control" name="s_code" value="<?= old('s_code', $data['s_code'] ?? '') ?>" placeholder="留空自動產生 (SUP0001)">
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">廠商名稱 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= getFieldClass('s_name') ?>" name="s_name" value="<?= old('s_name', $data['s_name'] ?? '') ?>" required maxlength="100">
                    <?= showFieldError('s_name') ?>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">統一編號</label>
                    <input type="text" class="form-control <?= getFieldClass('s_tax_id') ?>" name="s_tax_id" value="<?= old('s_tax_id', $data['s_tax_id'] ?? '') ?>" maxlength="20">
                    <?= showFieldError('s_tax_id') ?>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">聯絡人</label>
                    <input type="text" class="form-control" name="s_contact" value="<?= old('s_contact', $data['s_contact'] ?? '') ?>" maxlength="50">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">付款條件</label>
                    <select class="form-select" name="s_pm_id">
                        <option value="">— 請選擇 —</option>
                        <?php foreach (($paymentMethods ?? []) as $pm): ?>
                            <option value="<?= $pm['pm_id'] ?>" <?= (string)old('s_pm_id', $data['s_pm_id'] ?? '') === (string)$pm['pm_id'] ? 'selected' : '' ?>><?= esc($pm['pm_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">電話</label>
                    <input type="text" class="form-control" name="s_phone" value="<?= old('s_phone', $data['s_phone'] ?? '') ?>" maxlength="50">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">傳真</label>
                    <input type="text" class="form-control" name="s_fax" value="<?= old('s_fax', $data['s_fax'] ?? '') ?>" maxlength="50">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">電子郵件</label>
                    <input type="email" class="form-control <?= getFieldClass('s_email') ?>" name="s_email" value="<?= old('s_email', $data['s_email'] ?? '') ?>" maxlength="100">
                    <?= showFieldError('s_email') ?>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label">地址</label>
                    <input type="text" class="form-control" name="s_address" value="<?= old('s_address', $data['s_address'] ?? '') ?>" maxlength="255">
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label">備註</label>
                    <textarea class="form-control" name="s_note" rows="2"><?= old('s_note', $data['s_note'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= url_to('SupplierController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>儲存</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
