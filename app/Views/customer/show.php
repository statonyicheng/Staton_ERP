<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="bi bi-person-badge me-2"></i>客戶詳細資料
            </h2>
            <div class="text-muted">
                <i class="bi bi-hash me-1"></i><?= esc($data['c_code'] ?? '') ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url_to('CustomerController::index') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left-circle me-1"></i>返回列表
            </a>
            <a href="<?= url_to('CustomerController::edit', $data['c_id']) ?>" class="btn btn-primary">
                <i class="bi bi-pencil-square me-1"></i>編輯
            </a>
        </div>
    </div>

    <div class="row g-3">
        <!-- 基本資料 -->
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-light">
                    <i class="bi bi-building me-1 text-primary"></i>基本資料
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">公司行號名稱</dt>
                        <dd class="col-sm-8"><?= esc($data['c_name'] ?? '') ?></dd>

                        <dt class="col-sm-4 text-muted">負責人</dt>
                        <dd class="col-sm-8"><?= esc($data['c_manager'] ?? '') ?></dd>

                        <dt class="col-sm-4 text-muted">統一編號</dt>
                        <dd class="col-sm-8"><?= esc($data['c_tax_id'] ?? '') ?></dd>

                        <dt class="col-sm-4 text-muted">結帳方式</dt>
                        <dd class="col-sm-8"><?= esc($data['pm_name'] ?? '') ?></dd>

                        <dt class="col-sm-4 text-muted">電話</dt>
                        <dd class="col-sm-8"><?= esc($data['c_phone'] ?? '') ?></dd>

                        <dt class="col-sm-4 text-muted">傳真</dt>
                        <dd class="col-sm-8"><?= esc($data['c_fax'] ?? '') ?></dd>

                        <dt class="col-sm-4 text-muted">Email</dt>
                        <dd class="col-sm-8">
                            <?php if (!empty($data['c_email'])): ?>
                                <a href="mailto:<?= esc($data['c_email']) ?>"><?= esc($data['c_email']) ?></a>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-4 text-muted">地址</dt>
                        <dd class="col-sm-8">
                            <?= esc($data['c_city'] ?? '') ?>
                            <?= esc($data['c_address'] ?? '') ?>
                        </dd>

                        <dt class="col-sm-4 text-muted">備註</dt>
                        <dd class="col-sm-8"><?= esc($data['c_notes'] ?? '') ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- 聯絡人 -->
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people me-1 text-primary"></i>聯絡人</span>
                    <span class="badge bg-secondary"><?= count($contacts ?? []) ?> 位</span>
                </div>
                <div class="card-body">
                    <?php if (!empty($contacts)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>姓名</th>
                                        <th>手機</th>
                                        <th>Email</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contacts as $contact): ?>
                                        <tr>
                                            <td><?= esc($contact['cc_name'] ?? '') ?></td>
                                            <td><?= esc($contact['cc_phone'] ?? '') ?></td>
                                            <td>
                                                <?php if (!empty($contact['cc_email'])): ?>
                                                    <a href="mailto:<?= esc($contact['cc_email']) ?>"><?= esc($contact['cc_email']) ?></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-muted">尚無聯絡人資料</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 系統資訊 -->
    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-light">
                    <i class="bi bi-clock-history me-1 text-primary"></i>系統資訊
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">建立時間</dt>
                        <dd class="col-sm-8"><?= esc($data['c_created_at'] ?? '') ?></dd>
                        <dt class="col-sm-4 text-muted">更新時間</dt>
                        <dd class="col-sm-8"><?= esc($data['c_updated_at'] ?? '') ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- 報價單列表 -->
    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-file-text me-1 text-primary"></i>報價單列表</span>
                    <span class="badge bg-secondary"><?= esc($quotes['total'] ?? 0) ?> 筆</span>
                </div>
                <div class="card-body">
                    <?php if (!empty($quotes['data'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>報價單號</th>
                                        <th>日期</th>
                                        <th class="text-end">總金額</th>
                                        <th>建立時間</th>
                                        <th class="text-center" style="width: 120px;">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quotes['data'] as $quote): ?>
                                        <tr>
                                            <td><strong><?= esc($quote['q_number']) ?></strong></td>
                                            <td><?= esc($quote['q_date']) ?></td>
                                            <td class="text-end"><?= number_format($quote['q_total_amount'] ?? 0) ?></td>
                                            <td><small class="text-muted"><?= esc($quote['q_created_at']) ?></small></td>
                                            <td class="text-center">
                                                <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener"
                                                   href="<?= url_to('QuoteController::edit', $quote['q_id']) ?>">
                                                    <i class="bi bi-box-arrow-up-right me-1"></i>查看
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (($quotes['totalPages'] ?? 1) > 1): ?>
                            <?= view('components/pagination', [
                                'pager' => [
                                    'currentPage' => $quotes['currentPage'],
                                    'totalPages' => $quotes['totalPages'],
                                ],
                                'baseUrl' => url_to('CustomerController::show', $data['c_id']),
                                'params' => ['oPage' => $orders['currentPage'] ?? 1],
                                'pageParam' => 'qPage',
                            ]) ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-muted">尚無報價單</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 訂單列表 -->
    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-receipt me-1 text-primary"></i>訂單列表</span>
                    <span class="badge bg-secondary"><?= esc($orders['total'] ?? 0) ?> 筆</span>
                </div>
                <div class="card-body">
                    <?php if (!empty($orders['data'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>訂單號</th>
                                        <th>日期</th>
                                        <th class="text-end">總金額</th>
                                        <th>付款狀態</th>
                                        <th>出貨狀態</th>
                                        <th>訂單狀態</th>
                                        <th>建立時間</th>
                                        <th class="text-center" style="width: 120px;">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders['data'] as $order): ?>
                                        <tr>
                                            <td><strong><?= esc($order['o_number']) ?></strong></td>
                                            <td><?= esc($order['o_date']) ?></td>
                                            <td class="text-end"><?= number_format($order['o_total_amount'] ?? 0) ?></td>
                                            <td>
                                                <?php
                                                $paymentStatusMap = [
                                                    'unpaid' => ['text' => '未收款', 'class' => 'bg-danger'],
                                                    'partial' => ['text' => '部分收款', 'class' => 'bg-warning text-dark'],
                                                    'paid' => ['text' => '已結清', 'class' => 'bg-success'],
                                                ];
                                                $pStatus = $paymentStatusMap[$order['o_payment_status'] ?? ''] ?? ['text' => $order['o_payment_status'] ?? '', 'class' => 'bg-secondary'];
                                                ?>
                                                <span class="badge <?= $pStatus['class'] ?>"><?= $pStatus['text'] ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $shipmentStatusMap = [
                                                    'preparing' => ['text' => '備貨中', 'class' => 'bg-secondary'],
                                                    'partial' => ['text' => '部分出貨', 'class' => 'bg-warning text-dark'],
                                                    'shipped' => ['text' => '已全出', 'class' => 'bg-success'],
                                                ];
                                                $sStatus = $shipmentStatusMap[$order['o_shipment_status'] ?? 'preparing'] ?? ['text' => $order['o_shipment_status'] ?? '', 'class' => 'bg-secondary'];
                                                ?>
                                                <span class="badge <?= $sStatus['class'] ?>"><?= $sStatus['text'] ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $orderStatusMap = [
                                                    'processing' => ['text' => '處理中', 'class' => 'bg-primary'],
                                                    'completed' => ['text' => '已完結', 'class' => 'bg-success'],
                                                    'cancelled' => ['text' => '已取消', 'class' => 'bg-secondary'],
                                                ];
                                                $oStatus = $orderStatusMap[$order['o_status'] ?? ''] ?? ['text' => $order['o_status'] ?? '', 'class' => 'bg-secondary'];
                                                ?>
                                                <span class="badge <?= $oStatus['class'] ?>"><?= $oStatus['text'] ?></span>
                                            </td>
                                            <td><small class="text-muted"><?= esc($order['o_created_at']) ?></small></td>
                                            <td class="text-center">
                                                <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener"
                                                   href="<?= url_to('OrderController::edit', $order['o_id']) ?>">
                                                    <i class="bi bi-box-arrow-up-right me-1"></i>查看
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (($orders['totalPages'] ?? 1) > 1): ?>
                            <?= view('components/pagination', [
                                'pager' => [
                                    'currentPage' => $orders['currentPage'],
                                    'totalPages' => $orders['totalPages'],
                                ],
                                'baseUrl' => url_to('CustomerController::show', $data['c_id']),
                                'params' => ['qPage' => $quotes['currentPage'] ?? 1],
                                'pageParam' => 'oPage',
                            ]) ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-muted">尚無訂單</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

