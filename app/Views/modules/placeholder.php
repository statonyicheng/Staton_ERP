<?= $this->extend('_layout') ?>

<?= $this->section('content') ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb small text-muted mb-2">
        <li class="breadcrumb-item"><?= esc($subsystem) ?></li>
        <li class="breadcrumb-item active" aria-current="page"><?= esc($title) ?></li>
    </ol>
</nav>

<h1><?= esc($title) ?></h1>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-gold">規劃 / 建置中</span>
                    <span class="text-muted small"><?= esc($subsystem) ?> 子系統</span>
                </div>
                <p class="mb-4" style="font-size:1.02rem; line-height:1.9;"><?= esc($description) ?></p>

                <?php if (!empty($features)): ?>
                    <h6 class="fw-bold mb-3" style="color:var(--navy);">規劃功能</h6>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($features as $f): ?>
                            <li class="list-group-item bg-transparent px-0 py-2 d-flex align-items-center" style="color:var(--ink); border-color:var(--line);">
                                <i class="bi bi-check2-circle me-2" style="color:var(--gold);"></i><?= esc($f) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3" style="color:var(--navy);">
                    <i class="bi bi-info-circle me-1" style="color:var(--gold);"></i>模組狀態
                </h6>
                <p class="small text-muted mb-2">此模組屬於「仕坦登 ERP」完整套件的規劃範圍，介面與資料流已納入系統架構，功能開發中。</p>
                <hr>
                <p class="small mb-1"><strong>所屬子系統：</strong><?= esc($subsystem) ?></p>
                <p class="small mb-3"><strong>模組代碼：</strong><code><?= esc($key) ?></code></p>
                <a href="<?= site_url('/') ?>" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-arrow-left"></i> 返回儀表板
                </a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
