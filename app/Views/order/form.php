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

    /* 圖片選擇器樣式 */
    .image-selector {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 4px;
        margin-top: 8px;
    }

    .image-option {
        display: flex;
        flex-direction: column;
        align-items: center;
        cursor: pointer;
        padding: 8px;
        border: 2px solid transparent;
        border-radius: 4px;
        transition: all 0.2s;
    }

    .image-option:hover {
        border-color: #0d6efd;
        background: white;
    }

    .image-option input[type="radio"] {
        margin-bottom: 5px;
    }

    .image-option img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 4px;
        margin-bottom: 5px;
    }

    .image-option.selected {
        border-color: #0d6efd;
        background: white;
    }

    .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(0, 0, 0, 0.1);
        border-radius: 50%;
        border-top-color: #0d6efd;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>

<div class="container-fluid mt-4">
    <!-- 頁面標題 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?> me-2"></i>
            <?= $isEdit ? '編輯' : '新增' ?>訂單
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
            <form id="orderForm" action="<?= url_to('OrderController::save') ?>" method="post" novalidate>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="o_id" value="<?= $data['o_id'] ?? old('o_id') ?>">
                    <?php if (!empty($data['o_q_id'])): ?>
                        <input type="hidden" name="o_q_id" value="<?= $data['o_q_id'] ?>">
                    <?php endif; ?>
                <?php endif; ?>

                <!-- 基本資料區塊 -->
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="bi bi-file-earmark-text me-2 text-primary"></i>基本資料
                    </h5>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="orderNumber" class="form-label">訂單編號</label>
                            <input
                                type="text"
                                class="form-control <?= getFieldClass('o_number') ?>"
                                id="orderNumber"
                                name="o_number"
                                value="<?= old('o_number', $data['o_number'] ?? $orderNumber ?? '') ?>"
                                required
                                readonly>
                            <?= showFieldError('o_number') ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="orderDate" class="form-label">
                                訂單日期 <span class="text-danger">*</span>
                            </label>
                            <input
                                type="date"
                                class="form-control <?= getFieldClass('o_date') ?>"
                                id="orderDate"
                                name="o_date"
                                value="<?= old('o_date', $data['o_date'] ?? date('Y-m-d')) ?>"
                                required>
                            <?= showFieldError('o_date') ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="deliveryDate" class="form-label">預交期</label>
                            <input
                                type="date"
                                class="form-control <?= getFieldClass('o_delivery_date') ?>"
                                id="deliveryDate"
                                name="o_delivery_date"
                                value="<?= old('o_delivery_date', $data['o_delivery_date'] ?? '') ?>">
                            <?= showFieldError('o_delivery_date') ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="status" class="form-label">訂單狀態</label>
                            <select class="form-select <?= getFieldClass('o_status') ?>" id="status" name="o_status">
                                <option value="processing" <?= (old('o_status', $data['o_status'] ?? '') == 'processing') ? 'selected' : '' ?>>處理中</option>
                                <option value="completed" <?= (old('o_status', $data['o_status'] ?? '') == 'completed') ? 'selected' : '' ?>>已完結</option>
                                <option value="cancelled" <?= (old('o_status', $data['o_status'] ?? '') == 'cancelled') ? 'selected' : '' ?>>已取消</option>
                            </select>
                            <?= showFieldError('o_status') ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="customer" class="form-label">
                                客戶 <span class="text-danger">*</span>
                            </label>
                            <select
                                class="form-select <?= getFieldClass('o_c_id') ?>"
                                id="customer"
                                name="o_c_id"
                                required>
                                <option value=""></option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?= $customer['c_id'] ?>"
                                        <?= (old('o_c_id', $data['o_c_id'] ?? '') == $customer['c_id']) ? 'selected' : '' ?>>
                                        <?= esc($customer['c_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= showFieldError('o_c_id') ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="contactSelect" class="form-label">聯絡人</label>
                            <select
                                class="form-select <?= getFieldClass('o_cc_id') ?>"
                                id="contactSelect"
                                name="o_cc_id"
                                data-endpoint="<?= base_url('customer/contacts') ?>"
                                data-initial-customer="<?= esc($data['o_c_id'] ?? '') ?>"
                                data-selected-id="<?= esc(old('o_cc_id', $data['o_cc_id'] ?? '')) ?>">
                                <option value="">請先選擇客戶</option>
                            </select>
                            <?= showFieldError('o_cc_id') ?>
                            <div class="form-text" id="contactInfo" data-placeholder="電話 / Email"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="paymentStatus" class="form-label">付款狀態</label>
                            <select class="form-select <?= getFieldClass('o_payment_status') ?>" id="paymentStatus" name="o_payment_status">
                                <option value="unpaid" <?= (old('o_payment_status', $data['o_payment_status'] ?? '') == 'unpaid') ? 'selected' : '' ?>>未收款</option>
                                <option value="partial" <?= (old('o_payment_status', $data['o_payment_status'] ?? '') == 'partial') ? 'selected' : '' ?>>部分收款</option>
                                <option value="paid" <?= (old('o_payment_status', $data['o_payment_status'] ?? '') == 'paid') ? 'selected' : '' ?>>已結清</option>
                            </select>
                            <?= showFieldError('o_payment_status') ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="shipmentStatus" class="form-label">出貨狀態</label>
                            <select class="form-select <?= getFieldClass('o_shipment_status') ?>" id="shipmentStatus" name="o_shipment_status">
                                <option value="preparing" <?= (old('o_shipment_status', $data['o_shipment_status'] ?? '') == 'preparing') ? 'selected' : '' ?>>備貨中</option>
                                <option value="partial" <?= (old('o_shipment_status', $data['o_shipment_status'] ?? '') == 'partial') ? 'selected' : '' ?>>部分出貨</option>
                                <option value="shipped" <?= (old('o_shipment_status', $data['o_shipment_status'] ?? '') == 'shipped') ? 'selected' : '' ?>>已全出</option>
                            </select>
                            <?= showFieldError('o_shipment_status') ?>
                        </div>
                    </div>

                    <!-- 送貨地址區塊 -->
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="invoiceNumber" class="form-label">發票號碼</label>
                            <input
                                type="text"
                                class="form-control <?= getFieldClass('o_invoice_number') ?>"
                                id="invoiceNumber"
                                name="o_invoice_number"
                                value="<?= old('o_invoice_number', $data['o_invoice_number'] ?? '') ?>"
                                placeholder="請輸入發票號碼">
                            <?= showFieldError('o_invoice_number') ?>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="vendor" class="form-label">供應商</label>
                            <select
                                class="form-select <?= getFieldClass('o_vendor') ?>"
                                id="vendor"
                                name="o_vendor">
                                <option value="">請選擇供應商</option>
                                <?php 
                                    $vendors = ['文興W', '巨鋒G'];
                                    $selectedVendor = old('o_vendor', $data['o_vendor'] ?? '');
                                ?>
                                <?php foreach ($vendors as $vendor): ?>
                                    <option value="<?= esc($vendor) ?>" <?= $selectedVendor === $vendor ? 'selected' : '' ?>>
                                        <?= esc($vendor) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= showFieldError('o_vendor') ?>
                        </div>

                        <div class="col-md-6 mb-3">
                            <?= view('components/delivery_address_selector', [
                                'deliveryCity' => $data['o_delivery_city'] ?? old('o_delivery_city'),
                                'deliveryAddress' => $data['o_delivery_address'] ?? old('o_delivery_address'),
                                'prefix' => 'o'
                            ]) ?>
                        </div>
                    </div>
                </div>

                <!-- 廠商資料區塊 -->
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="bi bi-shop me-2 text-primary"></i>廠商資料
                    </h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="vendorContect" class="form-label">廠商聯絡人</label>
                            <input
                                type="text"
                                class="form-control <?= getFieldClass('o_vendor_contect') ?>"
                                id="vendorContect"
                                name="o_vendor_contect"
                                value="<?= old('o_vendor_contect', $data['o_vendor_contect'] ?? '') ?>"
                                placeholder="請輸入廠商聯絡人">
                            <?= showFieldError('o_vendor_contect') ?>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="vendorShippingAddress" class="form-label">廠商出貨地址</label>
                            <input
                                type="text"
                                class="form-control <?= getFieldClass('o_shipping_address') ?>"
                                id="vendorShippingAddress"
                                name="o_shipping_address"
                                value="<?= old('o_shipping_address', $data['o_shipping_address'] ?? '') ?>"
                                placeholder="請輸入廠商出貨地址">
                            <?= showFieldError('o_shipping_address') ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="vendorAddress" class="form-label">廠商地址</label>
                            <input
                                type="text"
                                class="form-control <?= getFieldClass('o_vendor_address') ?>"
                                id="vendorAddress"
                                name="o_vendor_address"
                                value="<?= old('o_vendor_address', $data['o_vendor_address'] ?? '') ?>"
                                placeholder="請輸入廠商詳細地址">
                            <?= showFieldError('o_vendor_address') ?>
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
                                    echo view('components/order_item_row', [
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

                <!-- 商品項目模板 -->
                <template id="itemRowTemplate">
                    <?= view('components/order_item_row', [
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
                                name="o_notes"
                                rows="4"
                                placeholder="請輸入備註"><?= old('o_notes', $data['o_notes'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td class="text-end fw-bold">小計：</td>
                                    <td class="text-end">
                                        <span id="subtotalDisplay">NT$ <?= number_format(old('o_subtotal', $data['o_subtotal'] ?? 0)) ?></span>
                                        <input type="hidden" name="o_subtotal" id="subtotalInput" value="<?= old('o_subtotal', $data['o_subtotal'] ?? 0) ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end">
                                        <label for="discount" class="form-label mb-0">整單折扣(%)：</label>
                                    </td>
                                    <td class="text-end">
                                        <input type="number" class="form-control form-control-sm text-end"
                                            id="discount" name="o_discount"
                                            value="<?= old('o_discount', $data['o_discount'] ?? 0) ?>"
                                            min="0" max="100" step="0.01">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end">
                                        <label for="taxRate" class="form-label mb-0">稅率(%)：</label>
                                    </td>
                                    <td class="text-end">
                                        <input type="number" class="form-control form-control-sm text-end"
                                            id="taxRate" name="o_tax_rate"
                                            value="<?= old('o_tax_rate', $data['o_tax_rate'] ?? 5) ?>"
                                            min="0" max="100" step="0.01">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end">
                                        <label for="shippingFee" class="form-label mb-0">運費：</label>
                                    </td>
                                    <td class="text-end">
                                        <input type="number" class="form-control form-control-sm text-end"
                                            id="shippingFee" name="o_shipping_fee"
                                            value="<?= old('o_shipping_fee', $data['o_shipping_fee'] ?? 0) ?>"
                                            min="0" step="1">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end fw-bold">稅額：</td>
                                    <td class="text-end">
                                        <span id="taxDisplay">NT$ <?= number_format(old('o_tax_amount', $data['o_tax_amount'] ?? 0)) ?></span>
                                        <input type="hidden" name="o_tax_amount" id="taxInput" value="<?= old('o_tax_amount', $data['o_tax_amount'] ?? 0) ?>">
                                    </td>
                                </tr>
                                <tr class="table-primary">
                                    <td class="text-end fw-bold fs-5 align-middle">訂單總金額：</td>
                                    <td class="text-end">
                                        <div class="input-group input-group-lg justify-content-end">
                                            <span class="input-group-text bg-transparent border-0 fw-bold fs-5 text-primary">NT$</span>
                                            <input type="number" 
                                                   class="form-control form-control-lg text-end fw-bold fs-5 text-primary border-0 bg-transparent p-0" 
                                                   name="o_total_amount" 
                                                   id="totalInput" 
                                                   value="<?= old('o_total_amount', $data['o_total_amount'] ?? 0) ?>" 
                                                   style="max-width: 200px;">
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 表單按鈕 -->
                <div class="d-flex gap-2 justify-content-end">
                    <a href="<?= url_to('OrderController::index') ?>" class="btn btn-outline-secondary">
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
            dropdownParent: 'body'
        };

        // --- State ---
        let itemIndex = <?= count($items) ?>;
        const products = <?= json_encode($products) ?>;
        const productImagesCache = {}; // 圖片緩存

        // --- Initialization ---
        initCustomerSelect();
        initContacts();
        initProductTomSelect(document.querySelectorAll('.product-select'));
        document.querySelectorAll('.item-row').forEach(async row => {
            const categorySelect = row.querySelector('.category-select');
            if (categorySelect && categorySelect.value) {
                applyCategoryFilter(row, true);
            }
            initVariantOptions(row, true);
            
            // 如果已有選擇商品，顯示圖片選擇器
            const productSelect = row.querySelector('.product-select');
            if (productSelect && productSelect.value) {
                const selectedImageId = row.dataset.imageId;
                await showImageSelector(row, productSelect.value, selectedImageId);
            }
        });
        initCalculations();
        updateRemoveButtons();
        // 送貨地址管理器已在 component 中自動初始化

        // --- Event Listeners ---
        bindEvents();

        // --- Functions ---
        function initCustomerSelect() {
            const customerSelect = document.getElementById('customer');
            if (customerSelect) {
                new TomSelect(customerSelect, {
                    ...TOM_SELECT_COMMON_CONFIG,
                    placeholder: '請選擇客戶'
                });
            }
        }

        function initContacts() {
            const customerSelect = document.getElementById('customer');
            const contactSelect = document.getElementById('contactSelect');
            const contactInfo = document.getElementById('contactInfo');
            const endpoint = contactSelect ? contactSelect.dataset.endpoint : '';
            const initialCustomerId = contactSelect ? contactSelect.dataset.initialCustomer : '';
            const initialContactId = contactSelect ? contactSelect.dataset.selectedId : '';

            if (!contactSelect || !customerSelect || !endpoint) {
                return;
            }

            const tomConfig = {
                ...TOM_SELECT_COMMON_CONFIG,
                placeholder: '請選擇聯絡人'
            };

            const contactSelectInstance = new TomSelect(contactSelect, tomConfig);

            customerSelect.addEventListener('change', function() {
                loadContacts(this.value, contactSelectInstance, contactInfo, endpoint);
            });

            if (initialCustomerId) {
                loadContacts(initialCustomerId, contactSelectInstance, contactInfo, endpoint, initialContactId, true);
            }
        }

        function loadContacts(customerId, selectInstance, infoDom, endpoint, selectedId = '', preserveValue = false) {
            if (!customerId) {
                selectInstance.clearOptions();
                selectInstance.addOption({
                    value: '',
                    text: '請先選擇客戶'
                });
                selectInstance.setValue('');
                infoDom.textContent = infoDom.dataset.placeholder || '';
                return;
            }

            fetch(`${endpoint}/${customerId}`)
                .then(resp => resp.json())
                .then(result => {
                    selectInstance.clearOptions();

                    if (!result.success || !result.data || result.data.length === 0) {
                        selectInstance.addOption({
                            value: '',
                            text: '無聯絡人資料'
                        });
                        selectInstance.setValue('');
                        infoDom.textContent = '無聯絡人資料';
                        return;
                    }

                    const contacts = result.data;
                    contacts.forEach(c => {
                        const label = c.cc_phone ? `${c.cc_name} (${c.cc_phone})` : c.cc_name;
                        selectInstance.addOption({
                            value: c.cc_id,
                            text: label,
                            phone: c.cc_phone,
                            email: c.cc_email
                        });
                    });

                    let targetId = preserveValue ? selectInstance.getValue() : selectedId;
                    if (!targetId || !contacts.some(c => String(c.cc_id) === String(targetId))) {
                        targetId = contacts[0].cc_id;
                    }

                    selectInstance.setValue(targetId);

                    const selected = contacts.find(c => String(c.cc_id) === String(targetId));
                    if (selected) {
                        infoDom.textContent = `${selected.cc_email || ''}`.trim();
                    } else {
                        infoDom.textContent = infoDom.dataset.placeholder || '';
                    }

                    selectInstance.on('change', function(value) {
                        const sel = contacts.find(c => String(c.cc_id) === String(value));
                        if (sel) {
                            infoDom.textContent = `${sel.cc_email || ''}`.trim();
                        } else {
                            infoDom.textContent = infoDom.dataset.placeholder || '';
                        }
                    });
                })
                .catch(() => {
                    selectInstance.clearOptions();
                    selectInstance.addOption({
                        value: '',
                        text: '載入失敗'
                    });
                    selectInstance.setValue('');
                    infoDom.textContent = '載入聯絡人失敗';
                });
        }

        function initProductTomSelect(elements) {
            elements.forEach(function(element) {
                if (element.tomselect) return;
                new TomSelect(element, {
                    ...TOM_SELECT_COMMON_CONFIG,
                    placeholder: '請選擇商品',
                    onInitialize: function() {
                        this.dropdown_content.style.maxHeight = '250px';
                    },
                    onChange: async function(value) {
                        // 當商品選擇變更時，更新價格和變體選項
                        const row = element.closest('.item-row');
                        if (!row) return;

                        if (value) {
                            const selectedProduct = products.find(p => p.p_id == value);
                            if (selectedProduct) {
                                row.querySelector('.price-input').value = selectedProduct.p_standard_price || 0;
                            } else {
                                row.querySelector('.price-input').value = 0;
                            }
                            
                            // 顯示圖片選擇器
                            await showImageSelector(row, value);
                        } else {
                            row.querySelector('.price-input').value = 0;
                            hideImageSelector(row);
                        }

                        initVariantOptions(row, false);
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

        function bindEvents() {
            document.getElementById('addItemBtn').addEventListener('click', handleAddItem);

            const itemsContainer = document.getElementById('itemsContainer');
            itemsContainer.addEventListener('click', handleRemoveItem);
            itemsContainer.addEventListener('change', handleItemChange);
            itemsContainer.addEventListener('input', handleItemInput);

            // 金額欄位變更時重新計算
            ['discount', 'taxRate', 'shippingFee'].forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('input', calculateTotal);
                }
            });

            document.getElementById('orderForm').addEventListener('submit', handleFormSubmit);
        }

        function handleItemInput(e) {
            if (e.target.classList.contains('quantity-input') ||
                e.target.classList.contains('price-input') ||
                e.target.classList.contains('discount-input')) {
                const row = e.target.closest('.item-row');

                // 驗證數量不能小於已出貨數量
                if (e.target.classList.contains('quantity-input')) {
                    const shippedQty = parseFloat(row.dataset.shippedQty) || 0;
                    const newQty = parseFloat(e.target.value) || 0;

                    if (shippedQty > 0 && newQty < shippedQty) {
                        e.target.setCustomValidity(`訂購數量不能小於已出貨數量 (${shippedQty})`);
                        e.target.classList.add('is-invalid');
                    } else {
                        e.target.setCustomValidity('');
                        e.target.classList.remove('is-invalid');
                    }
                }

                calculateItemAmount(row);
            }
        }

        function handleItemChange(e) {
            if (e.target.classList.contains('category-select')) {
                const row = e.target.closest('.item-row');
                applyCategoryFilter(row, true);
                return;
            }

            if (e.target.classList.contains('product-select')) {
                const selectedValue = e.target.value;
                const row = e.target.closest('.item-row');

                // Find the selected product from the products array
                const selectedProduct = products.find(p => p.p_id == selectedValue);
                if (selectedProduct) {
                    row.querySelector('.price-input').value = selectedProduct.p_standard_price || 0;
                } else {
                    row.querySelector('.price-input').value = 0;
                }

                initVariantOptions(row, false);
                calculateItemAmount(row);
            }
        }

        function handleAddItem() {
            const container = document.getElementById('itemsContainer');
            const newIndex = itemIndex++;
            const template = document.getElementById('itemRowTemplate');
            const clone = template.content.cloneNode(true);

            // Replace placeholder in template content
            const tempDiv = document.createElement('div');
            tempDiv.appendChild(clone);
            let html = tempDiv.innerHTML.replace(/__INDEX__/g, newIndex);

            container.insertAdjacentHTML('beforeend', html);

            const newRow = container.lastElementChild;
            const productSelect = newRow.querySelector('.product-select');
            initProductTomSelect([productSelect]);
            const categorySelect = newRow.querySelector('.category-select');
            if (categorySelect && categorySelect.value) {
                applyCategoryFilter(newRow, true);
            }
            initVariantOptions(newRow, true);
            updateRemoveButtons();
        }

        function handleRemoveItem(e) {
            if (e.target.closest('.remove-item')) {
                const btn = e.target.closest('.remove-item');

                // 檢查按鈕是否被禁用
                if (btn.disabled) {
                    alert('此商品已有出貨記錄，無法刪除');
                    return;
                }

                const row = e.target.closest('.item-row');
                if (document.querySelectorAll('.item-row').length <= 1) {
                    alert('至少需要保留一個商品項目');
                    return;
                }

                const select = row.querySelector('.product-select');
                if (select && select.tomselect) select.tomselect.destroy();

                row.remove();
                calculateTotal();
                updateRemoveButtons();
            }
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
                option.textContent = `${product.p_name}`;
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
            initVariantOptions(row, !preserveValue);
        }

        function initVariantOptions(row, preserveExisting) {
            const productSelect = row.querySelector('.product-select');
            const productId = productSelect ? productSelect.value : '';
            const product = products.find(p => String(p.p_id) === String(productId));

            const supplierSelect = row.querySelector('.supplier-select');
            const colorSelect = row.querySelector('.color-select');
            const sizeSelect = row.querySelector('.size-select');
            const imagePreview = row.querySelector('.item-image-preview');

            const savedSupplier = row.dataset.selectedSupplier || '';
            const savedColor = row.dataset.selectedColor || '';
            const savedSize = row.dataset.selectedSize || '';

            const setOptions = (selectEl, values, saved) => {
                if (!selectEl) return;
                const current = preserveExisting ? (selectEl.value || saved) : saved;
                selectEl.innerHTML = '<option value=""></option>';
                values.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v;
                    opt.textContent = v;
                    selectEl.appendChild(opt);
                });
                if (current && values.includes(current)) {
                    selectEl.value = current;
                }
            };

            const splitValues = (str) => {
                if (!str) return [];
                return str.split('、').map(s => s.trim()).filter(Boolean);
            };

            if (product) {
                setOptions(supplierSelect, splitValues(product.p_supplier), preserveExisting ? (supplierSelect?.value || savedSupplier) : savedSupplier);
                setOptions(colorSelect, splitValues(product.p_color), preserveExisting ? (colorSelect?.value || savedColor) : savedColor);
                setOptions(sizeSelect, splitValues(product.p_size), preserveExisting ? (sizeSelect?.value || savedSize) : savedSize);

                if (imagePreview) {
                    const placeholder = imagePreview.dataset.placeholder || '';
                    const imageSrc = product.p_image ? '<?= base_url() ?>' + product.p_image : placeholder;
                    imagePreview.src = imageSrc;
                }
            } else {
                setOptions(supplierSelect, [], '');
                setOptions(styleSelect, [], '');
                setOptions(colorSelect, [], '');
                setOptions(sizeSelect, [], '');
                if (imagePreview) {
                    const placeholder = imagePreview.dataset.placeholder || '';
                    imagePreview.src = placeholder;
                }
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
                    const response = await fetch(`<?= base_url() ?>order/getProductImages/${productId}`, {
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
         * 隱藏圖片選擇器
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
            const disabled = items.length === 1;
            removeButtons.forEach(btn => btn.disabled = disabled);
        }

        function handleFormSubmit(e) {
            const submitBtn = document.getElementById('submitBtn');
            if (document.querySelectorAll('.item-row').length === 0) {
                e.preventDefault();
                alert('至少需要新增一個商品項目');
                return false;
            }

            // 驗證每個商品項目都有選擇圖片
            const rows = document.querySelectorAll('.item-row');
            for (const row of rows) {
                const productSelect = row.querySelector('.product-select');
                const imageIdInput = row.querySelector('.image-id-input');
                
                if (productSelect && productSelect.value && (!imageIdInput || !imageIdInput.value)) {
                    e.preventDefault();
                    alert('請為每個商品選擇圖片/顏色');
                    return false;
                }
            }

            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('was-validated');
                return false;
            }

            if (submitBtn.disabled) {
                e.preventDefault();
                return false;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>儲存中...';
        }
    });
</script>

<?= $this->endSection() ?>