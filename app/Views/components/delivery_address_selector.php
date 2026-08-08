<?php
/**
 * Delivery Address Selector Component
 * 
 * @param string|null $deliveryCity
 * @param string|null $deliveryAddress
 * @param string $prefix 欄位前綴 (預設: q)
 */

$taiwanCities = [
    '基隆市', '臺北市', '新北市', '桃園市', '新竹市', '新竹縣', '苗栗縣', '臺中市',
    '彰化縣', '南投縣', '雲林縣', '嘉義市', '嘉義縣', '臺南市', '高雄市', '屏東縣',
    '宜蘭縣', '花蓮縣', '臺東縣', '澎湖縣', '金門縣', '連江縣'
];

$deliveryCity = $deliveryCity ?? '';
$deliveryAddress = $deliveryAddress ?? '';
$prefix = $prefix ?? 'q'; // 預設為報價單 (quote)
?>

<div class="row">
    <div class="col-md-3 mb-3">
        <label for="deliveryCity" class="form-label">送貨縣市</label>
        <select class="form-select" id="deliveryCity" name="<?= $prefix ?>_delivery_city">
            <option value="">請選擇縣市</option>
            <?php foreach ($taiwanCities as $city): ?>
                <option value="<?= esc($city) ?>" <?= $city === $deliveryCity ? 'selected' : '' ?>>
                    <?= esc($city) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-9 mb-3">
        <label for="deliveryAddress" class="form-label">送貨地址</label>
        <input type="text" class="form-control" 
            id="deliveryAddress" 
            name="<?= $prefix ?>_delivery_address" 
            value="<?= esc($deliveryAddress) ?>"
            placeholder="請輸入詳細地址">
    </div>
</div>

