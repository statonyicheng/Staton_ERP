<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-check2-square me-2"></i>立沖帳 — <?= esc($acct['ac_name']) ?></h1>
    <a href="<?= url_to('OpenItemController::match') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>返回</a>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>勾選要互相沖銷的借方與貸方項目,系統以雙方未沖金額孰小者進行沖銷。</div>

<form action="<?= url_to('OpenItemController::doOffset', $acct['ac_id']) ?>" method="post">
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm"><div class="card-body">
                <h5 class="mb-3"><span class="badge bg-primary me-1">借</span>借方未沖項目</h5>
                <?php if (empty($debits)): ?><p class="text-muted">無</p><?php else: ?>
                <div class="table-responsive"><table class="table align-middle">
                    <thead class="table-light"><tr><th style="width:36px;"></th><th>傳票/日期</th><th>摘要</th><th class="text-end">未沖</th></tr></thead>
                    <tbody>
                        <?php foreach ($debits as $d): ?>
                            <tr>
                                <td><input type="checkbox" class="form-check-input dchk" name="debit[]" value="<?= $d['je_id'] ?>" data-amt="<?= (int)$d['open_amt'] ?>"></td>
                                <td><code><?= esc($d['jv_no']) ?></code><br><small class="text-muted"><?= esc($d['jv_date']) ?></small></td>
                                <td><small><?= esc($d['je_summary'] ?: '—') ?></small></td>
                                <td class="text-end fw-semibold"><?= $fmt($d['open_amt']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table></div>
                <div class="text-end fw-bold">已選借方:<span id="dSum">0</span></div>
                <?php endif; ?>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm"><div class="card-body">
                <h5 class="mb-3"><span class="badge bg-secondary me-1">貸</span>貸方未沖項目</h5>
                <?php if (empty($credits)): ?><p class="text-muted">無</p><?php else: ?>
                <div class="table-responsive"><table class="table align-middle">
                    <thead class="table-light"><tr><th style="width:36px;"></th><th>傳票/日期</th><th>摘要</th><th class="text-end">未沖</th></tr></thead>
                    <tbody>
                        <?php foreach ($credits as $c): ?>
                            <tr>
                                <td><input type="checkbox" class="form-check-input cchk" name="credit[]" value="<?= $c['je_id'] ?>" data-amt="<?= (int)$c['open_amt'] ?>"></td>
                                <td><code><?= esc($c['jv_no']) ?></code><br><small class="text-muted"><?= esc($c['jv_date']) ?></small></td>
                                <td><small><?= esc($c['je_summary'] ?: '—') ?></small></td>
                                <td class="text-end fw-semibold"><?= $fmt($c['open_amt']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table></div>
                <div class="text-end fw-bold">已選貸方:<span id="cSum">0</span></div>
                <?php endif; ?>
            </div></div>
        </div>
    </div>

    <div class="card shadow-sm mt-3"><div class="card-body d-flex justify-content-between align-items-center">
        <div>本次可沖銷金額:<span class="fs-4 fw-bold" id="offsetable" style="color:var(--gold);">0</span></div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>執行沖銷</button>
    </div></div>
</form>

<script>
(function () {
    const fmt = n => new Intl.NumberFormat().format(n || 0);
    function tally(cls) { let s = 0; document.querySelectorAll(cls + ':checked').forEach(c => s += parseInt(c.dataset.amt) || 0); return s; }
    function recalc() {
        const d = tally('.dchk'), c = tally('.cchk');
        document.getElementById('dSum') && (document.getElementById('dSum').textContent = fmt(d));
        document.getElementById('cSum') && (document.getElementById('cSum').textContent = fmt(c));
        document.getElementById('offsetable').textContent = fmt(Math.min(d, c));
    }
    document.querySelectorAll('.dchk, .cchk').forEach(el => el.addEventListener('change', recalc));
})();
</script>

<?= $this->endSection() ?>
