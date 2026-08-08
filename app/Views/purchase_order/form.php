<?php
$items = $data['items'] ?? [];
// 商品下拉選項（含 data-* 供自動帶入）
$productOptions = '<option value="">— 選商品(可留空) —</option>';
foreach ($products as $p) {
    $productOptions .= sprintf(
        '<option value="%d" data-name="%s" data-spec="%s" data-unit="%s" data-price="%d">%s</option>',
        $p['p_id'], esc($p['p_name'], 'attr'), esc($p['p_specifications'] ?? '', 'attr'),
        esc($p['p_unit'] ?? '', 'attr'), (int) $p['p_standard_price'], esc($p['p_name'])
    );
}
?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?> me-2"></i><?= $isEdit ? '編輯' : '新增' ?>採購單</h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<form action="<?= url_to('PurchaseOrderController::save') ?>" method="post">
    <?php if ($isEdit): ?><input type="hidden" name="po_id" value="<?= $data['po_id'] ?>"><?php endif; ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">採購單號</label>
                    <input type="text" class="form-control" name="po_no" value="<?= esc($isEdit ? $data['po_no'] : ($defaultNo ?? '')) ?>" <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">廠商</label>
                    <select class="form-select" name="po_s_id">
                        <option value="">— 請選擇 —</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['s_id'] ?>" <?= (string)($data['po_s_id'] ?? '') === (string)$s['s_id'] ? 'selected' : '' ?>><?= esc($s['s_code']) ?> <?= esc($s['s_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">採購日期</label>
                    <input type="date" class="form-control" name="po_date" value="<?= esc($data['po_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">預計到貨</label>
                    <input type="date" class="form-control" name="po_expected_date" value="<?= esc($data['po_expected_date'] ?? '') ?>">
                </div>
                <div class="col-md-1 mb-3">
                    <label class="form-label">狀態</label>
                    <select class="form-select" name="po_status">
                        <?php foreach (\App\Models\PurchaseOrderModel::STATUSES as $st): ?>
                            <option value="<?= $st ?>" <?= ($data['po_status'] ?? '未結案') === $st ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>採購明細</h5>
                <button type="button" class="btn btn-sm btn-gold" id="addRowBtn"><i class="bi bi-plus-lg me-1"></i>新增品項</button>
            </div>
            <div class="table-responsive">
                <table class="table align-middle" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:18%;">選商品</th><th>品名 *</th><th style="width:12%;">規格</th>
                            <th style="width:9%;">數量</th><th style="width:8%;">單位</th><th style="width:11%;">單價</th>
                            <th style="width:12%;" class="text-end">金額</th><th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row justify-content-end">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2"><span>未稅小計</span><strong id="subtotalText">0</strong></div>
                    <div class="d-flex justify-content-between mb-2 align-items-center">
                        <span>營業稅</span>
                        <input type="number" class="form-control form-control-sm text-end" name="po_tax" id="taxInput" style="width:120px;" value="<?= (int)($data['po_tax'] ?? 0) ?>">
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between"><span class="fw-bold">含稅總計</span><strong class="fs-5" id="totalText" style="color:var(--navy);">0</strong></div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">備註</label>
        <input type="text" class="form-control" name="po_note" value="<?= esc($data['po_note'] ?? '') ?>">
    </div>

    <div class="d-flex gap-2 justify-content-end mb-4">
        <a href="<?= url_to('PurchaseOrderController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>儲存採購單</button>
    </div>
</form>

<template id="rowTpl">
    <tr>
        <td><select class="form-select form-select-sm prod-select"><?= $productOptions ?></select></td>
        <td><input type="text" class="form-control form-control-sm" name="items[__IDX__][poi_name]"></td>
        <td><input type="text" class="form-control form-control-sm" name="items[__IDX__][poi_spec]"></td>
        <td><input type="number" class="form-control form-control-sm text-end qty" name="items[__IDX__][poi_qty]" value="0"></td>
        <td><input type="text" class="form-control form-control-sm" name="items[__IDX__][poi_unit]"></td>
        <td><input type="number" class="form-control form-control-sm text-end price" name="items[__IDX__][poi_price]" value="0"></td>
        <td class="text-end amount">0</td>
        <td><input type="hidden" class="pid" name="items[__IDX__][poi_p_id]"><button type="button" class="btn btn-sm btn-outline-danger delRow"><i class="bi bi-x"></i></button></td>
    </tr>
</template>

<script>
(function () {
    let idx = 0;
    const body = document.getElementById('itemsBody');
    const tpl = document.getElementById('rowTpl').innerHTML;
    const fmt = n => new Intl.NumberFormat().format(Math.round(n || 0));

    function recalc() {
        let sub = 0;
        body.querySelectorAll('tr').forEach(tr => {
            const q = parseFloat(tr.querySelector('.qty').value) || 0;
            const p = parseFloat(tr.querySelector('.price').value) || 0;
            const amt = q * p;
            tr.querySelector('.amount').textContent = fmt(amt);
            sub += amt;
        });
        const tax = parseFloat(document.getElementById('taxInput').value) || 0;
        document.getElementById('subtotalText').textContent = fmt(sub);
        document.getElementById('totalText').textContent = fmt(sub + tax);
    }

    function addRow(data) {
        const html = tpl.replace(/__IDX__/g, idx++);
        const tr = document.createElement('tr');
        tr.innerHTML = html.replace(/^\s*<tr>|<\/tr>\s*$/g, '');
        body.appendChild(tr);
        if (data) {
            tr.querySelector('[name$="[poi_name]"]').value = data.poi_name || '';
            tr.querySelector('[name$="[poi_spec]"]').value = data.poi_spec || '';
            tr.querySelector('.qty').value = data.poi_qty || 0;
            tr.querySelector('[name$="[poi_unit]"]').value = data.poi_unit || '';
            tr.querySelector('.price').value = data.poi_price || 0;
            tr.querySelector('.pid').value = data.poi_p_id || '';
        }
        tr.querySelector('.delRow').addEventListener('click', () => { tr.remove(); recalc(); });
        tr.querySelectorAll('.qty, .price').forEach(el => el.addEventListener('input', recalc));
        tr.querySelector('.prod-select').addEventListener('change', function () {
            const o = this.options[this.selectedIndex];
            if (!o.value) return;
            tr.querySelector('[name$="[poi_name]"]').value = o.dataset.name || '';
            tr.querySelector('[name$="[poi_spec]"]').value = o.dataset.spec || '';
            tr.querySelector('[name$="[poi_unit]"]').value = o.dataset.unit || '';
            tr.querySelector('.price').value = o.dataset.price || 0;
            tr.querySelector('.pid').value = o.value;
            recalc();
        });
        recalc();
    }

    document.getElementById('addRowBtn').addEventListener('click', () => addRow());
    document.getElementById('taxInput').addEventListener('input', recalc);

    const existing = <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>;
    if (existing.length) existing.forEach(addRow); else { addRow(); addRow(); }
})();
</script>

<?= $this->endSection() ?>
