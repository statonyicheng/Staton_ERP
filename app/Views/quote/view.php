<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="container-fluid mt-4">
    <!-- Header & Actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-file-earmark-text me-2"></i>報價單內容</h2>
        <div class="d-flex gap-2">
            <a href="<?= url_to('QuoteController::print', $data['q_id']) ?>"
                class="btn btn-outline-dark btn-sm"
                target="_blank">
                <i class="bi bi-printer me-1"></i>列印
            </a>
            <?php if (empty($data['q_o_id'])): ?>
                <a href="<?= url_to('QuoteController::edit', $data['q_id']) ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil-square me-1"></i>編輯
                </a>
                <button type="button" class="btn btn-success btn-sm btn-convert-order"
                    data-id="<?= $data['q_id'] ?>"
                    data-number="<?= esc($data['q_number']) ?>">
                    <i class="bi bi-check-circle me-1"></i>轉成訂單
                </button>
            <?php else: ?>
                <a href="<?= url_to('OrderController::edit', $data['q_o_id']) ?>" class="btn btn-info btn-sm text-white">
                    <i class="bi bi-box-seam me-1"></i>查看訂單
                </a>
            <?php endif; ?>
            <a href="<?= url_to('QuoteController::index') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>返回列表
            </a>
        </div>
    </div>

    <!-- 成功/錯誤訊息 -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- 報價單資訊 & 客戶資料 -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light py-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0 text-primary fw-bold">報價單號：<?= esc($data['q_number']) ?></h5>
                        </div>
                        <div class="col-md-6 text-md-end text-muted small">
                            建立時間：<?= esc($data['created_at'] ?? date('Y-m-d H:i:s')) ?>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- 左側：基本資料 -->
                        <div class="col-md-4 border-end">
                            <h6 class="text-secondary mb-3 border-bottom pb-2">基本資料</h6>
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <td class="text-muted" width="100">報價日期</td>
                                    <td class="fw-bold"><?= esc($data['q_date']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">有效日期</td>
                                    <td class="text-danger fw-bold"><?= esc($data['q_valid_date']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">供應商</td>
                                    <td class="fw-bold"><?= esc($data['q_vendor'] ?? '-') ?></td>
                                </tr>
                            </table>
                        </div>

                        <!-- 中間：客戶資料 -->
                        <div class="col-md-4 border-end">
                            <h6 class="text-secondary mb-3 border-bottom pb-2">客戶資料</h6>
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <td class="text-muted" width="80">客戶名稱</td>
                                    <td class="fw-bold"><?= esc($data['customer']['c_name'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">統一編號</td>
                                    <td><?= esc($data['customer']['c_tax_id'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">聯絡人</td>
                                    <td><?= esc($data['contact']['cc_name'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">聯絡電話</td>
                                    <td><?= esc($data['contact']['cc_phone'] ?? '-') ?></td>
                                </tr>
                            </table>
                        </div>

                        <!-- 右側：送貨資訊 -->
                        <div class="col-md-4">
                            <h6 class="text-secondary mb-3 border-bottom pb-2">送貨資訊</h6>
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <td class="text-muted" width="80">送貨縣市</td>
                                    <td><?= esc($data['q_delivery_city'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">詳細地址</td>
                                    <td><?= esc($data['q_delivery_address'] ?? '-') ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 商品明細 -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0 card-title">商品明細</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 8%;">圖片</th>
                                    <th style="width: 32%;">商品資訊</th>
                                    <th class="text-center" style="width: 12%;">尺寸</th>
                                    <th class="text-center" style="width: 10%;">數量</th>
                                    <th class="text-end" style="width: 12%;">單價</th>
                                    <th class="text-center" style="width: 10%;">折扣</th>
                                    <th class="text-end pe-4" style="width: 16%;">金額</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($data['items'])): ?>
                                    <?php foreach ($data['items'] as $item): ?>
                                        <?php
                                        // 處理圖片路徑（使用 product_images）
                                        $imagePath = base_url('assets/images/placeholder.png');
                                        if (!empty($item['pi_name']) && !empty($item['pi_p_id'])) {
                                            $imagePath = base_url('uploads/products/' . $item['pi_p_id'] . '/' . $item['pi_name']);
                                        }

                                        // 圖片檔名就是顏色/花色
                                        $colorSpec = !empty($item['pi_name']) ? pathinfo($item['pi_name'], PATHINFO_FILENAME) : '';
                                        ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="ratio ratio-1x1 bg-light border rounded" style="width: 60px;">
                                                    <img src="<?= esc($imagePath) ?>" alt=""
                                                        class="img-fluid object-fit-cover rounded"
                                                        onerror="this.src='<?= base_url('assets/images/placeholder.png') ?>'">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= esc($item['p_name']) ?></div>
                                                <div class="d-flex align-items-center gap-2 mt-1">
                                                    <?php if (!empty($item['pc_name'])): ?>
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10 rounded-pill px-2">
                                                            <?= esc($item['pc_name']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="small text-muted"><?= esc($item['p_code']) ?></span>
                                                </div>

                                                <?php if ($colorSpec): ?>
                                                    <div class="small text-info mt-1">
                                                        <i class="bi bi-palette me-1"></i>顏色/花色：<?= esc($colorSpec) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="text-dark small fw-bold">
                                                    <?= esc($item['qi_size'] ?: '-') ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border fs-6">
                                                    <?= $item['qi_quantity'] ?>
                                                </span>
                                            </td>
                                            <td class="text-end">NT$ <?= number_format($item['qi_unit_price'], 0) ?></td>
                                            <td class="text-center text-danger">
                                                <?php if ($item['qi_discount'] > 0): ?>
                                                    -<?= floatval($item['qi_discount']) ?>%
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end fw-bold pe-4">NT$ <?= number_format($item['qi_amount'], 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">無商品項目</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white p-4">
                    <div class="row justify-content-end">
                        <div class="col-md-5 col-lg-4">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted">商品小計</td>
                                    <td class="text-end fw-bold">NT$ <?= number_format($data['q_subtotal'], 0) ?></td>
                                </tr>
                                <?php if ($data['q_discount'] > 0): ?>
                                    <tr>
                                        <td class="text-danger">整單折扣</td>
                                        <td class="text-end text-danger">- <?= floatval($data['q_discount']) ?>%</td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <td class="text-muted">運費</td>
                                    <td class="text-end">NT$ <?= number_format($data['q_shipping_fee'], 0) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">營業稅 (<?= floatval($data['q_tax_rate']) ?>%)</td>
                                    <td class="text-end">NT$ <?= number_format($data['q_tax_amount'], 0) ?></td>
                                </tr>
                                <tr class="border-top border-2">
                                    <td class="pt-2 fs-5 fw-bold text-primary">總金額</td>
                                    <td class="pt-2 fs-5 fw-bold text-end text-primary">NT$ <?= number_format($data['q_total_amount'], 0) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 備註 -->
        <?php if (!empty($data['q_notes'])): ?>
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">備註條款</h6>
                    </div>
                    <div class="card-body">
                        <?= nl2br(esc($data['q_notes'])) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 轉訂單的 JS 邏輯 (如果有) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 綁定轉訂單按鈕
        const convertBtn = document.querySelector('.btn-convert-order');
        if (convertBtn) {
            convertBtn.addEventListener('click', function() {
                if (confirm(`確定要將報價單 ${this.dataset.number} 轉為訂單嗎？`)) {
                    // 使用 GET 請求轉訂單
                    window.location.href = '<?= url_to('OrderController::createFromQuote', $data['q_id']) ?>';
                }
            });
        }
    });
</script>

<!-- 列印樣式 -->
<style>
    @media print {

        /* 隱藏非必要元素 */
        .btn,
        .alert,
        header,
        footer,
        .bi {
            display: none !important;
        }

        /* 確保背景色列印 */
        .card-header,
        .table-light,
        .bg-light {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* 調整列印版面 */
        .card {
            border: none !important;
            box-shadow: none !important;
        }

        .card-header {
            border-bottom: 2px solid #000 !important;
        }

        /* 強制分頁設定 */
        .page-break {
            page-break-before: always;
        }
    }
</style>

<?= $this->endSection() ?>