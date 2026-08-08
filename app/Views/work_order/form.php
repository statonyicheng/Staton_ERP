<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?> me-2"></i><?= $isEdit ? '編輯' : '新增' ?>製令</h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="<?= $isEdit ? url_to('WorkOrderController::update', $data['wo_id']) : url_to('WorkOrderController::store') ?>" method="post">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">製令單號</label>
                    <input type="text" class="form-control" name="wo_no" value="<?= esc($isEdit ? ($data['wo_no'] ?? '') : ($defaultNo ?? '')) ?>" <?= $isEdit ? 'readonly' : '' ?>>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">生產母件 <span class="text-danger">*</span></label>
                    <select class="form-select" name="wo_p_id" required>
                        <option value="">— 請選擇 —</option>
                        <?php foreach ($products as $p): ?><option value="<?= $p['p_id'] ?>" <?= (string)($data['wo_p_id'] ?? '')===(string)$p['p_id']?'selected':'' ?>><?= esc($p['p_code']) ?> <?= esc($p['p_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">生產數量 <span class="text-danger">*</span></label>
                    <input type="number" min="1" class="form-control" name="wo_qty" value="<?= old('wo_qty', $data['wo_qty'] ?? 1) ?>" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">領料/入庫倉 <span class="text-danger">*</span></label>
                    <select class="form-select" name="wo_w_id">
                        <option value="">— 請選擇 —</option>
                        <?php foreach ($warehouses as $w): ?><option value="<?= $w['w_id'] ?>" <?= (string)($data['wo_w_id'] ?? '')===(string)$w['w_id']?'selected':'' ?>><?= esc($w['w_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">製令日期</label>
                    <input type="date" class="form-control" name="wo_date" value="<?= old('wo_date', $data['wo_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">預計完工</label>
                    <input type="date" class="form-control" name="wo_due_date" value="<?= old('wo_due_date', $data['wo_due_date'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">備註</label>
                    <input type="text" class="form-control" name="wo_note" value="<?= old('wo_note', $data['wo_note'] ?? '') ?>" maxlength="255">
                </div>
            </div>
            <div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>完工時系統將依母件 BOM 自動領料出庫、成品入庫。請先於「產品結構 BOM」建立母件用料。</div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= url_to('WorkOrderController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>儲存</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
