<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-people me-2"></i>客戶資料管理</h2>
        <a href="<?= url_to('CustomerController::create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>新增客戶
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
            <form action="<?= url_to('CustomerController::index') ?>" method="get" class="row g-3">
                <div class="col-md-10">
                    <input type="text" class="form-control" name="keyword"
                        placeholder="搜尋客戶編號、公司名稱、負責人、聯絡人、電話、Email、地址、統一編號..."
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

    <!-- 客戶列表 -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($data)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-3">尚無客戶資料</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>客戶編號</th>
                                <th>公司行號名稱</th>
                                <th>聯絡電話</th>
                                <th>建立時間</th>
                                <th>更新時間</th>
                                <th style="width: 150px;" class="text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $item): ?>
                                <tr class="table-row-link" data-href="<?= url_to('CustomerController::show', $item['c_id']) ?>">
                                    <td><strong class="text-primary"><?= esc($item['c_code']) ?></strong></td>
                                    <td><strong><?= esc($item['c_name']) ?></strong></td>
                                    <td><?= esc($item['c_phone']) ?></td>
                                    <td><small class="text-muted"><?= esc($item['c_created_at']) ?></small></td>
                                    <td><small class="text-muted"><?= esc($item['c_updated_at']) ?></small></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= url_to('CustomerController::edit', $item['c_id']) ?>"
                                                class="btn btn-outline-primary" title="編輯" onclick="event.stopPropagation();">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger"
                                                onclick="event.stopPropagation(); confirmDelete('<?= url_to('CustomerController::delete', $item['c_id']) ?>')"
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
                        'baseUrl' => url_to('CustomerController::index'),
                        'params' => ['keyword' => $keyword ?? '']
                    ]) ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>