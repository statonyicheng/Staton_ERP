<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-diagram-2 me-2"></i>產品結構 BOM</h1>
    <a href="<?= site_url('bom/manage') ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>新增 BOM</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>建立母件商品的用料結構（子件與用量），供製令領料與 MRP 需求展算使用。</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form action="<?= url_to('BomController::index') ?>" method="get" class="row g-2">
            <div class="col-md-10"><input type="text" class="form-control" name="keyword" placeholder="搜尋母件品號 / 品名..." value="<?= esc($keyword ?? '') ?>"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>查詢</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($data)): ?>
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚未建立任何 BOM</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>母件品號</th><th>母件品名</th><th class="text-center">元件數</th><th style="width:150px;" class="text-center">操作</th></tr></thead>
                    <tbody>
                        <?php foreach ($data as $r): ?>
                            <tr>
                                <td><code><?= esc($r['p_code'] ?? '') ?></code></td>
                                <td><strong><?= esc($r['p_name'] ?? '—') ?></strong></td>
                                <td class="text-center"><span class="badge bg-primary"><?= (int)$r['cnt'] ?></span></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= site_url('bom/manage/' . $r['bi_parent_p_id']) ?>" class="btn btn-outline-primary" title="編輯"><i class="bi bi-pencil"></i></a>
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete('<?= site_url('bom/delete/' . $r['bi_parent_p_id']) ?>')" title="刪除"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
<?= view('components/pagination', ['pager' => $pager, 'baseUrl' => url_to('BomController::index'), 'params' => ['keyword' => $keyword ?? '']]) ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
