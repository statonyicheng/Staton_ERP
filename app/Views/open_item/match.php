<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-check2-square me-2"></i>立沖帳作業</h1>
    <a href="<?= url_to('OpenItemController::balance') ?>" class="btn btn-outline-secondary"><i class="bi bi-clipboard2-check me-1"></i>餘額表</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>選擇立沖帳科目進行逐筆借貸沖銷。借方未沖與貸方未沖相抵後,剩餘即為未沖銷餘額。</div>

<div class="card shadow-sm"><div class="card-body">
    <?php if (empty($accounts)): ?>
        <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無立沖帳科目</p><p class="small">請至「會計科目設定」勾選需立沖帳的科目。</p></div>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>科目代號</th><th>科目名稱</th><th class="text-end">借方未沖</th><th class="text-end">貸方未沖</th><th class="text-end">淨未沖餘額</th><th style="width:120px;" class="text-center">操作</th></tr></thead>
            <tbody>
                <?php foreach ($accounts as $a): $net = (int)$a['debit_open'] - (int)$a['credit_open']; ?>
                    <tr>
                        <td><code><?= esc($a['ac_code']) ?></code></td>
                        <td><strong><?= esc($a['ac_name']) ?></strong></td>
                        <td class="text-end"><?= $fmt($a['debit_open']) ?></td>
                        <td class="text-end"><?= $fmt($a['credit_open']) ?></td>
                        <td class="text-end fw-semibold" style="color:var(--navy);"><?= $fmt($net) ?></td>
                        <td class="text-center"><a href="<?= url_to('OpenItemController::account', $a['ac_id']) ?>" class="btn btn-sm btn-gold"><i class="bi bi-check2-square me-1"></i>沖銷</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div></div>

<?= $this->endSection() ?>
