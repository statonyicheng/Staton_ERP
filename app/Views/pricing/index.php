<?php $fmt = fn($n) => number_format((float) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-currency-exchange me-2"></i>商品價格管理</h1>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm mb-3"><div class="card-body">
    <form action="<?= url_to('PricingController::index') ?>" method="get" class="row g-2">
        <div class="col-md-10"><input type="text" class="form-control" name="keyword" placeholder="搜尋品號 / 品名..." value="<?= esc($keyword ?? '') ?>"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>查詢</button></div>
    </form>
</div></div>

<div class="card shadow-sm"><div class="card-body">
    <?php if (empty($data)): ?>
        <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無商品</p></div>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>品號</th><th>品名</th><th>規格</th><th class="text-end">售價</th><th class="text-end">成本</th><th class="text-end">毛利率</th><th style="width:80px;" class="text-center">操作</th></tr></thead>
            <tbody>
                <?php foreach ($data as $p): $price=(float)$p['p_standard_price']; $cost=(float)$p['p_cost_price']; $gm = $price>0 ? ($price-$cost)/$price*100 : 0; ?>
                    <tr>
                        <td><code><?= esc($p['p_code']) ?></code></td>
                        <td><strong><?= esc($p['p_name']) ?></strong></td>
                        <td><small class="text-muted"><?= esc($p['p_specifications'] ?: '—') ?></small></td>
                        <td class="text-end fw-semibold"><?= $fmt($price) ?></td>
                        <td class="text-end text-muted"><?= $fmt($cost) ?></td>
                        <td class="text-end <?= $gm<0?'text-danger':'' ?>"><?= $price>0 ? number_format($gm,1).'%' : '—' ?></td>
                        <td class="text-center"><a href="<?= url_to('PricingController::edit', $p['p_id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
<?= view('components/pagination', ['pager' => $pager, 'baseUrl' => url_to('PricingController::index'), 'params' => ['keyword' => $keyword ?? '']]) ?>
    <?php endif; ?>
</div></div>

<?= $this->endSection() ?>
