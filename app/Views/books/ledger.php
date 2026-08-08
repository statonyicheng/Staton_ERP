<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<?php
$fmt = fn($n) => $n ? number_format(abs((int) $n)) : '—';
$accounts = null;   // 總分類帳不需要科目下拉（本來就逐科目列出）
$acId = null;
?>

<h1><i class="bi bi-journal-bookmark me-2"></i>總分類帳</h1>

<?= view('books/_filter', compact('from', 'to', 'range', 'acId', 'accounts')) ?>

<?php
$tOpenD = $tOpenC = $tD = $tC = $tCloseD = $tCloseC = 0;
foreach ($rows as $r) {
    $r['opening'] > 0 ? $tOpenD += $r['opening'] : $tOpenC += -$r['opening'];
    $tD += $r['debit']; $tC += $r['credit'];
    $r['closing'] > 0 ? $tCloseD += $r['closing'] : $tCloseC += -$r['closing'];
}
?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="mb-0"><?= esc($from) ?> ~ <?= esc($to) ?>　<small class="text-muted">共 <?= count($rows) ?> 個科目</small></h5>
            <div>
                <span class="badge <?= $tD === $tC ? 'bg-success' : 'bg-danger' ?>">
                    本期借貸<?= $tD === $tC ? '平衡' : '不平衡' ?>
                </span>
                <span class="badge <?= $tCloseD === $tCloseC ? 'bg-success' : 'bg-danger' ?>">
                    期末借貸<?= $tCloseD === $tCloseC ? '平衡' : '不平衡' ?>
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2" style="width:100px;">科目代碼</th>
                        <th rowspan="2" style="width:200px;">會計科目</th>
                        <th rowspan="2" style="width:90px;">類別</th>
                        <th colspan="2" class="text-center">期初餘額</th>
                        <th colspan="2" class="text-center">本期發生額</th>
                        <th colspan="2" class="text-center">期末餘額</th>
                    </tr>
                    <tr>
                        <th class="text-end" style="width:110px;">金額</th>
                        <th class="text-center" style="width:50px;">借/貸</th>
                        <th class="text-end" style="width:110px;">借方</th>
                        <th class="text-end" style="width:110px;">貸方</th>
                        <th class="text-end" style="width:110px;">金額</th>
                        <th class="text-center" style="width:50px;">借/貸</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">此期間無資料</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><small><code><?= esc($r['ac_code']) ?></code></small></td>
                            <td><?= esc($r['ac_name']) ?></td>
                            <td><small class="text-muted"><?= esc($r['ac_category']) ?></small></td>
                            <td class="text-end text-muted"><?= $fmt($r['opening']) ?></td>
                            <td class="text-center"><small><?= $r['opening'] ? esc($r['opening_side']) : '' ?></small></td>
                            <td class="text-end"><?= $fmt($r['debit']) ?></td>
                            <td class="text-end"><?= $fmt($r['credit']) ?></td>
                            <td class="text-end fw-semibold"><?= $fmt($r['closing']) ?></td>
                            <td class="text-center"><small class="fw-bold"><?= $r['closing'] ? esc($r['closing_side']) : '' ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="3" class="text-end">合計</td>
                        <td class="text-end"><?= number_format($tOpenD) ?>／<?= number_format($tOpenC) ?></td>
                        <td class="text-center"><small>借/貸</small></td>
                        <td class="text-end"><?= number_format($tD) ?></td>
                        <td class="text-end"><?= number_format($tC) ?></td>
                        <td class="text-end"><?= number_format($tCloseD) ?>／<?= number_format($tCloseC) ?></td>
                        <td class="text-center"><small>借/貸</small></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="small text-muted mb-0"><i class="bi bi-diagram-3 me-1"></i>
            各科目「期初餘額 ＋ 本期借方 − 本期貸方 ＝ 期末餘額」。
            資產與支出科目為借餘、負債權益與收入科目為貸餘；全部科目的借餘合計應等於貸餘合計。
        </p>
    </div>
</div>

<?= $this->endSection() ?>
