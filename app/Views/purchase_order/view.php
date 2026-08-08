<?php $fmt = fn($n) => number_format((int) $n); $st = $data['po_status']; $cls = ['未結案'=>'bg-primary','部分進貨'=>'bg-info text-dark','已結案'=>'bg-success','作廢'=>'bg-secondary'][$st] ?? 'bg-secondary'; ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-file-earmark-text me-2"></i>採購單 <?= esc($data['po_no']) ?></h1>
    <div class="d-flex gap-2">
        <a href="<?= url_to('PurchaseOrderController::edit', $data['po_id']) ?>" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>編輯</a>
        <a href="<?= url_to('PurchaseOrderController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>返回</a>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3"><small class="text-muted">廠商</small><div class="fw-semibold"><?= esc($data['s_name'] ?: '—') ?></div></div>
            <div class="col-md-2"><small class="text-muted">聯絡人</small><div><?= esc($data['s_contact'] ?: '—') ?></div></div>
            <div class="col-md-2"><small class="text-muted">採購日期</small><div><?= esc($data['po_date']) ?></div></div>
            <div class="col-md-2"><small class="text-muted">預計到貨</small><div><?= esc($data['po_expected_date'] ?: '—') ?></div></div>
            <div class="col-md-3"><small class="text-muted">狀態</small><div><span class="badge <?= $cls ?>"><?= esc($st) ?></span></div></div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr><th style="width:40px;">#</th><th>品名</th><th>規格</th><th class="text-end">數量</th><th>單位</th><th class="text-end">單價</th><th class="text-end">金額</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($data['items'] as $i => $it): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= esc($it['poi_name']) ?></strong></td>
                            <td><?= esc($it['poi_spec'] ?: '—') ?></td>
                            <td class="text-end"><?= $fmt($it['poi_qty']) ?></td>
                            <td><?= esc($it['poi_unit'] ?: '—') ?></td>
                            <td class="text-end"><?= $fmt($it['poi_price']) ?></td>
                            <td class="text-end"><?= $fmt($it['poi_amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($data['items'])): ?><tr><td colspan="7" class="text-center text-muted py-3">無明細</td></tr><?php endif; ?>
                </tbody>
                <tfoot>
                    <tr><td colspan="6" class="text-end">未稅小計</td><td class="text-end"><?= $fmt($data['po_subtotal']) ?></td></tr>
                    <tr><td colspan="6" class="text-end">營業稅</td><td class="text-end"><?= $fmt($data['po_tax']) ?></td></tr>
                    <tr class="fw-bold"><td colspan="6" class="text-end">含稅總計</td><td class="text-end fs-5" style="color:var(--navy);"><?= $fmt($data['po_total']) ?></td></tr>
                </tfoot>
            </table>
        </div>
        <?php if ($data['po_note']): ?><p class="text-muted mb-0"><i class="bi bi-sticky me-1"></i><?= esc($data['po_note']) ?></p><?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
