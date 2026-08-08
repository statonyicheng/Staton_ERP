<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-receipt me-2"></i>訂單管理</h2>
        <a href="<?= url_to('OrderController::create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>新增訂單
        </a>
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

    <!-- 搜尋表單 -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form action="<?= url_to('OrderController::index') ?>" method="get" class="row g-3">
                <div class="col-md-10">
                    <input type="text" class="form-control" name="keyword"
                        placeholder="搜尋訂單編號或客戶名稱"
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
    <!-- 訂單列表 -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($data)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-3">尚無訂單資料</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>訂單編號</th>
                                <th>日期</th>
                                <th>客戶名稱</th>
                                <th>總金額</th>
                                <th>付款狀態</th>
                                <th>出貨狀態</th>
                                <th>訂單狀態</th>
                                <th style="width: 200px;" class="text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $item): ?>
                                <tr class="clickable-row" data-href="<?= url_to('OrderController::view', $item['o_id']) ?>" style="cursor: pointer;">
                                    <td>
                                        <strong><?= esc($item['o_number']) ?></strong>
                                    </td>
                                    <td><?= esc($item['o_date']) ?></td>
                                    <td><?= esc($item['c_name']) ?></td>
                                    <td>
                                        <span class="text-primary fw-bold"><?= number_format($item['o_total_amount']) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $paymentStatusMap = [
                                            'unpaid' => ['text' => '未收款', 'class' => 'bg-danger'],
                                            'partial' => ['text' => '部分收款', 'class' => 'bg-warning text-dark'],
                                            'paid' => ['text' => '已結清', 'class' => 'bg-success'],
                                        ];
                                        $status = $paymentStatusMap[$item['o_payment_status']] ?? ['text' => $item['o_payment_status'], 'class' => 'bg-secondary'];
                                        ?>
                                        <span class="badge <?= $status['class'] ?>"><?= $status['text'] ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $shipmentStatusMap = [
                                            'preparing' => ['text' => '備貨中', 'class' => 'bg-secondary'],
                                            'partial' => ['text' => '部分出貨', 'class' => 'bg-warning text-dark'],
                                            'shipped' => ['text' => '已全出', 'class' => 'bg-success'],
                                        ];
                                        $status = $shipmentStatusMap[$item['o_shipment_status'] ?? 'preparing'] ?? ['text' => '備貨中', 'class' => 'bg-secondary'];
                                        ?>
                                        <span class="badge <?= $status['class'] ?>"><?= $status['text'] ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusMap = [
                                            'processing' => ['text' => '處理中', 'class' => 'bg-primary'],
                                            'completed' => ['text' => '已完結', 'class' => 'bg-success'],
                                            'cancelled' => ['text' => '已取消', 'class' => 'bg-secondary'],
                                        ];
                                        $status = $statusMap[$item['o_status']] ?? ['text' => $item['o_status'], 'class' => 'bg-secondary'];
                                        ?>
                                        <span class="badge <?= $status['class'] ?>"><?= $status['text'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= url_to('OrderController::print', $item['o_id']) ?>" class="btn btn-outline-info" title="轉出廠商採購單" target="_blank">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            <a href="<?= url_to('ShipmentController::index') ?>?order_id=<?= $item['o_id'] ?>" class="btn btn-outline-info" title="出貨記錄">
                                                <i class="bi bi-truck"></i>
                                            </a>
                                            <a href="<?= url_to('OrderController::edit', $item['o_id']) ?>" class="btn btn-outline-primary" title="編輯">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger"
                                                onclick="confirmDelete('<?= url_to('OrderController::delete', $item['o_id']) ?>')"
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
                        'baseUrl' => url_to('OrderController::index'),
                        'params' => ['keyword' => $keyword ?? '']
                    ]) ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // 刪除確認對話框
    function confirmDelete(url) {
        if (confirm('確定要刪除此訂單嗎？刪除後將無法恢復。')) {
            window.location.href = url;
        }
    }

    // 點擊整列進入詳細頁面
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