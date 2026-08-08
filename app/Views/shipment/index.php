<?= $this->extend('_layout') ?>

<?= $this->section('content') ?>
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-truck me-2"></i>出貨單管理</h2>
        <div class="d-flex gap-2">
            <?php if ($orderId && $orderInfo): ?>
                <?php
                // 檢查是否還有未出貨的項目
                $hasRemainingItems = false;
                if (isset($orderInfo['items'])) {
                    foreach ($orderInfo['items'] as $item) {
                        $remaining = ($item['oi_quantity'] ?? 0) - ($item['oi_shipped_quantity'] ?? 0);
                        if ($remaining > 0) {
                            $hasRemainingItems = true;
                            break;
                        }
                    }
                }
                ?>
                <?php if ($hasRemainingItems): ?>
                    <a href="<?= url_to('ShipmentController::create', $orderId) ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>新增出貨單
                    </a>
                <?php endif; ?>
                <a href="<?= url_to('OrderController::index') ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>返回訂單列表
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 訂單資訊 -->
    <?php if ($orderId && $orderInfo): ?>
        <div class="alert alert-info mb-3">
            <h5 class="mb-2"><i class="bi bi-info-circle me-2"></i>訂單資訊</h5>
            <div class="row">
                <div class="col-md-3">
                    <strong>訂單編號：</strong><?= esc($orderInfo['o_number']) ?>
                </div>
                <div class="col-md-3">
                    <strong>客戶名稱：</strong><?= esc($orderInfo['c_name']) ?>
                </div>
                <div class="col-md-3">
                    <strong>訂單日期：</strong><?= esc($orderInfo['o_date']) ?>
                </div>
                <div class="col-md-3">
                    <strong>總金額：</strong><span class="text-primary fw-bold"><?= number_format($orderInfo['o_total_amount']) ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

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

    <!-- 搜尋表單 -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form action="<?= url_to('ShipmentController::index') ?>" method="get" class="row g-3">
                <?php if ($orderId): ?>
                    <input type="hidden" name="order_id" value="<?= esc($orderId) ?>">
                <?php endif; ?>
                <div class="col-md-10">
                    <input type="text" class="form-control" name="keyword"
                        placeholder="搜尋出貨單號或訂單編號"
                        value="<?= esc($keyword ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>搜尋
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 出貨單列表 -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($data)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-3">尚無出貨單資料</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>出貨單號</th>
                                <th>訂單編號</th>
                                <th>出貨日期</th>
                                <th>備註</th>
                                <th>出貨狀態</th>
                                <th>售後狀態</th>
                                <th style="width: 120px;" class="text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $item): ?>
                                <tr class="clickable-row" data-href="<?= url_to('ShipmentController::view', $item['s_id']) ?>" style="cursor: pointer;">
                                    <td><strong><?= esc($item['s_number']) ?></strong></td>
                                    <td>
                                        <a href="<?= url_to('OrderController::edit', $item['s_o_id']) ?>" class="text-decoration-none">
                                            <?= esc($item['o_number']) ?>
                                        </a>
                                    </td>
                                    <td><?= esc($item['s_date']) ?></td>
                                    <td>
                                        <?php
                                        $notes = $item['s_notes'] ?? '';
                                        if (!empty($notes)) {
                                            // 顯示前50個字元，超過則顯示省略號
                                            $displayNotes = mb_strlen($notes) > 50 ? mb_substr($notes, 0, 50) . '...' : $notes;
                                            echo '<span title="' . esc($notes) . '">' . esc($displayNotes) . '</span>';
                                        } else {
                                            echo '<span class="text-muted">無</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusLabel = \App\Models\ShipmentModel::$statusMap[$item['s_status'] ?? 0] ?? '未知';
                                        $statusClass = 'bg-secondary';
                                        if (($item['s_status'] ?? 0) == \App\Models\ShipmentModel::STATUS_SHIPPED || ($item['s_status'] ?? 0) == \App\Models\ShipmentModel::STATUS_ARRIVED) {
                                            $statusClass = 'bg-success';
                                        } elseif (($item['s_status'] ?? 0) > 1) {
                                            $statusClass = 'bg-primary';
                                        }
                                        ?>
                                        <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $asLabel = \App\Models\ShipmentModel::$afterSalesStatusMap[$item['s_after_sales_status'] ?? 1] ?? '正常';
                                        $asClass = 'bg-light text-dark border';
                                        if (($item['s_after_sales_status'] ?? 1) == \App\Models\ShipmentModel::AFTERSALES_PROCESSING) {
                                            $asClass = 'bg-warning text-dark';
                                        } elseif (($item['s_after_sales_status'] ?? 1) == \App\Models\ShipmentModel::AFTERSALES_COMPLETED) {
                                            $asClass = 'bg-info text-dark';
                                        }
                                        ?>
                                        <span class="badge <?= $asClass ?>"><?= $asLabel ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= url_to('ShipmentController::edit', $item['s_id']) ?>" class="btn btn-outline-primary" title="編輯">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger"
                                                onclick="confirmDelete('<?= url_to('ShipmentController::delete', $item['s_id']) ?>')"
                                                title="刪除">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 分頁 -->
                <?php if ($pager['totalPages'] > 1): ?>
                    <?= view('components/pagination', [
                        'pager' => $pager,
                        'baseUrl' => url_to('ShipmentController::index'),
                        'params' => [
                            'keyword' => $keyword ?? '',
                            'order_id' => $orderId ?? ''
                        ]
                    ]) ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function confirmDelete(url) {
        if (confirm('確定要刪除此出貨單嗎？刪除後將無法恢復。')) {
            window.location.href = url;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('.clickable-row');

        rows.forEach(row => {
            row.addEventListener('click', function(e) {
                // 如果點擊的是按鈕或按鈕內的元素，不要跳轉
                if (e.target.closest('.btn-group') || e.target.closest('button') || e.target.closest('a.btn')) {
                    return;
                }

                // 跳轉到詳細頁面
                window.location.href = this.dataset.href;
            });
        });
    });
</script>

<?= $this->endSection() ?>