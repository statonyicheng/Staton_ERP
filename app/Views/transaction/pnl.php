<?php
$segMap = $segMap ?? [];
$segs = $report['segments'];
$m = $report['matrix'];
$fmt = fn($n) => number_format((int) $n);
$pct = function ($num, $den) {
    if (!$den) return '—';
    return number_format($num / $den * 100, 1) . '%';
};
// 取某列（tier 或 gp 陣列）某欄值
$cell = fn($arr, $c) => (int) ($arr[$c] ?? 0);
$cols = array_merge($segs, ['total']);
?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<?php
$monthsAsc = $months; sort($monthsAsc);
$basis = $basis ?? 'net';
$years = $years ?? [];
?>
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <h1 class="mb-0"><i class="bi bi-graph-up me-2"></i>四階損益分析</h1>
    <form method="get" class="d-flex gap-2 align-items-center flex-wrap">
        <label class="small text-muted mb-0">期間</label>
        <select name="from" class="form-select form-select-sm" style="min-width:118px;">
            <?php foreach ($monthsAsc as $mo): ?>
                <option value="<?= $mo ?>" <?= $from === $mo ? 'selected' : '' ?>><?= $mo ?></option>
            <?php endforeach; ?>
        </select>
        <span class="text-muted">~</span>
        <select name="to" class="form-select form-select-sm" style="min-width:118px;">
            <?php foreach ($monthsAsc as $mo): ?>
                <option value="<?= $mo ?>" <?= $to === $mo ? 'selected' : '' ?>><?= $mo ?></option>
            <?php endforeach; ?>
        </select>
        <select name="basis" class="form-select form-select-sm" style="min-width:104px;">
            <option value="net" <?= $basis === 'net' ? 'selected' : '' ?>>未稅</option>
            <option value="gross" <?= $basis === 'gross' ? 'selected' : '' ?>>含稅</option>
        </select>
        <button class="btn btn-primary btn-sm"><i class="bi bi-search"></i> 查詢</button>
        <a href="<?= site_url('pnl') ?>" class="btn btn-outline-secondary btn-sm">全部期間</a>
    </form>
</div>

<div class="d-flex gap-2 flex-wrap mb-3">
    <?php foreach ($years as $y): ?>
        <a class="btn btn-sm <?= ($from === "{$y}-01" && $to === "{$y}-12") ? 'btn-gold' : 'btn-outline-primary' ?>"
           href="<?= site_url('pnl') ?>?from=<?= $y ?>-01&to=<?= $y ?>-12&basis=<?= esc($basis, 'url') ?>"><?= $y ?> 年度</a>
    <?php endforeach; ?>
</div>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>
    四階損益模型（<?= $basis === 'gross' ? '含稅' : '未稅' ?>、收付實現制）。財務預算標準：一階毛利率 65%、二階費用率 25%、四階毛利率 12.5%。
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h5 class="mb-3"><?= esc($ym) ?> 損益表 <small class="text-muted">Profit &amp; Loss</small></h5>
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-end">
                <thead class="table-light">
                    <tr>
                        <th class="text-start">項目</th>
                        <?php foreach ($segs as $s): ?>
                            <th><?= $s ?><br><small class="fw-normal text-muted"><?= esc($segMap[$s] ?? '') ?></small></th>
                        <?php endforeach; ?>
                        <th>合計</th>
                        <th>% 佔收入</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rev = $m['營業收入']; ?>
                    <tr>
                        <td class="text-start fw-semibold">一階收入</td>
                        <?php foreach ($cols as $c): ?><td><?= $fmt($cell($rev, $c)) ?></td><?php endforeach; ?>
                        <td>100%</td>
                    </tr>
                    <tr>
                        <td class="text-start ps-3 text-muted">一階成本</td>
                        <?php foreach ($cols as $c): ?><td class="text-muted"><?= $fmt($cell($m['一階成本'], $c)) ?></td><?php endforeach; ?>
                        <td><?= $pct($cell($m['一階成本'], 'total'), $cell($rev, 'total')) ?></td>
                    </tr>
                    <tr class="table-light fw-bold">
                        <td class="text-start">一階毛利</td>
                        <?php foreach ($cols as $c): ?><td><?= $fmt($cell($report['gp1'], $c)) ?></td><?php endforeach; ?>
                        <td><?= $pct($cell($report['gp1'], 'total'), $cell($rev, 'total')) ?></td>
                    </tr>

                    <tr>
                        <td class="text-start ps-3 text-muted">二階費用</td>
                        <?php foreach ($cols as $c): ?><td class="text-muted"><?= $fmt($cell($m['二階費用'], $c)) ?></td><?php endforeach; ?>
                        <td><?= $pct($cell($m['二階費用'], 'total'), $cell($rev, 'total')) ?></td>
                    </tr>
                    <tr class="fw-bold">
                        <td class="text-start">二階毛利</td>
                        <?php foreach ($cols as $c): ?><td><?= $fmt($cell($report['gp2'], $c)) ?></td><?php endforeach; ?>
                        <td><?= $pct($cell($report['gp2'], 'total'), $cell($rev, 'total')) ?></td>
                    </tr>

                    <tr>
                        <td class="text-start ps-3 text-muted">三階費用</td>
                        <?php foreach ($cols as $c): ?><td class="text-muted"><?= $fmt($cell($m['三階費用'], $c)) ?></td><?php endforeach; ?>
                        <td><?= $pct($cell($m['三階費用'], 'total'), $cell($rev, 'total')) ?></td>
                    </tr>
                    <tr class="fw-bold">
                        <td class="text-start">三階毛利</td>
                        <?php foreach ($cols as $c): ?><td><?= $fmt($cell($report['gp3'], $c)) ?></td><?php endforeach; ?>
                        <td><?= $pct($cell($report['gp3'], 'total'), $cell($rev, 'total')) ?></td>
                    </tr>

                    <tr>
                        <td class="text-start ps-3 text-muted">四階費用</td>
                        <?php foreach ($cols as $c): ?><td class="text-muted"><?= $fmt($cell($m['四階費用'], $c)) ?></td><?php endforeach; ?>
                        <td><?= $pct($cell($m['四階費用'], 'total'), $cell($rev, 'total')) ?></td>
                    </tr>
                    <tr style="background:rgba(244,183,2,0.14);" class="fw-bold">
                        <td class="text-start">四階毛利（≈營業利益）</td>
                        <?php foreach ($cols as $c): ?><td><?= $fmt($cell($report['gp4'], $c)) ?></td><?php endforeach; ?>
                        <td><?= $pct($cell($report['gp4'], 'total'), $cell($rev, 'total')) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="small text-muted mb-0"><i class="bi bi-diagram-3 me-1"></i>
            資料來源：交易登錄（收付）之進損益科目。商業模式：
            <?php $sm = []; foreach ($segs as $s) { $sm[] = $s . ' ' . ($segMap[$s] ?? ''); } echo esc(implode('　', $sm)); ?>。
            共用人事與管理費用目前全數歸於 M-0，可在交易登錄逐筆調整商業模式以取得各線真實損益。
        </p>
    </div>
</div>

<?= $this->endSection() ?>
