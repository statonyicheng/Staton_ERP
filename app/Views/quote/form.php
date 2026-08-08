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

$productCategories = $productCategories ?? [];
?>

<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<style>
    /* 商品項目表格優化 */
    #itemsTable thead th {
        background-color: #f8f9fa;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        white-space: nowrap;
        padding: 0.75rem 0.5rem;
    }

    #itemsTable tbody td {
        padding: 0.5rem;
        vertical-align: middle;
    }

    .item-row {
        transition: background-color 0.2s;
    }

    .item-row:hover {
        background-color: #f8f9fa;
    }

    /* 圖片預覽優化 */
    .item-image-preview {
        transition: transform 0.2s;
    }

    .item-image-preview:hover {
        transform: scale(1.05);
    }

    /* 表單控制項優化 */
    .form-control-sm,
    .form-select-sm {
        font-size: 0.875rem;
    }

    .small {
        font-size: 0.875rem;
    }

    /* 金額顯示強調 */
    .amount-display {
        color: #0d6efd;
    }

    /* 必填欄位標示 */
    .form-label .text-danger {
        margin-left: 2px;
    }

    /* 按鈕優化 */
    .remove-item {
        transition: all 0.2s;
    }

    .remove-item:hover {
        transform: scale(1.1);
    }

    /* 表格固定表頭 */
    .table-responsive {
        max-height: 600px;
        overflow-y: auto;
    }

    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    /* 圖片選擇區樣式 */
    .image-selector-container {
        padding: 0.5rem;
        background-color: #f8f9fa;
        border-radius: 0.25rem;
        margin-top: 0.5rem;
    }

    .image-grid {
        max-height: 200px;
        overflow-y: auto;
    }

    .image-item {
        padding: 0.25rem;
        border-radius: 0.25rem;
        transition: background-color 0.2s;
    }

    .image-item:hover {
        background-color: #e9ecef;
    }

    .image-item input[type="radio"]:checked + div {
        border: 2px solid #0d6efd !important;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
</style>

<div class="container-fluid mt-4">
    <!-- 頁面標題 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?> me-2"></i>
            <?= $isEdit ? '編輯' : '新增' ?>報價單
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
            <form id="quoteForm" action="<?= url_to('QuoteController::save') ?>" method="post" novalidate>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="q_id" value="<?= $data['q_id'] ?? old('q_id') ?>">
                <?php endif; ?>

                <!-- 基本資料區塊 -->
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="bi bi-file-earmark-text me-2 text-primary"></i>基本資料
                    </h5>
                    <div class="row">
                        <!-- 報價單號 -->
                        <div class="col-md-4 mb-3">
                            <label for="quoteNumber" class="form-label">報價單號</label>
                            <input
                                type="text"
                                class="form-control <?= getFieldClass('q_number') ?>"
                                id="quoteNumber"
                                name="q_number"
                                value="<?= old('q_number', $data['q_number'] ?? $quoteNumber ?? '') ?>"
                                required
                                readonly>
                            <?= showFieldError('q_number') ?>
                        </div>
                        <!-- 報價日期 -->
                        <div class="col-md-4 mb-3">
                            <label for="quoteDate" class="form-label">
                                報價日期 <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input
                                    type="date"
                                    class="form-control <?= getFieldClass('q_date') ?>"
                                    id="quoteDate"
                                    name="q_date"
                                    value="<?= old('q_date', $data['q_date'] ?? date('Y-m-d')) ?>"
                                    required>
                            </div>
                            <?= showFieldError('q_date') ?>
                        </div>
                        <!-- 有效日期 -->
                        <div class="col-md-4 mb-3">
                            <label for="validDate" class="form-label">有效日期</label>
                            <div class="input-group">
                                <input
                                    type="date"
                                    class="form-control <?= getFieldClass('q_valid_date') ?>"
                                    id="validDate"
                                    name="q_valid_date"
                                    value="<?php
                                            $validDate = old('q_valid_date', $data['q_valid_date'] ?? '');
                                            if (empty($validDate) && !$isEdit) {
                                                // 新增模式且沒有有效日期時，預設為報價日期+15天
                                                $quoteDate = old('q_date', $data['q_date'] ?? date('Y-m-d'));
                                                $quoteDateObj = new DateTime($quoteDate);
                                                $quoteDateObj->modify('+15 days');
                                                $validDate = $quoteDateObj->format('Y-m-d');
                                            }
                                            echo $validDate;
                                            ?>">
                            </div>
                            <?= showFieldError('q_valid_date') ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="customer" class="form-label">
                                客戶 <span class="text-danger">*</span>
                            </label>
                            <select
                                class="form-select <?= getFieldClass('q_c_id') ?>"
                                id="customer"
                                name="q_c_id"
                                required>
                                <option value=""></option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?= $customer['c_id'] ?>"
                                        <?= (old('q_c_id', $data['q_c_id'] ?? '') == $customer['c_id']) ? 'selected' : '' ?>>
                                        <?= esc($customer['c_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= showFieldError('q_c_id') ?>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="contactSelect" class="form-label">
                                聯絡人
                            </label>
                            <select
                                class="form-select <?= getFieldClass('q_cc_id') ?>"
                                id="contactSelect"
                                name="q_cc_id"
                                data-endpoint="<?= base_url('customer/contacts') ?>"
                                data-initial-customer="<?= esc($data['q_c_id'] ?? '') ?>"
                                data-selected-id="<?= esc(old('q_cc_id', $data['q_cc_id'] ?? '')) ?>">
                                <option value="">請先選擇客戶</option>
                            </select>
                            <?= showFieldError('q_cc_id') ?>
                            <div class="form-text" id="contactInfo" data-placeholder="電話 / Email"></div>
                        </div>
                    <div class="col-md-4 mb-3">
                        <label for="vendor" class="form-label">
                            供應商
                        </label>
                        <select
                            class="form-select <?= getFieldClass('q_vendor') ?>"
                            id="vendor"
                            name="q_vendor">
                            <option value="">請選擇供應商</option>
                            <?php 
                                $vendors = ['文興W', '巨鋒G'];
                                $selectedVendor = old('q_vendor', $data['q_vendor'] ?? '');
                            ?>
                            <?php foreach ($vendors as $vendor): ?>
                                <option value="<?= esc($vendor) ?>" <?= $selectedVendor === $vendor ? 'selected' : '' ?>>
                                    <?= esc($vendor) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= showFieldError('q_vendor') ?>
                    </div>
                    </div>
                    <!-- 送貨地址區塊 -->
                    <div class="row">
                        <div class="col-12">
                            <?= view('components/delivery_address_selector', [
                                'deliveryCity' => $data['q_delivery_city'] ?? old('q_delivery_city'),
                                'deliveryAddress' => $data['q_delivery_address'] ?? old('q_delivery_address'),
                                'prefix' => 'q'
                            ]) ?>
                        </div>
                    </div>

                </div>

                <!-- 商品項目區塊 -->
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="bi bi-cart me-2 text-primary"></i>商品項目 <span class="text-danger">*</span>
                    </h5>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="itemsTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 10%;" class="text-center">
                                        <i class="bi bi-image me-1"></i>圖片
                                    </th>
                                    <th style="width: 30%;">
                                        <i class="bi bi-box-seam me-1"></i>商品 / 顏色花色
                                    </th>
                                    <th style="width: 10%;" class="text-center">
                                        <i class="bi bi-123 me-1"></i>尺寸
                                    </th>
                                    <th style="width: 10%;" class="text-center">
                                        <i class="bi bi-123 me-1"></i>數量
                                    </th>
                                    <th style="width: 10%;" class="text-center">
                                        <i class="bi bi-currency-dollar me-1"></i>單價
                                    </th>
                                    <th style="width: 10%;" class="text-center">
                                        <i class="bi bi-percent me-1"></i>折扣%
                                    </th>
                                    <th style="width: 10%;" class="text-center">
                                        <i class="bi bi-calculator me-1"></i>金額
                                    </th>
                                    <th style="width: 10%;" class="text-center">
                                        <i class="bi bi-gear"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="itemsContainer">
                                <?php
                                $items = $data['items'] ?? [[]];
                                if (empty($items)) $items = [[]];
                                foreach ($items as $index => $item):
                                    echo view('components/quote_item_row', [
                                        'index' => $index,
                                        'item' => $item,
                                        'products' => $products,
                                        'productCategories' => $productCategories,
                                        'isTemplate' => false
                                    ]);
                                endforeach;
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-success btn-sm" id="addItemBtn">
                        <i class="bi bi-plus-circle me-1"></i>新增商品項目
                    </button>
                </div>

                <!-- 商品項目模板（供 JavaScript 使用） -->
                <template id="itemRowTemplate">
                    <?= view('components/quote_item_row', [
                        'index' => '__INDEX__',
                        'item' => [],
                        'products' => $products,
                        'productCategories' => $productCategories,
                        'isTemplate' => true
                    ]) ?>
                </template>

                <!-- 金額計算區塊 -->
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="bi bi-calculator me-2 text-primary"></i>金額計算
                    </h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="notes" class="form-label">備註</label>
                            <textarea
                                class="form-control"
                                id="notes"
                                name="q_notes"
                                rows="4"
                                placeholder="請輸入備註"><?= old('q_notes', $data['q_notes'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td class="text-end fw-bold">小計：</td>
                                    <td class="text-end">
                                        <span id="subtotalDisplay">NT$ <?= number_format(old('q_subtotal', $data['q_subtotal'] ?? 0)) ?></span>
                                        <input type="hidden" name="q_subtotal" id="subtotalInput" value="<?= old('q_subtotal', $data['q_subtotal'] ?? 0) ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end">
                                        <label for="discount" class="form-label mb-0">整單折扣(%)：</label>
                                    </td>
                                    <td class="text-end">
                                        <input type="number" class="form-control form-control-sm text-end"
                                            id="discount" name="q_discount"
                                            value="<?= old('q_discount', $data['q_discount'] ?? 0) ?>"
                                            min="0" max="100" step="0.01">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end">
                                        <label for="taxRate" class="form-label mb-0">稅率(%)：</label>
                                    </td>
                                    <td class="text-end">
                                        <input type="number" class="form-control form-control-sm text-end"
                                            id="taxRate" name="q_tax_rate"
                                            value="<?= old('q_tax_rate', $data['q_tax_rate'] ?? 5) ?>"
                                            min="0" max="100" step="0.01">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end">
                                        <label for="shippingFee" class="form-label mb-0">運費：</label>
                                    </td>
                                    <td class="text-end">
                                        <input type="number" class="form-control form-control-sm text-end"
                                            id="shippingFee" name="q_shipping_fee"
                                            value="<?= old('q_shipping_fee', $data['q_shipping_fee'] ?? 0) ?>"
                                            min="0" step="1">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end fw-bold">稅額：</td>
                                    <td class="text-end">
                                        <span id="taxDisplay">NT$ <?= number_format(old('q_tax_amount', $data['q_tax_amount'] ?? 0)) ?></span>
                                        <input type="hidden" name="q_tax_amount" id="taxInput" value="<?= old('q_tax_amount', $data['q_tax_amount'] ?? 0) ?>">
                                    </td>
                                </tr>
                                <tr class="table-primary">
                                    <td class="text-end fw-bold fs-5 align-middle">總金額：</td>
                                    <td class="text-end">
                                        <div class="input-group input-group-lg justify-content-end">
                                            <span class="input-group-text bg-transparent border-0 fw-bold fs-5 text-primary">NT$</span>
                                            <input type="number" 
                                                   class="form-control form-control-lg text-end fw-bold fs-5 text-primary border-0 bg-transparent p-0" 
                                                   name="q_total_amount" 
                                                   id="totalInput" 
                                                   value="<?= old('q_total_amount', $data['q_total_amount'] ?? 0) ?>" 
                                                   style="max-width: 200px;">
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 必填欄位說明 -->
                <div class="alert alert-info py-2 mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    標示 <span class="text-danger">*</span> 為必填欄位
                </div>

                <!-- 表單按鈕 -->
                <div class="d-flex gap-2 justify-content-end">
                    <a href="<?= url_to('QuoteController::index') ?>" class="btn btn-outline-secondary">
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
    document.addEventListener('DOMContentLoaded', function() {
        // --- Configuration ---
        const TOM_SELECT_COMMON_CONFIG = {
            plugins: ['clear_button'],
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            },
            dropdownParent: 'body' // Fix for dropdown being clipped in table
        };

        // --- State ---
        let itemIndex = <?= count($items) ?>;
        const products = <?= json_encode($products) ?>;
        const productImagesCache = {}; // 快取已載入的圖片資料

        // --- Initialization ---
        const customerSelectInstance = initCustomerSelect();
        initProductTomSelect(document.querySelectorAll('.product-select'));
        document.querySelectorAll('.item-row').forEach(row => {
            const categorySelect = row.querySelector('.category-select');
            if (categorySelect && categorySelect.value) {
                applyCategoryFilter(row, true);
            }
            // 初始化圖片選擇器
            const productId = row.dataset.productId;
            const selectedImageId = row.dataset.imageId;
            if (productId && selectedImageId) {
                showImageSelector(row, productId, selectedImageId);
            }
        });
        initCalculations();
        updateRemoveButtons();
        // 送貨地址管理器已在 component 中自動初始化
        initContacts(customerSelectInstance);
        initValidDateCalculation();

        // --- Event Listeners ---
        bindEvents();

        // --- Functions ---

        function initCustomerSelect() {
            const customerSelect = document.getElementById('customer');
            if (customerSelect) {
                return new TomSelect(customerSelect, {
                    ...TOM_SELECT_COMMON_CONFIG,
                    placeholder: '請選擇客戶'
                });
            }
            return null;
        }

        function initContacts(customerSelectInstance) {
            const contactSelect = document.getElementById('contactSelect');
            const contactInfo = document.getElementById('contactInfo');
            const endpoint = contactSelect ? contactSelect.dataset.endpoint : '';
            const initialCustomerId = contactSelect ? contactSelect.dataset.initialCustomer : '';
            const initialContactId = contactSelect ? contactSelect.dataset.selectedId : '';

            if (!contactSelect || !customerSelectInstance || !endpoint) {
                return;
            }

            // 追蹤當前的聯絡人 Tom Select 實例
            let contactTomSelect = null;
            // 緩存當前聯絡人資料，用於顯示資訊
            let currentContactsData = [];

            // 定義重建聯絡人選單的函數
            const rebuildContactSelect = (contacts, selectedId = '') => {
                // 1. 如果有舊實例，先銷毀
                if (contactTomSelect) {
                    contactTomSelect.destroy();
                    contactTomSelect = null;
                }

                // 2. 清空並重建原生 Options
                contactSelect.innerHTML = '';

                // 加入預設選項
                if (!contacts || contacts.length === 0) {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = contacts === null ? '請先選擇客戶' : '無聯絡人資料';
                    contactSelect.appendChild(opt);
                    currentContactsData = [];
                } else {
                    currentContactsData = contacts;
                    contacts.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.cc_id;
                        const label = c.cc_phone ? `${c.cc_name} (${c.cc_phone})` : c.cc_name;
                        opt.textContent = label;
                        if (String(c.cc_id) === String(selectedId)) {
                            opt.selected = true;
                        }
                        contactSelect.appendChild(opt);
                    });
                }

                // 3. 重新初始化 Tom Select
                contactTomSelect = new TomSelect(contactSelect, {
                    ...TOM_SELECT_COMMON_CONFIG,
                    placeholder: '請選擇聯絡人'
                });

                // 4. 綁定變更事件
                contactTomSelect.on('change', function(value) {
                    const selected = currentContactsData.find(c => String(c.cc_id) === String(value));
                    if (selected) {
                        contactInfo.textContent = `${selected.cc_email || ''}`.trim();
                    } else {
                        contactInfo.textContent = contactInfo.dataset.placeholder || '';
                    }
                });

                // 5. 觸發一次更新以顯示初始值的資訊
                if (contactTomSelect.getValue()) {
                    const initialVal = contactTomSelect.getValue();
                    const selected = currentContactsData.find(c => String(c.cc_id) === String(initialVal));
                    if (selected) {
                        contactInfo.textContent = `${selected.cc_email || ''}`.trim();
                    }
                } else {
                    contactInfo.textContent = contactInfo.dataset.placeholder || '';
                }
            };

            // 載入資料函數
            const loadAndBuild = (customerId, selectedId = '') => {
                if (!customerId) {
                    rebuildContactSelect(null); // null 代表"請先選擇客戶"
                    return;
                }

                fetch(`${endpoint}/${customerId}`)
                    .then(resp => resp.json())
                    .then(result => {
                        if (!result.success || !result.data || result.data.length === 0) {
                            rebuildContactSelect([], ''); // 空陣列代表"無聯絡人"
                            contactInfo.textContent = '無聯絡人資料';
                        } else {
                            // 自動選擇邏輯：有指定ID用指定ID，否則選第一個
                            let targetId = selectedId;
                            if (!targetId || !result.data.some(c => String(c.cc_id) === String(targetId))) {
                                targetId = result.data[0].cc_id;
                            }
                            rebuildContactSelect(result.data, targetId);
                        }
                    })
                    .catch(() => {
                        rebuildContactSelect([], '');
                        contactInfo.textContent = '載入聯絡人失敗';
                    });
            };

            // 監聽客戶選擇變更
            customerSelectInstance.on('change', function(value) {
                loadAndBuild(value);
            });

            // 初始化時如果有預設客戶，執行一次載入
            if (initialCustomerId) {
                loadAndBuild(initialCustomerId, initialContactId);
            } else {
                // 沒有客戶時，初始化一個空的選單
                rebuildContactSelect(null);
            }
        }

        function initValidDateCalculation() {
            const quoteDateInput = document.getElementById('quoteDate');
            const validDateInput = document.getElementById('validDate');

            if (!quoteDateInput || !validDateInput) {
                return;
            }

            // 當報價日期變更時，自動計算有效日期為報價日期+15天
            quoteDateInput.addEventListener('change', function() {
                const quoteDate = new Date(this.value);
                if (!isNaN(quoteDate.getTime())) {
                    // 加上15天
                    quoteDate.setDate(quoteDate.getDate() + 15);
                    // 格式化為YYYY-MM-DD
                    const validDateStr = quoteDate.toISOString().split('T')[0];
                    validDateInput.value = validDateStr;
                }
            });
        }

        function initProductTomSelect(elements) {
            elements.forEach(function(element) {
                if (element.tomselect) return; // Skip if already initialized

                new TomSelect(element, {
                    ...TOM_SELECT_COMMON_CONFIG,
                    placeholder: '請選擇商品',
                    onInitialize: function() {
                        // Ensure dropdown content has a reasonable max height
                        this.dropdown_content.style.maxHeight = '250px';
                    },
                    onChange: function(value) {
                        // 當商品選擇變更時，顯示圖片選擇區並更新價格
                        const row = element.closest('.item-row');
                        if (!row) return;

                        if (value) {
                            const selectedProduct = products.find(p => p.p_id == value);
                            if (selectedProduct) {
                                row.querySelector('.price-input').value = selectedProduct.p_standard_price || 0;
                                // 更新尺寸選單
                                updateSizeSelect(row, selectedProduct.p_size);
                            } else {
                                row.querySelector('.price-input').value = 0;
                                updateSizeSelect(row, '');
                            }
                            
                            // 顯示圖片選擇區
                            showImageSelector(row, value);
                        } else {
                            row.querySelector('.price-input').value = 0;
                            updateSizeSelect(row, '');
                            hideImageSelector(row);
                        }

                        calculateItemAmount(row);
                    }
                });
            });
        }

        function initCalculations() {
            // 初始化時只計算商品項目金額，不重新計算總金額
            // 這樣可以保留編輯模式下用戶手動調整的總金額
            document.querySelectorAll('.item-row').forEach(row => {
                calculateItemAmount(row, false);
            });
        }

        /**
         * 更新尺寸選單
         */
        function updateSizeSelect(row, sizeString) {
            const sizeSelect = row.querySelector('.size-select');
            if (!sizeSelect) return;

            const currentValue = sizeSelect.value;
            
            // 清空選單
            sizeSelect.innerHTML = '<option value="">請選擇尺寸</option>';
            
            if (!sizeString || sizeString.trim() === '') {
                return;
            }
            
            // 分割尺寸字串（用中文頓號"、"分隔）
            const sizes = sizeString.split('、').map(s => s.trim()).filter(s => s !== '');
            
            if (sizes.length === 0) {
                return;
            }
            
            // 添加尺寸選項
            sizes.forEach(size => {
                const option = document.createElement('option');
                option.value = size;
                option.textContent = size;
                // 如果之前選擇的值還存在，保持選中
                if (size === currentValue) {
                    option.selected = true;
                }
                sizeSelect.appendChild(option);
            });
            
            // 如果只有一個尺寸，自動選中
            if (sizes.length === 1) {
                sizeSelect.value = sizes[0];
            }
        }

        function bindEvents() {
            // Add Item Button
            document.getElementById('addItemBtn').addEventListener('click', handleAddItem);

            // Items Container (Delegated events for Remove, Change, and Input)
            const itemsContainer = document.getElementById('itemsContainer');
            itemsContainer.addEventListener('click', handleRemoveItem);
            itemsContainer.addEventListener('change', handleItemChange);
            itemsContainer.addEventListener('input', handleItemInput);

            // Global Discount, Tax, and Shipping Fee
            document.getElementById('discount').addEventListener('input', calculateTotal);
            document.getElementById('taxRate').addEventListener('input', calculateTotal);
            document.getElementById('shippingFee').addEventListener('input', calculateTotal);

            // Form Submit
            document.getElementById('quoteForm').addEventListener('submit', handleFormSubmit);
        }

        function handleAddItem() {
            const container = document.getElementById('itemsContainer');
            const newIndex = itemIndex++;

            // Clone template
            const template = document.getElementById('itemRowTemplate');
            const clone = template.content.cloneNode(true);

            // Replace index placeholder
            const tempDiv = document.createElement('div');
            tempDiv.appendChild(clone);
            let html = tempDiv.innerHTML;
            html = html.replace(/__INDEX__/g, newIndex);

            // Insert and initialize
            container.insertAdjacentHTML('beforeend', html);

            const newRow = container.lastElementChild;
            const newSelect = newRow.querySelector('.product-select');
            newRow.dataset.index = newIndex; // 設置索引用於 radio name
            initProductTomSelect([newSelect]);
            const categorySelect = newRow.querySelector('.category-select');
            if (categorySelect && categorySelect.value) {
                applyCategoryFilter(newRow, true);
            }

            updateRemoveButtons();
        }

        function handleRemoveItem(e) {
            if (e.target.closest('.remove-item')) {
                const row = e.target.closest('.item-row');
                const itemCount = document.querySelectorAll('.item-row').length;

                if (itemCount <= 1) {
                    alert('至少需要保留一個商品項目');
                    return;
                }

                // Destroy Tom Select instance before removing to prevent memory leaks
                const select = row.querySelector('.product-select');
                if (select && select.tomselect) {
                    select.tomselect.destroy();
                }

                row.remove();
                calculateTotal();
                updateRemoveButtons();
            }
        }

        function handleItemInput(e) {
            // Real-time calculation for inputs
            if (e.target.classList.contains('quantity-input') ||
                e.target.classList.contains('price-input') ||
                e.target.classList.contains('discount-input')) {
                const row = e.target.closest('.item-row');
                calculateItemAmount(row);
            }
        }

        function handleItemChange(e) {
            if (e.target.classList.contains('category-select')) {
                const row = e.target.closest('.item-row');
                applyCategoryFilter(row, true);
                return;
            }

            // Product selection change (fallback for non-TomSelect events)
            if (e.target.classList.contains('product-select')) {
                const selectedOption = e.target.options[e.target.selectedIndex];
                const price = selectedOption.dataset.price || 0;
                const row = e.target.closest('.item-row');

                row.querySelector('.price-input').value = price;
                calculateItemAmount(row);
            }
        }

        function handleFormSubmit(e) {
            const submitBtn = document.getElementById('submitBtn');

            // Check items count
            const items = document.querySelectorAll('.item-row');
            if (items.length === 0) {
                e.preventDefault();
                alert('至少需要新增一個商品項目');
                return false;
            }

            // 驗證每個商品項目都有選擇圖片
            for (const row of items) {
                const productSelect = row.querySelector('.product-select');
                const imageIdInput = row.querySelector('.image-id-input');
                
                if (productSelect && productSelect.value && (!imageIdInput || !imageIdInput.value)) {
                    e.preventDefault();
                    alert('請為每個商品選擇圖片/顏色');
                    return false;
                }
            }

            // Form validation
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                return false;
            }

            // Prevent double submit
            if (submitBtn.disabled) {
                e.preventDefault();
                return false;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>儲存中...';
        }

        function applyCategoryFilter(row, preserveValue = true) {
            const categorySelect = row.querySelector('.category-select');
            const categoryId = categorySelect ? categorySelect.value : '';
            const productSelect = row.querySelector('.product-select');

            if (!productSelect) {
                return;
            }

            const tomInstance = productSelect.tomselect ?? null;
            const currentValue = tomInstance ? tomInstance.getValue() : productSelect.value;
            const filteredProducts = getProductsByCategory(categoryId);
            const shouldPreserveValue = preserveValue && currentValue && filteredProducts.some(product => String(product.p_id) === String(currentValue));

            if (tomInstance) {
                tomInstance.destroy();
                delete productSelect.tomselect;
            }

            productSelect.innerHTML = '<option value=""></option>';
            filteredProducts.forEach(product => {
                const option = document.createElement('option');
                option.value = product.p_id;
                option.dataset.price = product.p_standard_price;
                option.dataset.category = product.p_pc_id || '';
                option.dataset.size = product.p_size || '';
                option.textContent = product.p_name;
                productSelect.appendChild(option);
            });

            initProductTomSelect([productSelect]);

            if (shouldPreserveValue && productSelect.tomselect) {
                productSelect.tomselect.setValue(currentValue, true);
                // 保留值時也要顯示圖片選擇器
                if (currentValue) {
                    const selectedImageId = row.dataset.imageId;
                    showImageSelector(row, currentValue, selectedImageId);
                }
            } else if (productSelect.tomselect) {
                productSelect.tomselect.clear();
                row.querySelector('.price-input').value = 0;
                hideImageSelector(row);
                calculateItemAmount(row);
            }
        }

        /**
         * 顯示圖片選擇區（使用 AJAX 載入）
         */
        async function showImageSelector(row, productId, selectedImageId = null) {
            const container = row.querySelector('.image-selector-container');
            const imageGrid = row.querySelector('.image-grid');
            const imageIdInput = row.querySelector('.image-id-input');
            const imagePreview = row.querySelector('.item-image-preview');
            const placeholder = imagePreview?.dataset.placeholder || '';
            const rowIndex = row.closest('tr')?.rowIndex || Date.now();

            if (!container || !imageGrid) return;

            // 顯示載入中
            imageGrid.innerHTML = '<div class="text-center p-2"><span class="spinner-border spinner-border-sm"></span> 載入中...</div>';
            container.style.display = 'block';

            try {
                let images = [];

                // 檢查快取
                if (productImagesCache[productId]) {
                    images = productImagesCache[productId];
                } else {
                    // AJAX 載入圖片
                    const response = await fetch(`<?= base_url() ?>quote/getProductImages/${productId}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (!data.success) {
                        throw new Error(data.message || '載入圖片失敗');
                    }

                    images = data.images || [];
                    // 存入快取
                    productImagesCache[productId] = images;
                }

                // 如果沒有圖片
                if (images.length === 0) {
                    imageGrid.innerHTML = '<div class="text-center p-2 text-muted"><i class="bi bi-image"></i> 此商品尚無圖片</div>';
                    if (imageIdInput) imageIdInput.value = '';
                    if (imagePreview) imagePreview.src = placeholder;
                    return;
                }

                // 清空並重建圖片選項
                imageGrid.innerHTML = '';

                images.forEach(image => {
                    const imageItem = document.createElement('label');
                    imageItem.className = 'image-item d-flex flex-column align-items-center';
                    imageItem.style.cursor = 'pointer';
                    
                    const isSelected = selectedImageId && image.pi_id == selectedImageId;
                    const radioName = `image_row_${rowIndex}`;

                    imageItem.innerHTML = `
                        <input type="radio" 
                            name="${radioName}" 
                            value="${image.pi_id}" 
                            class="form-check-input me-0 mb-1"
                            ${isSelected ? 'checked' : ''}
                            style="transform: scale(1.2);">
                        <div class="border rounded overflow-hidden" style="width: 60px; height: 60px;">
                            <img src="<?= base_url() ?>uploads/products/${productId}/${image.pi_name}" 
                                class="img-fluid object-fit-cover w-100 h-100"
                                alt="${image.pi_name}">
                        </div>
                        <small class="text-muted mt-1 text-center" style="max-width: 60px; font-size: 0.7rem; word-break: break-all;">
                            ${image.pi_name}
                        </small>
                    `;

                    // 點擊選擇圖片
                    const radio = imageItem.querySelector('input[type="radio"]');
                    radio.addEventListener('change', function() {
                        if (this.checked) {
                            // 更新隱藏欄位
                            if (imageIdInput) {
                                imageIdInput.value = image.pi_id;
                            }
                            
                            // 更新預覽圖
                            if (imagePreview) {
                                imagePreview.src = `<?= base_url() ?>uploads/products/${productId}/${image.pi_name}`;
                            }

                            // 更新 row 的 data 屬性
                            row.dataset.productId = productId;
                            row.dataset.imageId = image.pi_id;
                        }
                    });

                    imageGrid.appendChild(imageItem);
                });

                // 如果有預設選擇，更新預覽圖
                if (selectedImageId) {
                    const selectedImage = images.find(img => img.pi_id == selectedImageId);
                    if (selectedImage && imagePreview) {
                        imagePreview.src = `<?= base_url() ?>uploads/products/${productId}/${selectedImage.pi_name}`;
                    }
                } else if (imagePreview) {
                    imagePreview.src = placeholder;
                }
            } catch (error) {
                console.error('載入圖片失敗:', error);
                imageGrid.innerHTML = `<div class="text-center p-2 text-danger"><i class="bi bi-exclamation-triangle"></i> ${error.message}</div>`;
                if (imageIdInput) imageIdInput.value = '';
                if (imagePreview) imagePreview.src = placeholder;
            }
        }

        /**
         * 隱藏圖片選擇區
         */
        function hideImageSelector(row) {
            const container = row.querySelector('.image-selector-container');
            const imageIdInput = row.querySelector('.image-id-input');
            const imagePreview = row.querySelector('.item-image-preview');
            const placeholder = imagePreview?.dataset.placeholder || '';

            if (container) {
                container.style.display = 'none';
            }

            if (imageIdInput) {
                imageIdInput.value = '';
            }

            if (imagePreview) {
                imagePreview.src = placeholder;
            }

            // 清除 row 的 data 屬性
            row.dataset.productId = '';
            row.dataset.imageId = '';
        }

        function getProductsByCategory(categoryId) {
            if (!categoryId) {
                return products;
            }

            return products.filter(product => String(product.p_pc_id || '') === String(categoryId));
        }

        function calculateItemAmount(row, updateTotal = true) {
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const unitPrice = parseFloat(row.querySelector('.price-input').value) || 0;
            const discount = parseFloat(row.querySelector('.discount-input').value) || 0;

            const amount = quantity * unitPrice * (1 - discount / 100);
            row.querySelector('.amount-display').value = amount.toFixed(0);

            if (updateTotal) {
                calculateTotal();
            }
        }

        function calculateTotal() {
            // 1. 計算商品小計
            let subtotal = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const amount = parseFloat(row.querySelector('.amount-display').value) || 0;
                subtotal += amount;
            });

            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const taxRate = parseFloat(document.getElementById('taxRate').value) || 0;
            const shippingFee = parseInt(document.getElementById('shippingFee').value) || 0;

            // 2. 折扣後金額 = 商品小計 × (1 - 整單折扣%)
            const discountedSubtotal = subtotal * (1 - discount / 100);

            // 3. 加運費後 = 折扣後金額 + 運費
            const amountWithShipping = discountedSubtotal + shippingFee;

            // 4. 稅額 = 加運費後 × 稅率%
            const taxAmount = amountWithShipping * (taxRate / 100);

            // 5. 總金額 = 加運費後 + 稅額
            const totalAmount = amountWithShipping + taxAmount;

            // 更新顯示
            document.getElementById('subtotalDisplay').textContent = 'NT$ ' + subtotal.toLocaleString('zh-TW', {
                minimumFractionDigits: 0
            });
            document.getElementById('subtotalInput').value = subtotal.toFixed(0);

            document.getElementById('taxDisplay').textContent = 'NT$ ' + taxAmount.toLocaleString('zh-TW', {
                minimumFractionDigits: 0
            });
            document.getElementById('taxInput').value = taxAmount.toFixed(0);

            document.getElementById('totalInput').value = totalAmount.toFixed(0);
        }

        function updateRemoveButtons() {
            const items = document.querySelectorAll('.item-row');
            const removeButtons = document.querySelectorAll('.remove-item');

            if (items.length === 1) {
                removeButtons.forEach(btn => btn.disabled = true);
            } else {
                removeButtons.forEach(btn => btn.disabled = false);
            }
        }
    });
</script>

<?= $this->endSection() ?>