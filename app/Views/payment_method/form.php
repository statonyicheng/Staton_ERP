<?php

/**
 * Helper function: 顯示欄位錯誤訊息
 */
function showFieldError($fieldName)
{
    $errors = session()->getFlashdata('errors');
    if (isset($errors[$fieldName])) {
        return '<div class="invalid-feedback d-block"><i class="bi bi-exclamation-circle me-1"></i>' . esc($errors[$fieldName]) . '</div>';
    }
    return '';
}

/**
 * Helper function: 檢查欄位是否有錯誤並返回 class
 */
function getFieldClass($fieldName)
{
    $errors = session()->getFlashdata('errors');
    return isset($errors[$fieldName]) ? 'is-invalid' : '';
}
?>

<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="container mt-4">
    <!-- 頁面標題 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?> me-2"></i>
            <?= $isEdit ? '編輯' : '新增' ?>結帳方式
        </h2>
    </div>

    <!-- 全域錯誤訊息 -->
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- 表單卡片 -->
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form id="paymentMethodForm" action="<?= $isEdit ? url_to('PaymentMethodController::update', $data['pm_id']) : url_to('PaymentMethodController::store') ?>" method="post" novalidate>
        <?= \App\Libraries\EditGuard::field($data['pm_updated_at'] ?? null) ?>
                <!-- 基本資訊 -->
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="bi bi-cash-coin me-2 text-primary"></i>結帳方式資訊
                    </h5>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="paymentMethodName" class="form-label">
                                結帳方式名稱 <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control <?= getFieldClass('pm_name') ?>"
                                id="paymentMethodName"
                                name="pm_name"
                                value="<?= old('pm_name', $data['pm_name'] ?? '') ?>"
                                placeholder="請輸入結帳方式名稱"
                                required
                                maxlength="100"
                                aria-describedby="paymentMethodNameError">
                            <?= showFieldError('pm_name') ?>
                            <div class="form-text">例如：現金、月結30天、支票等</div>
                        </div>
                    </div>

                    <div class="alert alert-info py-2">
                        <i class="bi bi-info-circle me-2"></i>
                        下面兩欄決定<strong>到期日怎麼算</strong> ——「帳齡與周轉分析」用它判斷逾期幾天。
                        沒設定的話會當成即期（開帳當天就到期），月結客戶會被誤判成逾期。
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="pmType" class="form-label">付款條件類型</label>
                            <select class="form-select" id="pmType" name="pm_type">
                                <?php $pmType = old('pm_type', $data['pm_type'] ?? 'immediate'); ?>
                                <?php foreach (\App\Libraries\PaymentTerms::TYPES as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= $pmType === $k ? 'selected' : '' ?>><?= esc($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="pmDays" class="form-label">天數 / 日期</label>
                            <input type="number" class="form-control" id="pmDays" name="pm_days" min="0" max="60"
                                   value="<?= old('pm_days', $data['pm_days'] ?? 0) ?>">
                            <div class="form-text">
                                「發票日起算 N 天」填天數（如 30）；「月結，次月 N 日」填日期（如 1 代表次月 1 號）；即期填 0。
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 時間戳記資訊 (僅編輯時顯示) -->
                <?php if (!empty($isEdit) && isset($data)): ?>
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="bi bi-clock-history me-2 text-primary"></i>系統資訊
                        </h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">
                                    <i class="bi bi-calendar-plus me-1"></i>建立時間
                                </label>
                                <div class="p-2 bg-light rounded">
                                    <?= esc($data['pm_created_at'] ?? '無資料') ?>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">
                                    <i class="bi bi-calendar-check me-1"></i>更新時間
                                </label>
                                <div class="p-2 bg-light rounded">
                                    <?= esc($data['pm_updated_at'] ?? '無資料') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 必填欄位說明 -->
                <div class="alert alert-info py-2 mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    標示 <span class="text-danger">*</span> 為必填欄位
                </div>

                <!-- 表單按鈕 -->
                <div class="d-flex gap-2 justify-content-end">
                    <a href="<?= url_to('PaymentMethodController::index') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i>取消
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-check-circle me-1"></i>儲存
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // 表單提交處理 - 防止重複提交
    document.getElementById('paymentMethodForm').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('submitBtn');

        // 檢查表單驗證
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('was-validated');
            return false;
        }

        // 防止重複提交
        if (submitBtn.disabled) {
            e.preventDefault();
            return false;
        }

        // 禁用提交按鈕並顯示載入狀態
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>儲存中...';
    });
</script>

<?= $this->endSection() ?>
