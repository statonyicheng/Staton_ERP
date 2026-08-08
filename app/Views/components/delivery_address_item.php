<?php
/**
 * 送貨地址項目組件
 * 
 * 使用方式：
 * 1. 在 PHP 循環中渲染現有地址：
 *    foreach ($addresses as $index => $address) {
 *        echo view('components/delivery_address_item', [
 *            'index' => $index,
 *            'address' => $address,
 *            'totalCount' => count($addresses)
 *        ]);
 *    }
 * 
 * 2. 作為 JavaScript 模板：
 *    <template id="addressTemplate">
 *        <?= view('components/delivery_address_item', ['index' => '__INDEX__', 'address' => [], 'isTemplate' => true]) ?>
 *    </template>
 * 
 * @param int|string $index 地址索引
 * @param array $address 地址資料
 * @param int $totalCount 地址總數（用於判斷是否可刪除）
 * @param bool $isTemplate 是否為 JavaScript 模板
 */

$index = $index ?? 0;
$address = $address ?? [];
$totalCount = $totalCount ?? 1;
$isTemplate = $isTemplate ?? false;

// 預設值
$defaults = [
    'cda_id' => '',
    'cda_name' => '',
    'cda_contact_person' => '',
    'cda_phone' => '',
    'cda_city' => '',
    'cda_address' => '',
    'cda_is_default' => 0,
    'cda_notes' => ''
];

$address = array_merge($defaults, $address);
$addressNumber = is_numeric($index) ? $index + 1 : 1;
$canDelete = $totalCount > 1 || $isTemplate;
?>

<div class="address-item card mb-3" data-index="<?= $index ?>">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">
                <i class="bi bi-geo-alt-fill text-primary me-1"></i>
                送貨地址 #<span class="address-number"><?= $addressNumber ?></span>
            </h6>
            <button type="button" class="btn btn-sm btn-outline-danger remove-address">
                <i class="bi bi-trash me-1"></i>刪除
            </button>
        </div>

        <input type="hidden" name="delivery_addresses[<?= $index ?>][cda_id]" value="<?= esc($address['cda_id']) ?>">

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">地址名稱</label>
                <input type="text" class="form-control" 
                       name="delivery_addresses[<?= $index ?>][cda_name]" 
                       value="<?= esc($address['cda_name']) ?>" 
                       placeholder="例如：總公司、台北辦公室、新竹廠區">
                <div class="form-text">用於識別不同的送貨地點</div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">收件人</label>
                <input type="text" class="form-control" 
                       name="delivery_addresses[<?= $index ?>][cda_contact_person]" 
                       value="<?= esc($address['cda_contact_person']) ?>" 
                       placeholder="請輸入收件人姓名">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">縣市</label>
                <select class="form-select" 
                        name="delivery_addresses[<?= $index ?>][cda_city]">
                    <option value="">請選擇縣市</option>
                    <?php
                    $cities = [
                        '台北市', '新北市', '桃園市', '台中市', '台南市', '高雄市',
                        '基隆市', '新竹市', '嘉義市', '新竹縣', '苗栗縣', '彰化縣',
                        '南投縣', '雲林縣', '嘉義縣', '屏東縣', '宜蘭縣', '花蓮縣',
                        '台東縣', '澎湖縣', '金門縣', '連江縣'
                    ];
                    foreach ($cities as $city):
                    ?>
                        <option value="<?= $city ?>" <?= ($address['cda_city'] ?? '') == $city ? 'selected' : '' ?>>
                            <?= $city ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-8 mb-3">
                <label class="form-label">詳細地址</label>
                <input type="text" class="form-control" 
                       name="delivery_addresses[<?= $index ?>][cda_address]" 
                       value="<?= esc($address['cda_address']) ?>" 
                       placeholder="請輸入詳細地址">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">收件電話</label>
                <input type="tel" class="form-control" 
                       name="delivery_addresses[<?= $index ?>][cda_phone]" 
                       value="<?= esc($address['cda_phone']) ?>" 
                       placeholder="例如：02-12345678 或 0912-345678">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">備註</label>
                <input type="text" class="form-control" 
                       name="delivery_addresses[<?= $index ?>][cda_notes]" 
                       value="<?= esc($address['cda_notes']) ?>" 
                       placeholder="送貨注意事項">
            </div>
        </div>

        <div class="form-check">
            <input class="form-check-input default-address-checkbox" type="checkbox" 
                   name="delivery_addresses[<?= $index ?>][cda_is_default]" value="1" 
                   id="defaultAddress<?= $index ?>" 
                   <?= !empty($address['cda_is_default']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="defaultAddress<?= $index ?>">
                <i class="bi bi-star-fill text-warning me-1"></i>設為預設地址
            </label>
        </div>
    </div>
</div>

