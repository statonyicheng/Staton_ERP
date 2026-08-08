<?= $this->extend('_layout') ?>

<?= $this->section('content') ?>
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="bi bi-<?= $isEdit ? 'pencil-square' : 'truck' ?> me-2"></i>
            <?= $isEdit ? '編輯' : '建立' ?>出貨單
        </h2>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form action="<?= url_to('ShipmentController::save') ?>" method="post">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="s_id" value="<?= $shipment['s_id'] ?>">
                <?php endif; ?>
                <input type="hidden" name="s_o_id" value="<?= $order['o_id'] ?>">

                <!-- 基本資料 -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">出貨單號</label>
                        <input type="text" class="form-control" name="s_number" value="<?= $shipmentNumber ?>" readonly>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">出貨日期</label>
                        <input type="date" class="form-control" name="s_date" value="<?= $date ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">關聯訂單</label>
                        <input type="text" class="form-control" value="<?= $order['o_number'] ?>" readonly disabled>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">出貨狀態</label>
                        <select class="form-select" name="s_status">
                            <?php
                            $currentStatus = $isEdit ? ($shipment['s_status'] ?? 1) : 1;
                            foreach (\App\Models\ShipmentModel::$statusMap as $key => $label):
                            ?>
                                <option value="<?= $key ?>" <?= $currentStatus == $key ? 'selected' : '' ?>>
                                    <?= $key ?>. <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">售後狀態</label>
                        <select class="form-select" name="s_after_sales_status">
                            <?php
                            $currentAfterSales = $isEdit ? ($shipment['s_after_sales_status'] ?? 1) : 1;
                            foreach (\App\Models\ShipmentModel::$afterSalesStatusMap as $key => $label):
                            ?>
                                <option value="<?= $key ?>" <?= $currentAfterSales == $key ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">備註</label>
                    <textarea class="form-control" name="s_notes" rows="2" placeholder="送貨地址、司機資訊等..."><?= $shipment['s_notes'] ?? '' ?></textarea>
                </div>

                <!-- 出貨明細 -->
                <h5 class="border-bottom pb-2 mb-3">
                    <i class="bi bi-box-seam me-2 text-primary"></i>出貨內容
                </h5>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 30%;">商品名稱</th>
                                <th style="width: 15%;">訂單數量</th>
                                <th style="width: 15%;">已出貨</th>
                                <th style="width: 15%;">剩餘未出</th>
                                <th style="width: 25%;">本次出貨數量</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $index => $item): ?>
                                <?php
                                // 其他出貨單的已出貨數量（不含本次）
                                $otherShippedQty = $isEdit ? ($item['other_shipped_quantity'] ?? 0) : $item['oi_shipped_quantity'];
                                // 本次出貨數量
                                $currentShipmentQty = $item['current_shipment_qty'] ?? 0;
                                // 已出貨（含本次）
                                $totalShippedQty = $otherShippedQty + $currentShipmentQty;
                                // 剩餘未出
                                $initialRemaining = $item['oi_quantity'] - $totalShippedQty;
                                ?>
                                <tr class="shipment-row"
                                    data-order-qty="<?= $item['oi_quantity'] ?>"
                                    data-other-shipped-qty="<?= $otherShippedQty ?>">
                                    <td>
                                        <?= esc($item['p_name']) ?>
                                        <input type="hidden" name="items[<?= $index ?>][si_oi_id]" value="<?= $item['oi_id'] ?>">
                                    </td>
                                    <td class="order-qty"><?= $item['oi_quantity'] ?></td>
                                    <td>
                                        <span class="total-shipped-qty"><?= $totalShippedQty ?></span>
                                    </td>
                                    <td>
                                        <span class="remaining-qty"><?= $initialRemaining ?></span>
                                    </td>
                                    <td>
                                        <?php if ($item['remaining_quantity'] > 0): ?>
                                            <input type="number"
                                                class="form-control shipment-qty"
                                                name="items[<?= $index ?>][si_quantity]"
                                                value="<?= $item['current_shipment_qty'] ?? $item['remaining_quantity'] ?>"
                                                min="0"
                                                max="<?= $item['remaining_quantity'] ?>">
                                        <?php else: ?>
                                            <span class="text-muted">已全出</span>
                                            <input type="hidden" name="items[<?= $index ?>][si_quantity]" value="0">
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex gap-2 justify-content-end mt-4">
                    <a href="<?= url_to('ShipmentController::index') ?>?order_id=<?= $order['o_id'] ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i>取消
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i><?= $isEdit ? '更新' : '確認出貨' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 監聽所有出貨數量輸入框的變化
        const shipmentQtyInputs = document.querySelectorAll('.shipment-qty');

        shipmentQtyInputs.forEach(input => {
            input.addEventListener('input', function() {
                updateQuantities(this);
            });
        });

        function updateQuantities(input) {
            const row = input.closest('.shipment-row');
            if (!row) return;

            const orderQty = parseFloat(row.dataset.orderQty) || 0;
            const otherShippedQty = parseFloat(row.dataset.otherShippedQty) || 0;
            const currentQty = parseFloat(input.value) || 0;

            // 已出貨（含本次）= 其他出貨單的已出貨 + 本次出貨數量
            const totalShipped = otherShippedQty + currentQty;

            // 剩餘未出 = 訂單數量 - 已出貨（含本次）
            const remaining = orderQty - totalShipped;

            // 更新「已出貨」顯示
            const totalShippedSpan = row.querySelector('.total-shipped-qty');
            if (totalShippedSpan) {
                totalShippedSpan.textContent = totalShipped;
            }

            // 更新「剩餘未出」顯示
            const remainingSpan = row.querySelector('.remaining-qty');
            if (remainingSpan) {
                remainingSpan.textContent = remaining;

                // 如果剩餘數量為負數，顯示警告樣式
                if (remaining < 0) {
                    remainingSpan.classList.add('text-danger', 'fw-bold');
                    input.classList.add('is-invalid');
                } else {
                    remainingSpan.classList.remove('text-danger', 'fw-bold');
                    input.classList.remove('is-invalid');
                }
            }
        }

        // 表單提交驗證
        document.querySelector('form').addEventListener('submit', function(e) {
            let hasError = false;

            shipmentQtyInputs.forEach(input => {
                const row = input.closest('.shipment-row');
                if (!row) return;

                const orderQty = parseFloat(row.dataset.orderQty) || 0;
                const otherShippedQty = parseFloat(row.dataset.otherShippedQty) || 0;
                const currentQty = parseFloat(input.value) || 0;
                const totalShipped = otherShippedQty + currentQty;
                const remaining = orderQty - totalShipped;

                if (remaining < 0) {
                    hasError = true;
                    input.classList.add('is-invalid');
                }
            });

            if (hasError) {
                e.preventDefault();
                alert('出貨數量不能超過剩餘未出數量！');
                return false;
            }
        });
    });
</script>

<?= $this->endSection() ?>