<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-currency-exchange me-2"></i>編輯商品價格</h1>

<div class="card shadow-sm"><div class="card-body p-4">
    <form action="<?= url_to('PricingController::update', $data['p_id']) ?>" method="post">
        <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">品號</label><input type="text" class="form-control" value="<?= esc($data['p_code']) ?>" readonly></div>
            <div class="col-md-6 mb-3"><label class="form-label">品名</label><input type="text" class="form-control" value="<?= esc($data['p_name']) ?>" readonly></div>
            <div class="col-md-6 mb-3"><label class="form-label">標準售價</label><input type="number" class="form-control" name="p_standard_price" value="<?= (int)$data['p_standard_price'] ?>"></div>
            <div class="col-md-6 mb-3"><label class="form-label">標準成本</label><input type="number" step="0.01" class="form-control" name="p_cost_price" value="<?= (float)$data['p_cost_price'] ?>"></div>
        </div>
        <div class="d-flex gap-2 justify-content-end">
            <a href="<?= url_to('PricingController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>儲存</button>
        </div>
    </form>
</div></div>

<?= $this->endSection() ?>
