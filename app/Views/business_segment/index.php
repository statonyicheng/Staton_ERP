<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>業務別設定</h1>
    <a href="<?= url_to('BusinessSegmentController::create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>新增業務別
    </a>
</div>

<div class="alert alert-info py-2">
    <i class="bi bi-info-circle me-2"></i>
    業務別＝公司的商業模式劃分。<strong>四階損益分析會依這裡的設定分欄</strong>，
    每筆交易與分錄傳票都要指定一個業務別，才知道這筆收入或費用屬於哪條業務線。
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="d-flex gap-2">
            <input type="text" class="form-control" name="keyword" placeholder="搜尋代號、名稱或定義…"
                   value="<?= esc($keyword ?? '') ?>">
            <button class="btn btn-dark"><i class="bi bi-search me-1"></i>搜尋</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 10%;">代號</th>
                        <th style="width: 16%;">業務別名稱</th>
                        <th>商業模式定義</th>
                        <th class="text-center" style="width: 10%;">四階損益</th>
                        <th class="text-center" style="width: 10%;">狀態</th>
                        <th class="text-center" style="width: 10%;">已使用</th>
                        <th class="text-center" style="width: 10%;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">尚無業務別資料</td></tr>
                    <?php else: foreach ($data as $row): ?>
                        <tr>
                            <td><strong class="text-primary"><?= esc($row['bs_code']) ?></strong></td>
                            <td><strong><?= esc($row['bs_name']) ?></strong></td>
                            <td>
                                <?php if (!empty($row['bs_definition'])): ?>
                                    <small class="text-muted"><?= esc($row['bs_definition']) ?></small>
                                <?php else: ?>
                                    <small class="text-muted fst-italic">尚未填寫定義</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if (!empty($row['bs_in_pl'])): ?>
                                    <span class="badge bg-success bg-opacity-75">列入分欄</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if (!empty($row['bs_is_active'])): ?>
                                    <span class="badge bg-primary bg-opacity-75">啟用</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">停用</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php $n = $usage[$row['bs_code']] ?? 0; ?>
                                <span class="<?= $n > 0 ? 'fw-bold' : 'text-muted' ?>"><?= number_format($n) ?> 筆</span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url_to('BusinessSegmentController::edit', $row['bs_id']) ?>"
                                       class="btn btn-outline-primary" title="編輯"><i class="bi bi-pencil"></i></a>
                                    <a href="<?= url_to('BusinessSegmentController::delete', $row['bs_id']) ?>"
                                       class="btn btn-outline-danger" title="刪除"
                                       onclick="return confirm('確定刪除「<?= esc($row['bs_name'], 'js') ?>」？已被交易使用的業務別無法刪除，請改用停用。')">
                                        <i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="px-3 pb-2">
            <?= view('components/pagination', [
                'pager' => $pager,
                'baseUrl' => url_to('BusinessSegmentController::index'),
                'params' => ['keyword' => $keyword ?? ''],
            ]) ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
