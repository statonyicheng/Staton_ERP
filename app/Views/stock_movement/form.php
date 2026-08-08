<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-plus-circle me-2"></i>新增庫存異動</h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="<?= url_to('StockMovementController::save') ?>" method="post">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">異動日期</label>
                    <input type="date" class="form-control" name="sm_date" value="<?= old('sm_date', date('Y-m-d')) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">異動類別 <span class="text-danger">*</span></label>
                    <select class="form-select" name="sm_type" id="typeSel" required>
                        <option value="">— 請選擇 —</option>
                        <optgroup label="入庫">
                            <?php foreach ($inTypes as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
                        </optgroup>
                        <optgroup label="出庫">
                            <?php foreach ($outTypes as $t): ?><option value="<?= $t ?>"><?= $t ?></option><?php endforeach; ?>
                        </optgroup>
                        <optgroup label="調撥">
                            <option value="調撥">調撥（倉對倉）</option>
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">商品 <span class="text-danger">*</span></label>
                    <select class="form-select" name="sm_p_id" required>
                        <option value="">— 請選擇 —</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['p_id'] ?>" <?= (string)old('sm_p_id')===(string)$p['p_id']?'selected':'' ?>><?= esc($p['p_code']) ?> <?= esc($p['p_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label" id="wLabel">倉庫 <span class="text-danger">*</span></label>
                    <select class="form-select" name="sm_w_id">
                        <option value="">— 請選擇 —</option>
                        <?php foreach ($warehouses as $w): ?><option value="<?= $w['w_id'] ?>"><?= esc($w['w_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3" id="toWBox" style="display:none;">
                    <label class="form-label">目標倉庫 <span class="text-danger">*</span></label>
                    <select class="form-select" name="to_w_id">
                        <option value="">— 請選擇 —</option>
                        <?php foreach ($warehouses as $w): ?><option value="<?= $w['w_id'] ?>"><?= esc($w['w_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">數量 <span class="text-danger">*</span></label>
                    <input type="number" min="1" class="form-control" name="sm_qty" value="<?= old('sm_qty', 1) ?>" required>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label">備註</label>
                    <input type="text" class="form-control" name="sm_note" value="<?= old('sm_note', '') ?>" maxlength="255">
                </div>
            </div>
            <div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>儲存後將即時更新對應倉庫的在庫量（調撥為來源倉扣、目標倉增）。</div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= url_to('StockMovementController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>取消</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>儲存異動</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('typeSel').addEventListener('change', function () {
        const isTransfer = this.value === '調撥';
        document.getElementById('toWBox').style.display = isTransfer ? '' : 'none';
        document.getElementById('wLabel').innerHTML = isTransfer ? '來源倉庫 <span class="text-danger">*</span>' : '倉庫 <span class="text-danger">*</span>';
    });
</script>

<?= $this->endSection() ?>
