<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<?php
function formatImagePath($path)
{
    if (empty($path)) {
        return null;
    }

    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }

    return base_url(ltrim($path, '/'));
}

$imagePath = formatImagePath($data['p_image'] ?? '');
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-box-seam me-2"></i>商品詳細</h2>
        <div class="d-flex gap-2">
            <a href="<?= url_to('ProductController::edit', $data['p_id']) ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square me-1"></i>編輯
            </a>
            <a href="<?= url_to('ProductController::index') ?>" class="btn btn-outline-secondary btn-sm">
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

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <!-- 產品圖片輪播或單圖顯示 -->
                    <?php if (!empty($images) && count($images) > 0): ?>
                        <?php if (count($images) > 1): ?>
                            <!-- 多張圖片：使用輪播 -->
                            <div id="productImageCarousel" class="carousel slide mb-3" data-bs-ride="carousel">
                                <div class="carousel-indicators">
                                    <?php foreach ($images as $index => $image): ?>
                                        <button type="button" 
                                            data-bs-target="#productImageCarousel" 
                                            data-bs-slide-to="<?= $index ?>" 
                                            class="<?= $index === 0 ? 'active' : '' ?>"
                                            aria-current="<?= $index === 0 ? 'true' : 'false' ?>"
                                            aria-label="圖片 <?= $index + 1 ?>">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <div class="carousel-inner">
                                    <?php foreach ($images as $index => $image): ?>
                                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                            <img src="<?= base_url('uploads/products/' . $data['p_id'] . '/' . esc($image['pi_name'])) ?>"
                                                class="d-block w-100 rounded"
                                                alt="<?= esc($data['p_name']) ?>"
                                                style="height: 300px; object-fit: cover; cursor: pointer;"
                                                onclick="openImageModal('<?= base_url('uploads/products/' . $data['p_id'] . '/' . esc($image['pi_name'])) ?>')">
                                            <div class="text-center mt-2">
                                                <small class="text-muted"><?= esc($image['pi_name']) ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#productImageCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">上一張</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#productImageCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">下一張</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <!-- 單張圖片 -->
                            <div class="text-center mb-3">
                                <img src="<?= base_url('uploads/products/' . $data['p_id'] . '/' . esc($images[0]['pi_name'])) ?>"
                                    class="img-fluid rounded"
                                    alt="<?= esc($data['p_name']) ?>"
                                    style="max-height: 300px; object-fit: cover; cursor: pointer;"
                                    onclick="openImageModal('<?= base_url('uploads/products/' . $data['p_id'] . '/' . esc($images[0]['pi_name'])) ?>')">
                                <div class="mt-2">
                                    <small class="text-muted"><?= esc($images[0]['pi_name']) ?></small>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- 縮圖列表 -->
                        <?php if (count($images) > 1): ?>
                            <div class="d-flex gap-2 overflow-auto pb-2" style="max-width: 100%;">
                                <?php foreach ($images as $index => $image): ?>
                                    <div class="text-center" style="min-width: 80px;">
                                        <img src="<?= base_url('uploads/products/' . $data['p_id'] . '/' . esc($image['pi_name'])) ?>"
                                            class="img-thumbnail"
                                            alt="縮圖 <?= $index + 1 ?>"
                                            style="width: 60px; height: 60px; object-fit: cover; cursor: pointer;"
                                            onclick="document.querySelector('[data-bs-slide-to=&quot;<?= $index ?>&quot;]').click()">
                                        <div class="mt-1">
                                            <small class="text-muted text-truncate d-block" style="max-width: 80px; font-size: 0.65rem;" title="<?= esc($image['pi_name']) ?>">
                                                <?= esc($image['pi_name']) ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- 無圖片 -->
                        <div class="bg-light d-flex align-items-center justify-content-center mb-3"
                            style="width: 100%; height: 300px; border-radius: 8px;">
                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-muted small text-center">
                        建立時間：<?= esc($data['p_created_at'] ?? '-') ?><br>
                        更新時間：<?= esc($data['p_updated_at'] ?? '-') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="text-muted small">產品編號</div>
                            <div class="fw-bold h5"><?= esc($data['p_code'] ?? '-') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">產品名稱</div>
                            <div class="fw-bold h5"><?= esc($data['p_name'] ?? '-') ?></div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="text-muted small">分類</div>
                            <div>
                                <?php if (!empty($data['pc_name'])): ?>
                                    <span class="badge bg-info"><i class="bi bi-tag me-1"></i><?= esc($data['pc_name']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="text-muted small">顏色/花色</div>
                            <div><?= esc($data['p_color'] ?? '-') ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">尺寸</div>
                            <div><?= esc($data['p_size'] ?? '-') ?></div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="text-muted small">售價</div>
                            <div class="fw-bold text-primary"><?= $data['p_standard_price'] !== null ? number_format($data['p_standard_price'], 2) : '-' ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">進貨成本</div>
                            <div class="fw-bold text-success"><?= $data['p_cost_price'] !== null ? number_format($data['p_cost_price'], 2) : '-' ?></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted small">詳細規格</div>
                        <div class="p-3 bg-light rounded"><?= nl2br(esc($data['p_specifications'] ?? '-')) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 圖片放大預覽 Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2" 
                    data-bs-dismiss="modal" aria-label="關閉" style="z-index: 1050;"></button>
                <img id="modalImage" src="" alt="預覽圖片" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

<script>
    // 開啟圖片放大預覽
    function openImageModal(imageUrl) {
        document.getElementById('modalImage').src = imageUrl;
        new bootstrap.Modal(document.getElementById('imageModal')).show();
    }
</script>

<?= $this->endSection() ?>