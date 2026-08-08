<?php

/**
 * 訂單商品項目行組件
 * 
 * 使用方式：
 * 1. 在 PHP 循環中渲染現有項目：
 *    foreach ($items as $index => $item) {
 *        echo view('components/order_item_row', [
 *            'index' => $index,
 *            'item' => $item,
 *            'products' => $products,
 *            'productCategories' => $productCategories,
 *            'isTemplate' => false
 *        ]);
 *    }
 * 
 * 2. 作為 JavaScript 模板：
 *    <template id="itemRowTemplate">
 *        <?= view('components/order_item_row', ['index' => '__INDEX__', 'item' => [], 'products' => $products, 'productCategories' => $productCategories, 'isTemplate' => true]) ?>
 *    </template>
 * 
 * @param int|string $index 項目索引
 * @param array $item 項目資料
 * @param array $products 商品列表
 * @param array $productCategories 商品分類列表
 * @param bool $isTemplate 是否為 JavaScript 模板
 */

$index = $index ?? 0;
$item = $item ?? [];
$products = $products ?? [];
$productCategories = $productCategories ?? [];
$isTemplate = $isTemplate ?? false;

// 預設值
$defaults = [
    'oi_id' => '',
    'oi_pi_id' => '',
    'oi_quantity' => 1,
    'oi_unit_price' => 0,
    'oi_discount' => 0,
    'oi_shipped_quantity' => 0,
];

$item = array_merge($defaults, $item);

// 已出貨數量
$shippedQty = $item['oi_shipped_quantity'] ?? 0;
$hasShipped = $shippedQty > 0;

// 取得選中商品的 ID（透過 pi_p_id）
$selectedProductId = '';
if (!empty($item['oi_pi_id']) && !empty($item['pi_p_id'])) {
    $selectedProductId = $item['pi_p_id'];
}

// 找出選中商品的分類
$selectedCategoryId = '';
if (!empty($selectedProductId)) {
    foreach ($products as $p) {
        if ($p['p_id'] == $selectedProductId) {
            $selectedCategoryId = $p['p_pc_id'] ?? '';
            break;
        }
    }
}

// 取得已選擇的圖片
$selectedImage = '';
if (!empty($item['oi_pi_id']) && !empty($selectedProductId) && !empty($item['pi_name'])) {
    $selectedImage = base_url('uploads/products/' . $selectedProductId . '/' . $item['pi_name']);
}
$placeholder = base_url('images/placeholder.png');
?>
<tr class="item-row" 
    data-shipped-qty="<?= $shippedQty ?>"
    data-product-id="<?= $selectedProductId ?>" 
    data-image-id="<?= $item['oi_pi_id'] ?? '' ?>"
    data-selected-size="<?= esc($item['oi_size'] ?? '') ?>">
    <!-- 商品圖片預覽 -->
    <td style="width: 10%;" class="align-middle">
        <input type="hidden" name="items[<?= $index ?>][oi_id]" value="<?= esc($item['oi_id']) ?>">
        <input type="hidden" name="items[<?= $index ?>][oi_pi_id]" class="image-id-input" value="<?= esc($item['oi_pi_id']) ?>" required>
        <div class="ratio ratio-1x1 border rounded overflow-hidden bg-light shadow-sm">
            <img src="<?= esc($selectedImage ?: $placeholder) ?>"
                class="img-fluid item-image-preview object-fit-cover"
                alt="商品圖片"
                data-placeholder="<?= esc($placeholder) ?>"
                style="cursor: pointer;"
                title="點擊查看大圖">
        </div>
    </td>
    <!-- 商品選擇 -->
    <td style="width: 30%;" class="align-middle">
        <div class="d-flex flex-column gap-2">
            <select class="form-select form-select-sm category-select" data-index="<?= $index ?>" title="商品分類">
                <option value="">全部分類</option>
                <?php foreach ($productCategories as $category): ?>
                    <option value="<?= $category['pc_id'] ?>" <?= ($selectedCategoryId == $category['pc_id']) ? 'selected' : '' ?>>
                        <?= esc($category['pc_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select class="form-select form-select-sm product-select"
                data-index="<?= $index ?>"
                title="選擇商品"
                required>
                <option value="">請選擇商品</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= $product['p_id'] ?>"
                        data-price="<?= $product['p_standard_price'] ?>"
                        data-category="<?= $product['p_pc_id'] ?? '' ?>"
                        data-size="<?= esc($product['p_size'] ?? '') ?>"
                        <?= ($selectedProductId == $product['p_id']) ? 'selected' : '' ?>>
                        <?= esc($product['p_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <!-- 圖片選擇區（初始隱藏） -->
            <div class="image-selector-container" style="display: none;">
                <small class="text-muted mb-1 d-block">請選擇顏色/花色：</small>
                <div class="image-grid d-flex flex-wrap gap-2">
                    <!-- 圖片選項將由 JavaScript 動態產生 -->
                </div>
            </div>
        </div>
        <?php if ($hasShipped): ?>
            <div class="mt-1">
                <small class="text-muted">已出貨：<?= $shippedQty ?></small>
            </div>
        <?php endif; ?>
    </td>
    <!-- 尺寸選擇 -->
    <td style="width: 10%;" class="align-middle">
        <select class="form-select form-select-sm size-select"
            name="items[<?= $index ?>][oi_size]"
            title="尺寸"
            required>
            <option value="">請選擇尺寸</option>
            <?php if (!empty($item['oi_size'])): ?>
                <option value="<?= esc($item['oi_size']) ?>" selected><?= esc($item['oi_size']) ?></option>
            <?php endif; ?>
        </select>
    </td>
    <td style="width: 7%;" class="align-middle">
        <input type="number" class="form-control form-control-sm quantity-input text-center"
            name="items[<?= $index ?>][oi_quantity]"
            value="<?= esc($item['oi_quantity']) ?>"
            min="<?= $shippedQty > 0 ? $shippedQty : 1 ?>" step="1"
            data-original-qty="<?= $item['oi_quantity'] ?? 1 ?>"
            title="數量"
            required>
    </td>
    <td style="width: 10%;" class="align-middle">
        <input type="number" class="form-control form-control-sm price-input text-end"
            name="items[<?= $index ?>][oi_unit_price]"
            value="<?= esc($item['oi_unit_price']) ?>"
            min="0" step="1"
            title="單價"
            required>
    </td>
    <td style="width: 7%;" class="align-middle">
        <input type="number" class="form-control form-control-sm discount-input text-center"
            name="items[<?= $index ?>][oi_discount]"
            value="<?= esc($item['oi_discount']) ?>"
            min="0" max="100" step="0.01"
            title="折扣百分比">
    </td>
    <td style="width: 10%;" class="align-middle">
        <input type="text" class="form-control form-control-sm amount-display text-end bg-light fw-bold"
            name="items[<?= $index ?>][oi_amount]"
            value="<?= $item['oi_amount'] ?? 0 ?>"
            title="小計金額"
            readonly>
    </td>
    <td class="text-center align-middle" style="width: 4%;">
        <button type="button" class="btn btn-sm btn-outline-danger remove-item"
            title="刪除此項目"
            <?= $hasShipped ? 'disabled title="此商品已有出貨記錄，無法刪除"' : '' ?>>
            <i class="bi bi-trash"></i>
        </button>
    </td>
</tr>