<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-wallet2 me-2"></i>資金餘額表</h1>
    <form method="get" class="d-flex gap-2 align-items-center">
        <select name="year" class="form-select form-select-sm" style="width:120px;" onchange="this.form.submit()">
            <?php foreach (($years ?? [$year]) as $y): ?>
                <option value="<?= $y ?>" <?= (int) $year === (int) $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
        <span class="text-muted small">年度</span>
    </form>
</div>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>
    收付實現制（含稅、以收付日期歸月）：逐月「期初 ＋ 淨變動 ＝ 期末」，僅計已收付交易。
    本年度期初 <strong><?= number_format((int) ($opening ?? 0)) ?></strong> 係承接 <?= (int) $year - 1 ?> 年底累計結餘。
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-end">
                <thead class="table-light">
                    <tr>
                        <th class="text-start">月份</th>
                        <th>期初結餘</th>
                        <th>營業收現</th>
                        <th>營業付現</th>
                        <th>本期淨變動</th>
                        <th>期末結餘</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $yin = 0; $yout = 0; foreach ($rows as $r): $yin += $r['in']; $yout += $r['out']; ?>
                        <tr>
                            <td class="text-start"><?= esc($r['ym']) ?></td>
                            <td class="text-muted"><?= $fmt($r['open']) ?></td>
                            <td class="text-success"><?= $fmt($r['in']) ?></td>
                            <td class="text-danger"><?= $r['out'] ? '(' . $fmt($r['out']) . ')' : '0' ?></td>
                            <td class="<?= $r['net'] < 0 ? 'text-danger' : '' ?>"><?= $fmt($r['net']) ?></td>
                            <td class="fw-bold"><?= $fmt($r['close']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td class="text-start">全年合計</td>
                        <td>—</td>
                        <td class="text-success"><?= $fmt($yin) ?></td>
                        <td class="text-danger"><?= $yout ? '(' . $fmt($yout) . ')' : '0' ?></td>
                        <td><?= $fmt($yin - $yout) ?></td>
                        <td><?= $fmt($rows ? end($rows)['close'] : 0) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="small text-muted mb-0"><i class="bi bi-diagram-3 me-1"></i>資料來源：交易登錄（收付狀態＝已收付）。年度期初自動承接前一年度累計結餘，跨年度可直接串接。</p>
    </div>
</div>

<?= $this->endSection() ?>
