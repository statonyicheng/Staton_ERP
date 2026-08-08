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

<h1><i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?> me-2"></i><?= $isEdit ? '編輯' : '新增' ?>倉庫</h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="<?= $isEdit ? url_to('WarehouseController::update', $data['w_id']) : url_to('WarehouseController::store') ?>" method="post" novalidate>
        <?= \App\Libraries\EditGuard::field($data['w_updated_at'] ?? null) ?>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">倉庫代號</label>
                    <input type="text" class="form-control" name="w_code" value="<?= old('w_code', $data['w_code'] ?? '') ?>" placeholder="留空自動產生 (WH01)">
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">倉庫名稱 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= getFieldClass('w_name') ?>" name="w_name" value="<?= old('w_name', $data['w_name'] ?? '') ?>" required maxlength="100">
                    <?= showFieldError('w_name') ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">存放位置</label>
                    <input type="text" class="form-control" name="w_location" value="<?= old('w_location', $data['w_location'] ?? '') ?>" maxlength="255">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">倉管人員</label>
                    <input type="text" class="form-control" name="w_manager" value="<?= old('w_manager', $data['w_manager'] ?? '') ?>" maxlength="50">
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label">備註</label>
                    <input type="text" class="form-control" name="w_note" value="<?= old('w_note', $data['w_note'] ?? '') ?>" maxlength="255">
                </div>
                <div class="col-md-12 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="w_is_active" value="1" id="wActive" <?= old('w_is_active', $data['w_is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="wActive">啟用此倉庫</label>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= url_to('WarehouseController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>儲存</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
