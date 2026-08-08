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
// 依損益歸屬分組科目
$grouped = [];
foreach ($accounts as $a) { $grouped[$a['ac_tier']][] = $a; }
$curAc = old('t_ac_id', $data['t_ac_id'] ?? '');
?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?> me-2"></i><?= $isEdit ? '編輯' : '新增' ?>交易</h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="<?= $isEdit ? url_to('TransactionController::update', $data['t_id']) : url_to('TransactionController::store') ?>" method="post" novalidate>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">交易日期 <span class="text-danger">*</span></label>
                    <input type="date" class="form-control <?= getFieldClass('t_date') ?>" name="t_date" value="<?= old('t_date', $data['t_date'] ?? date('Y-m-d')) ?>" required>
                    <?= showFieldError('t_date') ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">摘要 / 品名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= getFieldClass('t_summary') ?>" name="t_summary" value="<?= old('t_summary', $data['t_summary'] ?? '') ?>" required maxlength="255" placeholder="如：洞石訂單、進貨、運費...">
                    <?= showFieldError('t_summary') ?>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">對象（客戶/廠商）</label>
                    <input type="text" class="form-control" name="t_partner" value="<?= old('t_partner', $data['t_partner'] ?? '') ?>" maxlength="100">
                </div>

                <div class="col-md-5 mb-3">
                    <label class="form-label">會計科目 <span class="text-danger">*</span></label>
                    <select class="form-select <?= getFieldClass('t_ac_id') ?>" name="t_ac_id" required>
                        <option value="">— 請選擇 —</option>
                        <?php foreach ($grouped as $tier => $list): ?>
                            <optgroup label="<?= esc($tier) ?>">
                                <?php foreach ($list as $a): ?>
                                    <option value="<?= $a['ac_id'] ?>" <?= (string)$curAc === (string)$a['ac_id'] ? 'selected' : '' ?>>
                                        <?= esc($a['ac_code']) ?> <?= esc($a['ac_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <?= showFieldError('t_ac_id') ?>
                    <div class="form-text">收入類自動記「收」、支出類自動記「付」；非損益類請自行選收付方向。</div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">業務別</label>
                    <select class="form-select" name="t_segment">
                        <?php foreach ($segments as $k => $v): ?>
                            <option value="<?= $k ?>" <?= old('t_segment', $data['t_segment'] ?? 'M-1') === $k ? 'selected' : '' ?>><?= $k ?> <?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">收付方向</label>
                    <select class="form-select" name="t_direction">
                        <option value="收" <?= old('t_direction', $data['t_direction'] ?? '收') === '收' ? 'selected' : '' ?>>收（流入）</option>
                        <option value="付" <?= old('t_direction', $data['t_direction'] ?? '收') === '付' ? 'selected' : '' ?>>付（流出）</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">未稅金額</label>
                    <input type="number" class="form-control" name="t_amount" value="<?= old('t_amount', $data['t_amount'] ?? 0) ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">營業稅</label>
                    <input type="number" class="form-control" name="t_tax" value="<?= old('t_tax', $data['t_tax'] ?? 0) ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">收付狀態</label>
                    <select class="form-select" name="t_settle_status">
                        <option value="已收付" <?= old('t_settle_status', $data['t_settle_status'] ?? '已收付') === '已收付' ? 'selected' : '' ?>>已收付</option>
                        <option value="未收付" <?= old('t_settle_status', $data['t_settle_status'] ?? '已收付') === '未收付' ? 'selected' : '' ?>>未收付</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">收付日期</label>
                    <input type="date" class="form-control" name="t_settle_date" value="<?= old('t_settle_date', $data['t_settle_date'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">備註</label>
                    <input type="text" class="form-control" name="t_note" value="<?= old('t_note', $data['t_note'] ?? '') ?>" maxlength="255">
                </div>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= url_to('TransactionController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>儲存</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
