<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-box-arrow-in-down me-2"></i>採購單進貨 — <?= esc($po['po_no']) ?></h1>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4"><small class="text-muted">廠商</small><div class="fw-semibold"><?= esc($po['s_name'] ?: '—') ?></div></div>
            <div class="col-md-3"><small class="text-muted">採購日期</small><div><?= esc($po['po_date']) ?></div></div>
            <div class="col-md-3"><small class="text-muted">含稅總計</small><div><?= $fmt($po['po_total']) ?></div></div>
        </div>
    </div>
</div>

<form action="<?= url_to('GoodsReceiptController::doReceive', $po['po_id']) ?>" method="post">
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">入庫倉庫 <span class="text-danger">*</span></label>
                    <select class="form-select" name="w_id" required>
                        <option value="">— 請選擇 —</option>
                        <?php foreach ($warehouses as $w): ?><option value="<?= $w['w_id'] ?>"><?= esc($w['w_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">進貨日期</label>
                    <input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr><th>品名</th><th>規格</th><th class="text-end">採購數量</th><th style="width:140px;" class="text-end">進貨數量</th><th class="text-center">狀態</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($po['items'] as $it): ?>
                            <tr>
                                <td><strong><?= esc($it['poi_name']) ?></strong></td>
                                <td><?= esc($it['poi_spec'] ?: '—') ?></td>
                                <td class="text-end"><?= $fmt($it['poi_qty']) ?></td>
                                <td>
                                    <?php if ($it['poi_p_id']): ?>
                                        <input type="number" min="0" class="form-control form-control-sm text-end" name="qty[<?= $it['poi_id'] ?>]" value="<?= (int)$it['poi_qty'] ?>">
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($it['poi_p_id']): ?><span class="badge bg-primary">可入庫</span><?php else: ?><span class="badge bg-secondary" title="此品項未對應商品主檔，不進庫存">未對應商品</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1"></i>僅「已對應商品主檔」的品項會進庫存；進貨後採購單將結案。</p>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end mb-4">
        <a href="<?= url_to('GoodsReceiptController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>確認進貨入庫</button>
    </div>
</form>

<?= $this->endSection() ?>
