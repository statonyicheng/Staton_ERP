<?php

/**
 * 訂單商品項目行組件
 *
 * 品項的單位是「商品」（oi_p_id）—— 庫存、成本、出貨扣帳都以商品為準。
 * 顏色/花色與尺寸是該商品的規格（來自 products.p_color / p_size），存在明細上。
 * 圖片只是顯示用，取商品自己的圖片，不參與存檔。
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
    'oi_p_id' => '',
    'oi_supplier' => '',
    'oi_color' => '',
    'oi_size' => '',
    'oi_quantity' => 1,
    'oi_unit_price' => 0,
    'oi_discount' => 0,
    'oi_shipped_quantity' => 0,
];

$item = array_merge($defaults, $item);

// 已出貨數量
$shippedQty = $item['oi_shipped_quantity'] ?? 0;
$hasShipped = $shippedQty > 0;

$selectedProductId = $item['oi_p_id'] ?? '';

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

// 商品圖片（純顯示）：優先用商品主圖，其次用商品的第一張圖
$selectedImage = '';
if (!empty($item['p_image'])) {
    $selectedImage = base_url($item['p_image']);
} elseif (!empty($item['pi_name']) && !empty($item['pi_p_id'])) {
    $selectedImage = base_url('uploads/products/' . $item['pi_p_id'] . '/' . $item['pi_name']);
}
$placeholder = base_url('images/placeholder.png');
?>
<tr class="item-row"
    data-shipped-qty="<?= $shippedQty ?>"
    data-product-id="<?= esc($selectedProductId) ?>"
    data-selected-supplier="<?= esc($item['oi_supplier'] ?? '') ?>"
    data-selected-color="<?= esc($item['oi_color'] ?? '') ?>"
    data-selected-size="<?= esc($item['oi_size'] ?? '') ?>">
    <!-- 商品圖片預覽 -->
    <td style="width: 10%;" class="align-middle">
        <input type="hidden" name="items[<?= $index ?>][oi_id]" value="<?= esc($item['oi_id']) ?>">
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
                name="items[<?= $index ?>][oi_p_id]"
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
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm color-select"
                    name="items[<?= $index ?>][oi_color]"
                    title="顏色/花色">
                    <option value="">顏色/花色</option>
                    <?php if (!empty($item['oi_color'])): ?>
                        <option value="<?= esc($item['oi_color']) ?>" selected><?= esc($item['oi_color']) ?></option>
                    <?php endif; ?>
                </select>
                <select class="form-select form-select-sm supplier-select"
                    name="items[<?= $index ?>][oi_supplier]"
                    title="供應商">
                    <option value="">供應商</option>
                    <?php if (!empty($item['oi_supplier'])): ?>
                        <option value="<?= esc($item['oi_supplier']) ?>" selected><?= esc($item['oi_supplier']) ?></option>
                    <?php endif; ?>
                </select>
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
            title="尺寸">
            <option value="">尺寸</option>
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
