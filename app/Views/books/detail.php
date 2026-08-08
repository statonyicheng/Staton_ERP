<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<?php $fmt = fn($n) => $n ? number_format((int) $n) : ''; ?>

<h1><i class="bi bi-journal-richtext me-2"></i>明細分類帳</h1>

<?= view('books/_filter', compact('from', 'to', 'range', 'acId', 'accounts')) ?>

<?php if (empty($groups)): ?>
    <div class="card shadow-sm"><div class="card-body text-center text-muted py-5">此期間無資料</div></div>
<?php endif; ?>

<?php foreach ($groups as $g): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <h5 class="mb-0">
                    <code><?= esc($g['account']['ac_code']) ?></code>
                    <?= esc($g['account']['ac_name']) ?>
                    <small class="text-muted">（<?= esc($g['account']['ac_category']) ?>）</small>
                </h5>
                <div>
                    <span class="badge bg-secondary">期初 <?= number_format(abs($g['opening'])) ?> <?= $g['opening'] ? esc($g['opening_side']) : '' ?></span>
                    <span class="badge bg-primary">借 <?= number_format($g['debit']) ?></span>
                    <span class="badge bg-primary">貸 <?= number_format($g['credit']) ?></span>
                    <span class="badge bg-gold">期末 <?= number_format(abs($g['closing'])) ?> <?= $g['closing'] ? esc($g['closing_side']) : '' ?></span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:100px;">日期</th>
                            <th style="width:130px;">傳票號</th>
                            <th>摘要</th>
                            <th class="text-end" style="width:110px;">借方</th>
                            <th class="text-end" style="width:110px;">貸方</th>
                            <th class="text-end" style="width:120px;">累計餘額</th>
                            <th class="text-center" style="width:50px;">借/貸</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-light">
                            <td colspan="5" class="text-end text-muted"><small>期初餘額</small></td>
                            <td class="text-end text-muted"><?= number_format(abs($g['opening'])) ?></td>
                            <td class="text-center"><small><?= $g['opening'] ? esc($g['opening_side']) : '' ?></small></td>
                        </tr>
                        <?php foreach ($g['rows'] as $r): ?>
                            <tr>
                                <td><small><?= esc($r['jv_date']) ?></small></td>
                                <td><small><code><?= esc($r['jv_no']) ?></code></small></td>
                                <td><small><?= esc($r['je_summary'] ?: $r['jv_summary']) ?></small></td>
                                <td class="text-end"><?= $fmt($r['je_debit']) ?></td>
                                <td class="text-end"><?= $fmt($r['je_credit']) ?></td>
                                <td class="text-end"><?= number_format(abs($r['balance'])) ?></td>
                                <td class="text-center"><small><?= esc($r['side']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold" style="background:rgba(244,183,2,0.14);">
                            <td colspan="3" class="text-end">本期合計 / 期末餘額</td>
                            <td class="text-end"><?= number_format($g['debit']) ?></td>
                            <td class="text-end"><?= number_format($g['credit']) ?></td>
                            <td class="text-end"><?= number_format(abs($g['closing'])) ?></td>
                            <td class="text-center"><?= $g['closing'] ? esc($g['closing_side']) : '' ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<p class="small text-muted"><i class="bi bi-diagram-3 me-1"></i>
    逐科目列出期間內每一筆分錄並計算累計餘額。未指定科目時列出所有有異動的科目；
    科目數多時建議先在上方篩選單一科目，報表會比較好讀。
</p>

<?= $this->endSection() ?>
