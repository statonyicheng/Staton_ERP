<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="container-fluid mt-4">
    <!-- Header & Actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-truck me-2"></i>出貨單內容</h2>
        <div class="d-flex gap-2">
            <!-- 暫時沒有 Print 功能，先註解或保留連結樣式 -->
            <!--
            <a href="#" class="btn btn-success btn-sm" target="_blank">
                <i class="bi bi-printer me-1"></i>列印
            </a>
            -->
            <a href="<?= url_to('ShipmentController::edit', $data['s_id']) ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square me-1"></i>編輯
            </a>
            <a href="<?= url_to('ShipmentController::index') . '?order_id=' . $data['s_o_id'] ?>" class="btn btn-outline-secondary btn-sm">
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
        <!-- 出貨單資訊 & 客戶資料 -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light py-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0 text-primary fw-bold">出貨單號：<?= esc($data['s_number']) ?></h5>
                        </div>
                        <div class="col-md-6 text-md-end text-muted small">
                            建立時間：<?= esc($data['s_created_at'] ?? date('Y-m-d H:i:s')) ?>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- 左側：基本資料 -->
                        <div class="col-md-6 border-end">
                            <h6 class="text-secondary mb-3 border-bottom pb-2">基本資料</h6>
                            <div class="row">
                                <div class="col-6">
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr>
                                            <td class="text-muted" width="80">出貨日期</td>
                                            <td class="fw-bold"><?= esc($data['s_date']) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">訂單編號</td>
                                            <td>
                                                <a href="<?= url_to('OrderController::view', $data['s_o_id']) ?>" class="text-decoration-none">
                                                    <?= esc($data['o_number']) ?>
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">訂單日期</td>
                                            <td><?= esc($data['order_date'] ?? '-') ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-6">
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr>
                                            <td class="text-muted" width="80">出貨狀態</td>
                                            <td>
                                                <?php
                                                $statusLabel = \App\Models\ShipmentModel::$statusMap[$data['s_status'] ?? 0] ?? '未知';
                                                $statusClass = 'bg-secondary';
                                                if (($data['s_status'] ?? 0) == \App\Models\ShipmentModel::STATUS_SHIPPED || ($data['s_status'] ?? 0) == \App\Models\ShipmentModel::STATUS_ARRIVED) {
                                                    $statusClass = 'bg-success';
                                                } elseif (($data['s_status'] ?? 0) > 1) {
                                                    $statusClass = 'bg-primary';
                                                }
                                                ?>
                                                <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">售後狀態</td>
                                            <td>
                                                <?php
                                                $asLabel = \App\Models\ShipmentModel::$afterSalesStatusMap[$data['s_after_sales_status'] ?? 1] ?? '正常';
                                                $asClass = 'bg-light text-dark border';
                                                if (($data['s_after_sales_status'] ?? 1) == \App\Models\ShipmentModel::AFTERSALES_PROCESSING) {
                                                    $asClass = 'bg-warning text-dark';
                                                } elseif (($data['s_after_sales_status'] ?? 1) == \App\Models\ShipmentModel::AFTERSALES_COMPLETED) {
                                                    $asClass = 'bg-info text-dark';
                                                }
                                                ?>
                                                <span class="badge <?= $asClass ?>"><?= $asLabel ?></span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- 右側：客戶資料 -->
                        <div class="col-md-6">
                            <h6 class="text-secondary mb-3 border-bottom pb-2">客戶資料</h6>
                            <div class="row">
                                <div class="col-6">
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr>
                                            <td class="text-muted" width="80">客戶名稱</td>
                                            <td class="fw-bold"><?= esc($data['c_name'] ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">統一編號</td>
                                            <td><?= esc($data['c_tax_id'] ?? '-') ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-6">
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr>
                                            <td class="text-muted" width="80">聯絡人</td>
                                            <td><?= esc($data['cc_name'] ?? '-') ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">聯絡電話</td>
                                            <td><?= esc($data['cc_phone'] ?? '-') ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 出貨項目明細 -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0 card-title">出貨內容</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 80px;">圖片</th>
                                    <th>商品資訊</th>
                                    <th class="text-center" style="width: 120px;">訂單數量</th>
                                    <th class="text-center" style="width: 120px;">本次出貨</th>
                                    <th class="text-center" style="width: 120px;">剩餘未出</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($data['items'])): ?>
                                    <?php foreach ($data['items'] as $item): ?>
                                        <?php
                                        // 處理圖片路徑
                                        $imagePath = !empty($item['p_image']) ? base_url($item['p_image']) : base_url('assets/images/placeholder.png');

                                        $specs = array_filter([
                                            ($item['oi_style'] ?? '') ? "款式:{$item['oi_style']}" : null,
                                            ($item['oi_color'] ?? '') ? "顏色:{$item['oi_color']}" : null,
                                            ($item['oi_size'] ?? '') ? "尺寸:{$item['oi_size']}" : null,
                                        ]);
                                        $specString = implode(' / ', $specs);

                                        // 計算剩餘未出 = 訂單數量 - 總已出貨 (注意：total_shipped 已包含本次出貨)
                                        $remaining = ($item['order_quantity'] ?? 0) - ($item['total_shipped'] ?? 0);
                                        ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="ratio ratio-1x1 bg-light border rounded" style="width: 60px;">
                                                    <img src="<?= esc($imagePath) ?>" alt=""
                                                        class="img-fluid object-fit-cover rounded">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= esc($item['p_name'] ?? '未知商品') ?></div>
                                                <div class="d-flex align-items-center gap-2 mt-1">
                                                    <?php if (!empty($item['pc_name'])): ?>
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10 rounded-pill px-2">
                                                            <?= esc($item['pc_name']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="small text-muted"><?= esc($item['p_code'] ?? '') ?></span>
                                                </div>
                                                <?php if (!empty($item['oi_supplier'])): ?>
                                                    <div class="small text-secondary mt-1">
                                                        <i class="bi bi-shop me-1"></i>供應商：<?= esc($item['oi_supplier']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($specString): ?>
                                                    <div class="small text-info mt-1">
                                                        <i class="bi bi-tags me-1"></i><?= esc($specString) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border fs-6">
                                                    <?= $item['order_quantity'] ?? 0 ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary fs-6">
                                                    <?= $item['si_quantity'] ?? 0 ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-secondary border">
                                                    <?= max(0, $remaining) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">無出貨項目</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 備註 -->
        <?php if (!empty($data['s_notes'])): ?>
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">備註</h6>
                    </div>
                    <div class="card-body">
                        <?= nl2br(esc($data['s_notes'])) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    @media print {

        .btn,
        .alert,
        header,
        footer,
        .bi {
            display: none !important;
        }

        .card-header,
        .table-light,
        .bg-light,
        .badge {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }

        .card-header {
            border-bottom: 2px solid #000 !important;
        }
    }
</style>

<?= $this->endSection() ?>