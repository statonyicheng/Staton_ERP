<?php $fmt = fn($n) => number_format((int) $n); $cls = ['未完工'=>'bg-primary','已完工'=>'bg-success','取消'=>'bg-secondary'][$wo['wo_status']] ?? 'bg-secondary'; ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-hammer me-2"></i>製令 <?= esc($wo['wo_no']) ?></h1>
    <a href="<?= url_to('WorkOrderController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>返回</a>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4"><small class="text-muted">生產母件</small><div class="fw-semibold"><?= esc($wo['p_name'] ?: '—') ?></div></div>
            <div class="col-md-2"><small class="text-muted">生產數量</small><div class="fw-bold" style="color:var(--navy);"><?= $fmt($wo['wo_qty']) ?></div></div>
            <div class="col-md-2"><small class="text-muted">領料/入庫倉</small><div><?= esc($wo['w_name'] ?: '—') ?></div></div>
            <div class="col-md-2"><small class="text-muted">日期</small><div><?= esc($wo['wo_date']) ?></div></div>
            <div class="col-md-2"><small class="text-muted">狀態</small><div><span class="badge <?= $cls ?>"><?= esc($wo['wo_status']) ?></span></div></div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h5 class="mb-3"><i class="bi bi-list-ul me-2"></i>BOM 展開用料（本製令所需）</h5>
        <?php if (empty($bom)): ?>
            <div class="alert alert-warning py-2"><i class="bi bi-exclamation-triangle me-1"></i>此母件尚未建立 BOM，完工時將僅成品入庫、不領料。可至「產品結構 BOM」建立。</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light"><tr><th>子件品號</th><th>子件品名</th><th class="text-end">單位用量</th><th class="text-end">所需數量</th><th class="text-end">現有庫存</th><th class="text-end">短缺</th></tr></thead>
                    <tbody>
                        <?php foreach ($bom as $b): ?>
                            <tr>
                                <td><code><?= esc($b['child_code'] ?? '') ?></code></td>
                                <td><?= esc($b['child_name'] ?? '—') ?></td>
                                <td class="text-end"><?= $fmt($b['bi_qty']) ?> <?= esc($b['bi_unit'] ?? '') ?></td>
                                <td class="text-end fw-semibold"><?= $fmt($b['required']) ?></td>
                                <td class="text-end"><?= $fmt($b['onhand']) ?></td>
                                <td class="text-end <?= $b['short']>0?'text-danger fw-bold':'text-success' ?>"><?= $b['short']>0 ? $fmt($b['short']) : '足夠' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($wo['wo_status'] === '未完工'): ?>
    <div class="card shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div><i class="bi bi-check2-circle me-2" style="color:var(--gold);"></i>確認完工後，系統將依 BOM 領料出庫、成品 <?= $fmt($wo['wo_qty']) ?> 入庫。</div>
            <form action="<?= url_to('WorkOrderController::complete', $wo['wo_id']) ?>" method="post" onsubmit="return confirm('確定要完工入庫嗎？此動作將扣減用料庫存並增加成品庫存。');">
                <button type="submit" class="btn btn-gold"><i class="bi bi-box-arrow-in-down me-1"></i>完工入庫</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
