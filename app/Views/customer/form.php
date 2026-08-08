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

$contacts = $contacts ?? [];
if (old('contacts') !== null) {
    $contacts = old('contacts');
}
if (empty($contacts)) {
    $contacts = [['cc_id' => '', 'cc_name' => '', 'cc_phone' => '', 'cc_email' => '']];
}
?>

<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="container mt-4">
    <!-- 頁面標題 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?> me-2"></i>
            <?= $isEdit ? '編輯' : '新增' ?>客戶資料
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
            <form id="customerForm" action="<?= url_to('CustomerController::save') ?>" method="post" novalidate>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="c_id" value="<?= $data['c_id'] ?? old('c_id') ?>">
                <?php endif; ?>

                <!-- 基本資料區塊 -->
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="bi bi-building me-2 text-primary"></i>基本資料
                    </h5>

                    <!-- 客戶編號 (僅編輯時顯示) -->
                    <?php if (!empty($isEdit) && isset($data['c_code'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label text-muted">
                                    <i class="bi bi-hash me-1"></i>客戶編號
                                </label>
                                <div class="p-2 bg-light rounded">
                                    <strong class="text-primary"><?= esc($data['c_code']) ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="customerName" class="form-label">
                                公司行號名稱
                            </label>
                            <input
                                type="text"
                                class="form-control <?= getFieldClass('c_name') ?>"
                                id="customerName"
                                name="c_name"
                                value="<?= old('c_name', $data['c_name'] ?? '') ?>"
                                placeholder="請輸入公司行號名稱"
                                aria-describedby="customerNameError">
                            <?= showFieldError('c_name') ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="manager" class="form-label">
                                負責人
                            </label>
                            <input
                                type="text"
                                class="form-control <?= getFieldClass('c_manager') ?>"
                                id="manager"
                                name="c_manager"
                                value="<?= old('c_manager', $data['c_manager'] ?? '') ?>"
                                placeholder="請輸入負責人姓名"
                                aria-describedby="managerError">
                            <?= showFieldError('c_manager') ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="taxId" class="form-label">統一編號</label>
                            <input
                                type="text"
                                class="form-control <?= getFieldClass('c_tax_id') ?>"
                                id="taxId"
                                name="c_tax_id"
                                value="<?= old('c_tax_id', $data['c_tax_id'] ?? '') ?>"
                                placeholder="8 位數字"
                                maxlength="8"
                                pattern="[0-9]{8}"
                                aria-describedby="taxIdError">
                            <?= showFieldError('c_tax_id') ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="paymentMethod" class="form-label">結帳方式</label>
                            <select
                                class="form-select <?= getFieldClass('c_pm_id') ?>"
                                id="paymentMethod"
                                name="c_pm_id"
                                aria-describedby="paymentMethodError">
                                <option value="">請選擇結帳方式</option>
                                <?php foreach ($paymentMethods as $pm): ?>
                                    <option value="<?= $pm['pm_id'] ?>" <?= old('c_pm_id', $data['c_pm_id'] ?? '') == $pm['pm_id'] ? 'selected' : '' ?>>
                                        <?= esc($pm['pm_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= showFieldError('c_pm_id') ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="phone" class="form-label">
                                聯絡電話
                            </label>
                            <input
                                type="tel"
                                class="form-control <?= getFieldClass('c_phone') ?>"
                                id="phone"
                                name="c_phone"
                                value="<?= old('c_phone', $data['c_phone'] ?? '') ?>"
                                placeholder="例如：02-12345678 或 0912-345678"
                                aria-describedby="phoneError">
                            <?= showFieldError('c_phone') ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="fax" class="form-label">傳真號碼</label>
                            <input
                                type="tel"
                                class="form-control <?= getFieldClass('c_fax') ?>"
                                id="fax"
                                name="c_fax"
                                value="<?= old('c_fax', $data['c_fax'] ?? '') ?>"
                                placeholder="例如：02-12345678"
                                aria-describedby="faxError">
                            <?= showFieldError('c_fax') ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input
                                type="email"
                                class="form-control <?= getFieldClass('c_email') ?>"
                                id="email"
                                name="c_email"
                                value="<?= old('c_email', $data['c_email'] ?? '') ?>"
                                placeholder="example@company.com"
                                aria-describedby="emailError">
                            <?= showFieldError('c_email') ?>
                        </div>
                        <!-- <div class="col-md-3 mb-3">
                            <label for="contactPerson" class="form-label">
                                主要聯絡人
                            </label>
                            <input
                                type="text"
                                class="form-control <?= getFieldClass('c_contact_person') ?>"
                                id="contactPerson"
                                name="c_contact_person"
                                value="<?= old('c_contact_person', $data['c_contact_person'] ?? '') ?>"
                                placeholder="請輸入聯絡人姓名"
                                aria-describedby="contactPersonError">
                            <?= showFieldError('c_contact_person') ?>
                        </div> -->
                    </div>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label for="city" class="form-label">縣市</label>
                            <select
                                class="form-select <?= getFieldClass('c_city') ?>"
                                id="city"
                                name="c_city"
                                aria-describedby="cityError">
                                <option value="">請選擇縣市</option>
                                <?php
                                $cities = [
                                    '台北市',
                                    '新北市',
                                    '桃園市',
                                    '台中市',
                                    '台南市',
                                    '高雄市',
                                    '基隆市',
                                    '新竹市',
                                    '嘉義市',
                                    '新竹縣',
                                    '苗栗縣',
                                    '彰化縣',
                                    '南投縣',
                                    '雲林縣',
                                    '嘉義縣',
                                    '屏東縣',
                                    '宜蘭縣',
                                    '花蓮縣',
                                    '台東縣',
                                    '澎湖縣',
                                    '金門縣',
                                    '連江縣'
                                ];
                                foreach ($cities as $city):
                                ?>
                                    <option value="<?= $city ?>" <?= old('c_city', $data['c_city'] ?? '') == $city ? 'selected' : '' ?>>
                                        <?= $city ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= showFieldError('c_city') ?>
                        </div>
                        <div class="col-md-10 mb-3">
                            <label for="address" class="form-label">詳細地址</label>
                            <input
                                type="text"
                                class="form-control <?= getFieldClass('c_address') ?>"
                                id="address"
                                name="c_address"
                                value="<?= old('c_address', $data['c_address'] ?? '') ?>"
                                placeholder="請輸入詳細地址"
                                aria-describedby="addressError">
                            <?= showFieldError('c_address') ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="notes" class="form-label">備註</label>
                            <textarea class="form-control <?= getFieldClass('c_notes') ?>" id="notes" name="c_notes" rows="3"><?= old('c_notes', $data['c_notes'] ?? '') ?></textarea>
                            <?= showFieldError('c_notes') ?>
                        </div>
                    </div>
                </div>

                <!-- 聯絡人區塊 -->
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="bi bi-person-lines-fill me-2 text-primary"></i>聯絡人
                    </h5>

                    <div id="contactContainer">
                        <?php foreach ($contacts as $index => $contact): ?>
                            <div class="contact-item card mb-3" data-index="<?= $index ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">
                                            <i class="bi bi-person-badge me-1 text-primary"></i>
                                            聯絡人 #<span class="contact-number"><?= $index + 1 ?></span>
                                        </h6>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-contact">
                                            <i class="bi bi-trash me-1"></i>刪除
                                        </button>
                                    </div>

                                    <input type="hidden" name="contacts[<?= $index ?>][cc_id]" value="<?= esc($contact['cc_id'] ?? '') ?>">

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">姓名</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                name="contacts[<?= $index ?>][cc_name]"
                                                value="<?= esc($contact['cc_name'] ?? '') ?>"
                                                placeholder="聯絡人姓名">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">手機</label>
                                            <input
                                                type="tel"
                                                class="form-control"
                                                name="contacts[<?= $index ?>][cc_phone]"
                                                value="<?= esc($contact['cc_phone'] ?? '') ?>"
                                                placeholder="0912-345678">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Email</label>
                                            <input
                                                type="email"
                                                class="form-control"
                                                name="contacts[<?= $index ?>][cc_email]"
                                                value="<?= esc($contact['cc_email'] ?? '') ?>"
                                                placeholder="example@company.com">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" class="btn btn-success" id="addContactBtn">
                        <i class="bi bi-plus-circle me-1"></i>新增聯絡人
                    </button>

                    <input type="hidden" id="deletedContactIds" name="deleted_contact_ids" value="">

                    <!-- 聯絡人模板（供 JavaScript 使用） -->
                    <template id="contactTemplate">
                        <div class="contact-item card mb-3" data-index="__INDEX__">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">
                                        <i class="bi bi-person-badge me-1 text-primary"></i>
                                        聯絡人 #<span class="contact-number">__NUM__</span>
                                    </h6>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-contact">
                                        <i class="bi bi-trash me-1"></i>刪除
                                    </button>
                                </div>

                                <input type="hidden" name="contacts[__INDEX__][cc_id]" value="">

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">姓名</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            name="contacts[__INDEX__][cc_name]"
                                            placeholder="聯絡人姓名">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">手機</label>
                                        <input
                                            type="tel"
                                            class="form-control"
                                            name="contacts[__INDEX__][cc_phone]"
                                            placeholder="0912-345678">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Email</label>
                                        <input
                                            type="email"
                                            class="form-control"
                                            name="contacts[__INDEX__][cc_email]"
                                            placeholder="example@company.com">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
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
                                    <i class="bi bi-calendar-plus me-1"></i>新增時間
                                </label>
                                <div class="p-2 bg-light rounded">
                                    <?= esc($data['c_created_at'] ?? '無資料') ?>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted">
                                    <i class="bi bi-calendar-check me-1"></i>更新時間
                                </label>
                                <div class="p-2 bg-light rounded">
                                    <?= esc($data['c_updated_at'] ?? '無資料') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 表單按鈕 -->
                <div class="d-flex gap-2 justify-content-end">
                    <a href="<?= url_to('CustomerController::index') ?>" class="btn btn-outline-secondary">
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
    // 聯絡人管理
    let contactIndex = <?= count($contacts) ?>;
    let deletedContactIds = [];

    document.getElementById('addContactBtn')?.addEventListener('click', function() {
        const container = document.getElementById('contactContainer');
        const template = document.getElementById('contactTemplate');
        const newIndex = contactIndex++;

        const clone = template.content.cloneNode(true);
        const wrapper = document.createElement('div');
        wrapper.appendChild(clone);
        let html = wrapper.innerHTML;
        html = html.replace(/__INDEX__/g, newIndex).replace(/__NUM__/g, newIndex + 1);

        container.insertAdjacentHTML('beforeend', html);
        updateContactNumbers();
    });

    document.getElementById('contactContainer')?.addEventListener('click', function(e) {
        if (e.target.closest('.remove-contact')) {
            const item = e.target.closest('.contact-item');
            const contactId = item.querySelector('input[name*="[cc_id]"]').value;
            if (contactId) {
                deletedContactIds.push(contactId);
            }
            item.remove();
            updateContactNumbers();
        }
    });

    function updateContactNumbers() {
        document.querySelectorAll('.contact-item .contact-number').forEach((el, idx) => {
            el.textContent = idx + 1;
        });
    }

    // 表單提交處理 - 防止重複提交
    document.getElementById('customerForm').addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('submitBtn');

        // 更新刪除聯絡人 ID
        document.getElementById('deletedContactIds').value = deletedContactIds.join(',');

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

    // 統一編號格式驗證
    document.getElementById('taxId')?.addEventListener('input', function(e) {
        // 只允許數字輸入
        this.value = this.value.replace(/[^\d]/g, '');
    });

    // 電話號碼格式提示
    document.getElementById('phone')?.addEventListener('blur', function(e) {
        const value = this.value.trim();
        if (value && !value.match(/^[0-9\-\+\(\)\s]+$/)) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });
</script>

<?= $this->endSection() ?>