<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-clipboard2-check me-2"></i>立沖帳餘額表</h1>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>僅列示「需立沖帳」科目的<strong>未沖銷</strong>明細(未沖 = 借或貸金額 − 已沖)。可指定立帳日期期間。</div>

<div class="card shadow-sm mb-3"><div class="card-body">
    <form method="get" class="row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label">立帳日期(起)</label><input type="date" class="form-control" name="from" value="<?= esc($from) ?>"></div>
        <div class="col-md-4"><label class="form-label">立帳日期(迄)</label><input type="date" class="form-control" name="to" value="<?= esc($to) ?>"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>查詢</button></div>
        <div class="col-md-2"><a href="<?= url_to('OpenItemController::match') ?>" class="btn btn-gold w-100"><i class="bi bi-check2-square me-1"></i>立沖帳作業</a></div>
    </form>
</div></div>

<?php if (empty($byAcct)): ?>
    <div class="card shadow-sm"><div class="card-body text-center py-5 text-muted">
        <i class="bi bi-check2-all" style="font-size:3rem;"></i>
        <p class="mt-3">此期間無未沖銷餘額(或尚未設定立沖帳科目)。</p>
        <p class="small">請至「會計科目設定」勾選需立沖帳的科目(如應收/應付帳款)。</p>
    </div></div>
<?php else: ?>
    <?php $grand = 0; foreach ($byAcct as $acId => $g): ?>
        <?php $sub = 0; ?>
        <div class="card shadow-sm mb-3"><div class="card-body">
            <h5 class="mb-3" style="color:var(--navy);"><code><?= esc($g['acct']['code']) ?></code> <?= esc($g['acct']['name']) ?></h5>
            <div class="table-responsive"><table class="table table-hover align-middle">
                <thead class="table-light"><tr><th>傳票號</th><th>立帳日期</th><th>摘要</th><th class="text-center">借/貸</th><th class="text-end">原始金額</th><th class="text-end">已沖</th><th class="text-end">未沖餘額</th></tr></thead>
                <tbody>
                    <?php foreach ($g['items'] as $it): $orig = (int)$it['je_debit'] + (int)$it['je_credit']; $dir = (int)$it['je_debit']>0 ? '借' : '貸'; $sub += (int)$it['open_amt']; ?>
                        <tr>
                            <td><code><?= esc($it['jv_no']) ?></code></td>
                            <td><small><?= esc($it['jv_date']) ?></small></td>
                            <td><?= esc($it['je_summary'] ?: $it['v_summary'] ?: '—') ?></td>
                            <td class="text-center"><?= $dir==='借'?'<span class="badge bg-primary">借</span>':'<span class="badge bg-secondary">貸</span>' ?></td>
                            <td class="text-end"><?= $fmt($orig) ?></td>
                            <td class="text-end text-muted"><?= $fmt($it['je_offset']) ?></td>
                            <td class="text-end fw-semibold" style="color:var(--navy);"><?= $fmt($it['open_amt']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot><tr class="table-light fw-bold"><td colspan="6" class="text-end">本科目未沖銷合計</td><td class="text-end" style="color:var(--navy);"><?= $fmt($sub) ?></td></tr></tfoot>
            </table></div>
        </div></div>
        <?php $grand += $sub; ?>
    <?php endforeach; ?>
    <div class="card shadow-sm"><div class="card-body d-flex justify-content-between align-items-center">
        <span class="fw-bold fs-5">全部未沖銷餘額合計</span>
        <span class="fw-bold fs-4" style="color:var(--navy);"><?= $fmt($grand) ?></span>
    </div></div>
<?php endif; ?>

<?= $this->endSection() ?>
