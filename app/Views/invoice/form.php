<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-receipt-cutoff me-2"></i><?= $isEdit ? '編輯' : '開立' ?>發票</h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm"><div class="card-body p-4">
    <form action="<?= $isEdit ? url_to('InvoiceController::update', $data['inv_id']) : url_to('InvoiceController::store') ?>" method="post">
        <?= \App\Libraries\EditGuard::field($data['inv_updated_at'] ?? null) ?>
        <div class="row">
            <div class="col-md-3 mb-3"><label class="form-label">發票號碼</label>
                <input type="text" class="form-control" name="inv_number" value="<?= esc($isEdit ? ($data['inv_number'] ?? '') : ($defaultNo ?? '')) ?>" <?= $isEdit?'readonly':'' ?>
                       placeholder="<?= $isEdit ? '' : '存檔後自動配發' ?>">
                <?php if (! $isEdit): ?>
                    <div class="form-text">留白即可，存檔當下才配發號碼，確保連續、不留需作廢申報的空號。</div>
                <?php endif; ?>
            </div>
            <div class="col-md-3 mb-3"><label class="form-label">開立日期</label>
                <input type="date" class="form-control" name="inv_date" value="<?= esc($data['inv_date'] ?? date('Y-m-d')) ?>"></div>
            <div class="col-md-6 mb-3"><label class="form-label">客戶</label>
                <select class="form-select" name="inv_c_id" id="custSel">
                    <option value="">— 請選擇（或直接填買方）—</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['c_id'] ?>" data-name="<?= esc($c['c_name'], 'attr') ?>" data-tax="<?= esc($c['c_tax_id'] ?? '', 'attr') ?>" <?= (string)($data['inv_c_id'] ?? '')===(string)$c['c_id']?'selected':'' ?>><?= esc($c['c_code']) ?> <?= esc($c['c_name']) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="col-md-6 mb-3"><label class="form-label">買方名稱</label>
                <input type="text" class="form-control" name="inv_buyer" id="buyer" value="<?= esc($data['inv_buyer'] ?? '') ?>"></div>
            <div class="col-md-3 mb-3"><label class="form-label">買方統編</label>
                <input type="text" class="form-control" name="inv_buyer_tax" id="buyerTax" value="<?= esc($data['inv_buyer_tax'] ?? '') ?>"></div>
            <div class="col-md-3 mb-3"><label class="form-label">未稅金額</label>
                <input type="number" class="form-control" name="inv_amount" id="amt" value="<?= (int)($data['inv_amount'] ?? 0) ?>"></div>
            <div class="col-md-3 mb-3"><label class="form-label">營業稅(5%)</label>
                <input type="number" class="form-control" name="inv_tax" id="tax" value="<?= (int)($data['inv_tax'] ?? 0) ?>"></div>
            <div class="col-md-3 mb-3"><label class="form-label">含稅總計</label>
                <input type="text" class="form-control" id="total" value="0" readonly></div>
            <div class="col-md-12 mb-3"><label class="form-label">備註</label>
                <input type="text" class="form-control" name="inv_note" value="<?= esc($data['inv_note'] ?? '') ?>"></div>
        </div>
        <div class="d-flex gap-2 justify-content-end">
            <a href="<?= url_to('InvoiceController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>儲存</button>
        </div>
    </form>
</div></div>

<script>
(function () {
    const amt = document.getElementById('amt'), tax = document.getElementById('tax'), total = document.getElementById('total');
    const recalc = () => { total.value = new Intl.NumberFormat().format((parseInt(amt.value)||0) + (parseInt(tax.value)||0)); };
    const autoTax = () => { tax.value = Math.round((parseInt(amt.value)||0) * 0.05); recalc(); };
    amt.addEventListener('input', autoTax);
    tax.addEventListener('input', recalc);
    document.getElementById('custSel').addEventListener('change', function () {
        const o = this.options[this.selectedIndex];
        if (o.value) { document.getElementById('buyer').value = o.dataset.name || ''; document.getElementById('buyerTax').value = o.dataset.tax || ''; }
    });
    recalc();
})();
</script>

<?= $this->endSection() ?>
