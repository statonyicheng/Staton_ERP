<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<?php $fmt = fn($n) => $n ? number_format((int) $n) : ''; ?>

<h1><i class="bi bi-journal-text me-2"></i>日記帳</h1>

<?= view('books/_filter', compact('from', 'to', 'range', 'acId', 'accounts')) ?>

<?php
$totD = 0; $totC = 0;
foreach ($rows as $r) { $totD += (int) $r['je_debit']; $totC += (int) $r['je_credit']; }
$balanced = $totD === $totC;
?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="mb-0"><?= esc($from) ?> ~ <?= esc($to) ?>　<small class="text-muted">共 <?= number_format(count($rows)) ?> 筆分錄</small></h5>
            <div>
                <span class="badge bg-primary">借方合計 <?= number_format($totD) ?></span>
                <span class="badge bg-primary">貸方合計 <?= number_format($totC) ?></span>
                <?php if ($balanced): ?>
                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> 借貸平衡</span>
                <?php else: ?>
                    <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> 不平衡，差 <?= number_format(abs($totD - $totC)) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:100px;">日期</th>
                        <th style="width:130px;">傳票號</th>
                        <th style="width:110px;">科目代碼</th>
                        <th style="width:180px;">會計科目</th>
                        <th>摘要</th>
                        <th class="text-end" style="width:120px;">借方</th>
                        <th class="text-end" style="width:120px;">貸方</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">此期間無分錄</td></tr>
                    <?php endif; ?>
                    <?php $lastVoucher = null; foreach ($rows as $r): ?>
                        <?php $newVoucher = $lastVoucher !== $r['jv_no']; $lastVoucher = $r['jv_no']; ?>
                        <tr<?= $newVoucher ? ' style="border-top:2px solid #e6e8ee;"' : '' ?>>
                            <td><small><?= $newVoucher ? esc($r['jv_date']) : '' ?></small></td>
                            <td><small><?= $newVoucher ? '<code>' . esc($r['jv_no']) . '</code>' : '' ?></small></td>
                            <td><small><?= esc($r['ac_code'] ?? '') ?></small></td>
                            <td><?= esc($r['ac_name'] ?? '—') ?></td>
                            <td><small class="text-muted"><?= esc($r['je_summary'] ?: $r['jv_summary']) ?></small></td>
                            <td class="text-end"><?= $fmt($r['je_debit']) ?></td>
                            <td class="text-end"><?= $fmt($r['je_credit']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="5" class="text-end">合計</td>
                        <td class="text-end"><?= number_format($totD) ?></td>
                        <td class="text-end"><?= number_format($totC) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="small text-muted mb-0"><i class="bi bi-diagram-3 me-1"></i>
            序時簿：依日期順序記錄每一筆分錄。同一張傳票的多筆分錄以上方分隔線分組，借貸總額必須相等。
        </p>
    </div>
</div>

<?= $this->endSection() ?>
