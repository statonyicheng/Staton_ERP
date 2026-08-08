<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-box-seam me-2"></i>商品資料管理</h2>
        <a href="<?= url_to('ProductController::create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>新增商品
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
            <form action="<?= url_to('ProductController::index') ?>" method="get" class="row g-3">
                <div class="col-md-10">
                    <input type="text" class="form-control" name="keyword"
                        placeholder="搜尋產品編號、產品名稱、供應商或分類..."
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

    <!-- 商品列表 -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($data)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-3">尚無商品資料</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">圖片</th>
                                <th>產品編號</th>
                                <th>產品名稱</th>
                                <th>分類</th>
                                <th>供應商</th>
                                <th class="text-end">售價</th>
                                <th style="width: 120px;" class="text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $item): ?>
                                <tr class="table-row-link" data-href="<?= url_to('ProductController::show', $item['p_id']) ?>">
                                    <td>
                                        <?php if (!empty($item['first_image'])): ?>
                                            <img src="<?= base_url('uploads/products/' . $item['p_id'] . '/' . esc($item['first_image'])) ?>"
                                                alt="<?= esc($item['p_name']) ?>"
                                                class="img-thumbnail"
                                                style="width: 60px; height: 60px; object-fit: cover;"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="bg-light align-items-center justify-content-center"
                                                style="width: 60px; height: 60px; border-radius: 4px; display: none;">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center"
                                                style="width: 60px; height: 60px; border-radius: 4px;">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= esc($item['p_code']) ?></strong></td>
                                    <td>
                                        <div>
                                            <div class="fw-bold"><?= esc($item['p_name']) ?></div>
                                            <?php if (!empty($item['p_color']) || !empty($item['p_size'])): ?>
                                                <small class="text-muted">
                                                    <?php
                                                    $specs = [];
                                                    if (!empty($item['p_color'])) $specs[] = esc($item['p_color']);
                                                    if (!empty($item['p_size'])) $specs[] = esc($item['p_size']);
                                                    echo implode(' / ', $specs);
                                                    ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($item['pc_name'])): ?>
                                            <span class="badge bg-info">
                                                <i class="bi bi-tag me-1"></i>
                                                <?= esc($item['pc_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($item['p_supplier'])): ?>
                                            <small class="text-muted"><?= esc($item['p_supplier']) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($item['p_standard_price']): ?>
                                            <span class="text-primary fw-bold">
                                                <?= number_format($item['p_standard_price']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= url_to('ProductController::edit', $item['p_id']) ?>"
                                                class="btn btn-outline-primary" title="編輯" onclick="event.stopPropagation();">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger"
                                                onclick="event.stopPropagation(); confirmDelete('<?= url_to('ProductController::delete', $item['p_id']) ?>')"
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
                        'baseUrl' => url_to('ProductController::index'),
                        'params' => ['keyword' => $keyword ?? '']
                    ]) ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>


<?= $this->endSection() ?>