<?php
$productOptions = '<option value="">— 選子件商品 —</option>';
foreach ($products as $p) {
    $productOptions .= sprintf('<option value="%d">%s %s</option>', $p['p_id'], esc($p['p_code'], 'attr'), esc($p['p_name'], 'attr'));
}
?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-diagram-2 me-2"></i><?= $parentId ? '編輯' : '新增' ?> BOM</h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<form action="<?= site_url('bom/save') ?>" method="post">
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label class="form-label">母件商品 <span class="text-danger">*</span></label>
                    <select class="form-select" name="parent_p_id" <?= $parentId ? 'disabled' : '' ?> required>
                        <option value="">— 請選擇母件 —</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['p_id'] ?>" <?= (string)$parentId === (string)$p['p_id'] ? 'selected' : '' ?>><?= esc($p['p_code']) ?> <?= esc($p['p_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($parentId): ?><input type="hidden" name="parent_p_id" value="<?= $parentId ?>"><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>用料元件</h5>
                <button type="button" class="btn btn-sm btn-gold" id="addRowBtn"><i class="bi bi-plus-lg me-1"></i>新增元件</button>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light"><tr><th>子件商品 *</th><th style="width:15%;">單位用量</th><th style="width:15%;">單位</th><th>備註</th><th style="width:40px;"></th></tr></thead>
                    <tbody id="itemsBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end mb-4">
        <a href="<?= url_to('BomController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>儲存 BOM</button>
    </div>
</form>

<template id="rowTpl">
    <tr>
        <td><select class="form-select form-select-sm" name="items[__IDX__][bi_child_p_id]"><?= $productOptions ?></select></td>
        <td><input type="number" min="1" class="form-control form-control-sm text-end" name="items[__IDX__][bi_qty]" value="1"></td>
        <td><input type="text" class="form-control form-control-sm" name="items[__IDX__][bi_unit]"></td>
        <td><input type="text" class="form-control form-control-sm" name="items[__IDX__][bi_note]"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger delRow"><i class="bi bi-x"></i></button></td>
    </tr>
</template>

<script>
(function () {
    let idx = 0;
    const body = document.getElementById('itemsBody');
    const tpl = document.getElementById('rowTpl').innerHTML;
    function addRow(data) {
        const tr = document.createElement('tr');
        tr.innerHTML = tpl.replace(/__IDX__/g, idx++).replace(/^\s*<tr>|<\/tr>\s*$/g, '');
        body.appendChild(tr);
        if (data) {
            tr.querySelector('[name$="[bi_child_p_id]"]').value = data.bi_child_p_id || '';
            tr.querySelector('[name$="[bi_qty]"]').value = data.bi_qty || 1;
            tr.querySelector('[name$="[bi_unit]"]').value = data.bi_unit || '';
            tr.querySelector('[name$="[bi_note]"]').value = data.bi_note || '';
        }
        tr.querySelector('.delRow').addEventListener('click', () => tr.remove());
    }
    document.getElementById('addRowBtn').addEventListener('click', () => addRow());
    const existing = <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>;
    if (existing.length) existing.forEach(addRow); else addRow();
})();
</script>

<?= $this->endSection() ?>
