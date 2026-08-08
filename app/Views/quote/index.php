<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-file-text me-2"></i>報價單管理</h2>
        <a href="<?= url_to('QuoteController::create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>新增報價單
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
            <form action="<?= url_to('QuoteController::index') ?>" method="get" class="row g-3">
                <div class="col-md-10">
                    <input type="text" class="form-control" name="keyword"
                        placeholder="搜尋報價單號、客戶名稱..."
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

    <!-- 報價單列表 -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($data)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-3">尚無報價單資料</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>報價單號</th>
                                <th>客戶名稱</th>
                                <th>報價日期</th>
                                <th>有效日期</th>
                                <th>總金額</th>
                                <th>建立時間</th>
                                <th style="width: 150px;" class="text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $item): ?>
                                <tr class="table-row-link" data-href="<?= url_to('QuoteController::view', $item['q_id']) ?>" style="cursor: pointer;">
                                    <td><strong><?= esc($item['q_number']) ?></strong></td>
                                    <td><?= esc($item['customer_name']) ?></td>
                                    <td><?= esc($item['q_date']) ?></td>
                                    <td><?= esc($item['q_valid_date'] ?? '-') ?></td>
                                    <td>
                                        <span class="text-primary fw-bold"><?= number_format($item['q_total_amount']) ?></span>
                                    </td>
                                    <td><small class="text-muted"><?= esc($item['q_created_at']) ?></small></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= url_to('QuoteController::print', $item['q_id']) ?>" class="btn btn-outline-info" title="列印報價單" target="_blank">
                                                <i class="bi bi-printer"></i>
                                            </a>
											<?php if (empty($item['q_o_id'])): ?>
                                                <a href="<?= url_to('OrderController::createFromQuote', $item['q_id']) ?>"
                                                    class="btn btn-outline-success"
                                                    title="轉成訂單"
                                                    onclick="event.stopPropagation(); return confirm('確定要將此報價單轉換為訂單嗎？')">
                                                    <i class="bi bi-arrow-right-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?= url_to('QuoteController::edit', $item['q_id']) ?>" 
                                               class="btn btn-outline-primary" 
                                               title="編輯"
                                               onclick="event.stopPropagation();">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger"
                                                onclick="event.stopPropagation(); confirmDelete('<?= url_to('QuoteController::delete', $item['q_id']) ?>')"
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
                        'baseUrl' => url_to('QuoteController::index'),
                        'params' => ['keyword' => $keyword ?? '']
                    ]) ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>