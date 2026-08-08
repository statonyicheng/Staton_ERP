<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-tags me-2"></i>產品分類管理</h2>
        <a href="<?= url_to('ProductCategoryController::create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>新增分類
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
            <form action="<?= url_to('ProductCategoryController::index') ?>" method="get" class="row g-3">
                <div class="col-md-10">
                    <input type="text" class="form-control" name="keyword"
                        placeholder="搜尋分類名稱..."
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

    <!-- 分類列表 -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($data)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-3">尚無分類資料</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>分類名稱</th>
                                <th>建立時間</th>
                                <th style="width: 150px;" class="text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= esc($item['pc_name']) ?></strong>
                                    </td>
                                    <td><small class="text-muted"><?= esc($item['pc_created_at']) ?></small></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= url_to('ProductCategoryController::edit', $item['pc_id']) ?>"
                                                class="btn btn-outline-primary" title="編輯">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger"
                                                onclick="confirmDelete('<?= url_to('ProductCategoryController::delete', $item['pc_id']) ?>')"
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
                <?php if ($pager['totalPages'] > 1): ?>ㄋ
                    <?= view('components/pagination', [
                        'pager' => $pager,
                        'baseUrl' => url_to('ProductCategoryController::index'),
                        'params' => ['keyword' => $keyword ?? '']
                    ]) ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 刪除確認 Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>確認刪除
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
            </div>
            <div class="modal-body">
                <p>確定要刪除這個分類嗎？此操作無法復原。</p>
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle me-2"></i>
                    如果此分類已被產品使用，將無法刪除。
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">確認刪除</button>
            </div>
        </div>
    </div>
</div>

<script>
// 刪除確認函數
function confirmDelete(url) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const confirmBtn = document.getElementById('confirmDeleteBtn');

    confirmBtn.onclick = function() {
        window.location.href = url;
    };

    modal.show();
}
</script>

<?= $this->endSection() ?>
