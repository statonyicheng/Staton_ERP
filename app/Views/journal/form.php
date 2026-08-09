<?php
$entries = $data['entries'] ?? [];
$grouped = [];
foreach ($accounts as $a) { $grouped[$a['ac_tier']][] = $a; }
$acctOptions = '<option value="">— 選會計科目 —</option>';
foreach ($grouped as $tier => $list) {
    $acctOptions .= '<optgroup label="' . esc($tier, 'attr') . '">';
    foreach ($list as $a) {
        $acctOptions .= sprintf('<option value="%d">%s %s</option>', $a['ac_id'], esc($a['ac_code'], 'attr'), esc($a['ac_name'], 'attr'));
    }
    $acctOptions .= '</optgroup>';
}
?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?> me-2"></i><?= $isEdit ? '編輯' : '新增' ?>分錄傳票</h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<form action="<?= url_to('JournalController::save') ?>" method="post">
    <?php if ($isEdit): ?><input type="hidden" name="jv_id" value="<?= $data['jv_id'] ?>"><?php endif; ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">傳票號</label>
                    <input type="text" class="form-control" name="jv_no" value="<?= esc($isEdit ? ($data['jv_no'] ?? '') : ($defaultNo ?? '')) ?>" <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">日期</label>
                    <input type="date" class="form-control" name="jv_date" value="<?= esc($data['jv_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">類別</label>
                    <select class="form-select" name="jv_type">
                        <?php foreach ($types as $t): ?><option value="<?= $t ?>" <?= ($data['jv_type'] ?? '轉帳')===$t?'selected':'' ?>><?= $t ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">
                        業務別
                        <i class="bi bi-info-circle text-muted"
                           title="四階損益分析會依業務別分欄；只動資產負債科目的傳票可留在共用/總部"></i>
                    </label>
                    <select class="form-select" name="jv_segment">
                        <?php foreach (\App\Models\TransactionModel::SEGMENTS as $code => $label): ?>
                            <option value="<?= esc($code) ?>" <?= ($data['jv_segment'] ?? 'M-0') === $code ? 'selected' : '' ?>>
                                <?= esc($code === '非營業' ? $label : "{$code} {$label}") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">摘要</label>
                    <input type="text" class="form-control" name="jv_summary" value="<?= esc($data['jv_summary'] ?? '') ?>" maxlength="255">
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>借貸分錄</h5>
                <button type="button" class="btn btn-sm btn-gold" id="addRowBtn"><i class="bi bi-plus-lg me-1"></i>新增分錄</button>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light"><tr><th style="width:34%;">會計科目</th><th>子摘要</th><th style="width:16%;" class="text-end">借方</th><th style="width:16%;" class="text-end">貸方</th><th style="width:40px;"></th></tr></thead>
                    <tbody id="itemsBody"></tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="2" class="text-end">合計</td>
                            <td class="text-end" id="sumDebit">0</td>
                            <td class="text-end" id="sumCredit">0</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="text-end fw-bold">借貸差額</td>
                            <td colspan="2" class="text-center"><span id="balanceText" class="badge bg-secondary">0</span></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end mb-4">
        <a href="<?= url_to('JournalController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
        <button type="submit" class="btn btn-primary" id="saveBtn"><i class="bi bi-check-circle me-1"></i>儲存傳票</button>
    </div>
</form>

<template id="rowTpl">
    <tr>
        <td><select class="form-select form-select-sm" name="entries[__IDX__][je_ac_id]"><?= $acctOptions ?></select></td>
        <td><input type="text" class="form-control form-control-sm" name="entries[__IDX__][je_summary]"></td>
        <td><input type="number" min="0" class="form-control form-control-sm text-end debit" name="entries[__IDX__][je_debit]" value="0"></td>
        <td><input type="number" min="0" class="form-control form-control-sm text-end credit" name="entries[__IDX__][je_credit]" value="0"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger delRow"><i class="bi bi-x"></i></button></td>
    </tr>
</template>

<script>
(function () {
    let idx = 0;
    const body = document.getElementById('itemsBody');
    const tpl = document.getElementById('rowTpl').innerHTML;
    const fmt = n => new Intl.NumberFormat().format(Math.round(n || 0));

    function recalc() {
        let d = 0, c = 0;
        body.querySelectorAll('tr').forEach(tr => {
            d += parseFloat(tr.querySelector('.debit').value) || 0;
            c += parseFloat(tr.querySelector('.credit').value) || 0;
        });
        document.getElementById('sumDebit').textContent = fmt(d);
        document.getElementById('sumCredit').textContent = fmt(c);
        const diff = d - c;
        const b = document.getElementById('balanceText');
        b.textContent = fmt(diff);
        b.className = 'badge ' + (diff === 0 && d > 0 ? 'bg-success' : 'bg-danger');
        if (diff === 0 && d > 0) b.textContent = '平衡 ✓';
    }

    function addRow(data) {
        const tr = document.createElement('tr');
        tr.innerHTML = tpl.replace(/__IDX__/g, idx++).replace(/^\s*<tr>|<\/tr>\s*$/g, '');
        body.appendChild(tr);
        if (data) {
            tr.querySelector('[name$="[je_ac_id]"]').value = data.je_ac_id || '';
            tr.querySelector('[name$="[je_summary]"]').value = data.je_summary || '';
            tr.querySelector('.debit').value = data.je_debit || 0;
            tr.querySelector('.credit').value = data.je_credit || 0;
        }
        tr.querySelector('.delRow').addEventListener('click', () => { tr.remove(); recalc(); });
        tr.querySelectorAll('.debit, .credit').forEach(el => el.addEventListener('input', recalc));
        recalc();
    }

    document.getElementById('addRowBtn').addEventListener('click', () => addRow());
    const existing = <?= json_encode($entries, JSON_UNESCAPED_UNICODE) ?>;
    if (existing.length) existing.forEach(addRow); else { addRow(); addRow(); }
})();
</script>

<?= $this->endSection() ?>
