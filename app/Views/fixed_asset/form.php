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
$statuses = ['使用中', '閒置', '已處分', '報廢'];
$methods = ['直線法', '定率遞減法', '年數合計法'];
?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?> me-2"></i><?= $isEdit ? '編輯' : '新增' ?>固定資產</h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="<?= $isEdit ? url_to('FixedAssetController::update', $data['fa_id']) : url_to('FixedAssetController::store') ?>" method="post" novalidate>
        <?= \App\Libraries\EditGuard::field($data['fa_updated_at'] ?? null) ?>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">資產編號</label>
                    <input type="text" class="form-control" name="fa_code" value="<?= old('fa_code', $data['fa_code'] ?? '') ?>" placeholder="留空自動產生 (FA0001)">
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">資產名稱 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= getFieldClass('fa_name') ?>" name="fa_name" value="<?= old('fa_name', $data['fa_name'] ?? '') ?>" required maxlength="100">
                    <?= showFieldError('fa_name') ?>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">資產類別</label>
                    <input type="text" class="form-control" name="fa_category" value="<?= old('fa_category', $data['fa_category'] ?? '') ?>" placeholder="如：機器設備、辦公設備" maxlength="50">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">取得日期</label>
                    <input type="date" class="form-control" name="fa_acquire_date" value="<?= old('fa_acquire_date', $data['fa_acquire_date'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">取得成本</label>
                    <input type="number" min="0" class="form-control" name="fa_cost" value="<?= old('fa_cost', $data['fa_cost'] ?? 0) ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">耐用年數</label>
                    <input type="number" min="0" class="form-control" name="fa_useful_life" value="<?= old('fa_useful_life', $data['fa_useful_life'] ?? 0) ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">殘值</label>
                    <input type="number" min="0" class="form-control" name="fa_salvage" value="<?= old('fa_salvage', $data['fa_salvage'] ?? 0) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">折舊方法</label>
                    <select class="form-select" name="fa_depr_method">
                        <?php foreach ($methods as $m): ?>
                            <option value="<?= $m ?>" <?= old('fa_depr_method', $data['fa_depr_method'] ?? '直線法') === $m ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">存放地點</label>
                    <input type="text" class="form-control" name="fa_location" value="<?= old('fa_location', $data['fa_location'] ?? '') ?>" maxlength="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">狀態</label>
                    <select class="form-select" name="fa_status">
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= old('fa_status', $data['fa_status'] ?? '使用中') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label">備註</label>
                    <input type="text" class="form-control" name="fa_note" value="<?= old('fa_note', $data['fa_note'] ?? '') ?>" maxlength="255">
                </div>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= url_to('FixedAssetController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>儲存</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
