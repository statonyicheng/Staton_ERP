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

<h1><i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?> me-2"></i><?= $isEdit ? '編輯' : '新增' ?>會計科目</h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="<?= $isEdit ? url_to('AccountController::update', $data['ac_id']) : url_to('AccountController::store') ?>" method="post" novalidate>
        <?= \App\Libraries\EditGuard::field($data['ac_updated_at'] ?? null) ?>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">科目代號</label>
                    <input type="text" class="form-control" name="ac_code" value="<?= old('ac_code', $data['ac_code'] ?? '') ?>" maxlength="20" placeholder="如 4101">
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">科目名稱 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= getFieldClass('ac_name') ?>" name="ac_name" value="<?= old('ac_name', $data['ac_name'] ?? '') ?>" required maxlength="50" placeholder="如 營-銷貨收入">
                    <?= showFieldError('ac_name') ?>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">類別 <span class="text-danger">*</span></label>
                    <select class="form-select <?= getFieldClass('ac_category') ?>" name="ac_category" required>
                        <option value="">— 請選擇 —</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c ?>" <?= old('ac_category', $data['ac_category'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= showFieldError('ac_category') ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">損益歸屬（四階模型）<span class="text-danger">*</span></label>
                    <select class="form-select <?= getFieldClass('ac_tier') ?>" name="ac_tier" required>
                        <option value="">— 請選擇 —</option>
                        <?php foreach ($tiers as $t): ?>
                            <option value="<?= $t ?>" <?= old('ac_tier', $data['ac_tier'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= showFieldError('ac_tier') ?>
                    <div class="form-text">選「不進損益」者僅計資金餘額，不影響各階毛利。</div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">排序</label>
                    <input type="number" class="form-control" name="ac_sort" value="<?= old('ac_sort', $data['ac_sort'] ?? 0) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" for="acArAp">
                        應收付歸屬
                        <i class="bi bi-info-circle text-muted"
                           title="設定後，這個科目未沖銷的分錄會出現在應收帳款管理或應付帳款管理"></i>
                    </label>
                    <select class="form-select" name="ac_ar_ap" id="acArAp">
                        <?php $arAp = old('ac_ar_ap', $data['ac_ar_ap'] ?? ''); ?>
                        <option value="" <?= $arAp === '' ? 'selected' : '' ?>>不列入</option>
                        <option value="AR" <?= $arAp === 'AR' ? 'selected' : '' ?>>應收</option>
                        <option value="AP" <?= $arAp === 'AP' ? 'selected' : '' ?>>應付</option>
                    </select>
                    <div class="form-text">設為應收/應付時請一併勾選下方「需立沖帳」，否則算不出未沖銷餘額</div>
                </div>
                <div class="col-md-12 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ac_open_item" value="1" id="acOpenItem" <?= old('ac_open_item', $data['ac_open_item'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="acOpenItem">
                            <strong>需立沖帳</strong>（開放項目)— 勾選後,此科目的分錄需逐筆立帳/沖銷,並納入「立沖帳餘額表」追蹤未沖銷餘額(常用於應收/應付/暫收暫付)。
                        </label>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= url_to('AccountController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>儲存</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
