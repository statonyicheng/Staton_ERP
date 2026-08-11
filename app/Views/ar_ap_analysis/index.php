<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<?php
$fmt = fn($n) => number_format((int) $n);
$label = $isAr ? '應收' : '應付';
$verb = $isAr ? '收款' : '付款';
$partnerLabel = $isAr ? '客戶' : '廠商';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>帳齡與周轉分析</h1>
    <div class="btn-group">
        <a href="?type=AR&year=<?= $year ?>" class="btn btn-<?= $isAr ? 'primary' : 'outline-primary' ?>">應收</a>
        <a href="?type=AP&year=<?= $year ?>" class="btn btn-<?= $isAr ? 'outline-primary' : 'primary' ?>">應付</a>
    </div>
</div>

<div class="alert alert-info py-2">
    <i class="bi bi-info-circle me-2"></i>
    逾期天數是<strong>依該<?= $partnerLabel ?>的付款條件推算到期日</strong>之後才算的
    —— 月結客戶當月開的帳本來就還沒到期，用傳票日期直接算會把它們誤判成逾期。
    付款條件在 <a href="<?= site_url('payment-method') ?>">結帳方式管理</a> 設定，
    再到<?= $partnerLabel ?>資料指定給各<?= $partnerLabel ?>。
</div>

<!-- ── 周轉率 ───────────────────────────────── -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-arrow-repeat me-1"></i><?= $label ?>帳款周轉率</strong>
        <form method="get" class="d-flex align-items-center gap-2 mb-0">
            <input type="hidden" name="type" value="<?= esc($type) ?>">
            <label class="mb-0 small text-muted">年度</label>
            <select name="year" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="card-body">
        <div class="row g-3 text-center">
            <div class="col-md-3">
                <div class="text-muted small"><?= esc($turnover['flowLabel']) ?></div>
                <div class="fs-4 fw-bold"><?= $fmt($turnover['flow']) ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">平均<?= $label ?>餘額</div>
                <div class="fs-4 fw-bold"><?= $fmt($turnover['average']) ?></div>
                <div class="text-muted" style="font-size:.75rem;">
                    期初 <?= $fmt($turnover['opening']) ?> ／ 期末 <?= $fmt($turnover['closing']) ?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">周轉率（次／年）</div>
                <div class="fs-4 fw-bold text-primary">
                    <?= $turnover['rate'] !== null ? number_format($turnover['rate'], 2) : '—' ?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small"><?= esc($turnover['daysLabel']) ?></div>
                <div class="fs-4 fw-bold" style="color:var(--gold);">
                    <?= $turnover['days'] !== null ? $turnover['days'] . ' 天' : '—' ?>
                </div>
            </div>
        </div>
        <?php if ($turnover['rate'] === null): ?>
            <div class="text-muted small mt-3">
                <i class="bi bi-dash-circle me-1"></i>
                該年度平均<?= $label ?>餘額為 0（沒有賒帳），周轉率無法計算 —— 不硬算一個數字出來。
            </div>
        <?php else: ?>
            <div class="text-muted small mt-3">
                <i class="bi bi-lightbulb me-1"></i>
                周轉率＝<?= esc($turnover['flowLabel']) ?> ÷ 平均<?= $label ?>餘額；
                <?= esc($turnover['daysLabel']) ?>＝365 ÷ 周轉率。
                數字越大代表<?= $verb ?>越快<?= $isAr ? '，資金回收壓力越小' : '' ?>。
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── 帳齡 ───────────────────────────────── -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <strong><i class="bi bi-hourglass-split me-1"></i>依<?= $partnerLabel ?>的帳齡分析</strong>
        <span class="text-muted small ms-2">
            未<?= $verb ?>總額 <?= $fmt($summary['open']) ?>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:16%;"><?= $partnerLabel ?></th>
                        <th style="width:14%;">付款條件</th>
                        <?php foreach ($buckets as $b): ?>
                            <th class="text-end"><?= esc($b) ?></th>
                        <?php endforeach; ?>
                        <th class="text-end" style="width:10%;">未<?= $verb ?>合計</th>
                        <th class="text-center" style="width:9%;">最久逾期</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($aging)): ?>
                        <tr><td colspan="<?= count($buckets) + 4 ?>" class="text-center text-muted py-5">
                            <i class="bi bi-check2-circle fs-1 d-block mb-2 opacity-50"></i>
                            目前沒有未<?= $verb ?>的<?= $label ?>帳款
                        </td></tr>
                    <?php else: foreach ($aging as $row): ?>
                        <tr>
                            <td><strong><?= esc($row['partner']) ?></strong></td>
                            <td><small class="text-muted"><?= esc($row['terms']) ?></small></td>
                            <?php foreach ($buckets as $b): ?>
                                <?php $v = (int) ($row['buckets'][$b] ?? 0); ?>
                                <td class="text-end <?= $v > 0 && $b !== '未到期' ? 'text-danger' : ($v > 0 ? '' : 'text-muted') ?>">
                                    <?= $v > 0 ? $fmt($v) : '—' ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="text-end fw-bold"><?= $fmt($row['total']) ?></td>
                            <td class="text-center">
                                <?php if ($row['maxOverdue'] > 0): ?>
                                    <span class="badge <?= $row['maxOverdue'] > 60 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                        <?= $row['maxOverdue'] ?> 天
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── 逐筆明細 ───────────────────────────── -->
<?php if (! empty($aging)): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light"><strong><i class="bi bi-list-ul me-1"></i>逐筆明細</strong></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= $partnerLabel ?></th>
                        <th>傳票編號</th>
                        <th>摘要</th>
                        <th>傳票日期</th>
                        <th>到期日</th>
                        <th class="text-end">未<?= $verb ?>金額</th>
                        <th class="text-center">逾期</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($aging as $row): foreach ($row['items'] as $it): ?>
                        <?php if ((int) $it['open_amt'] <= 0) continue; ?>
                        <tr>
                            <td><?= esc($it['partner_name']) ?></td>
                            <td><a href="<?= site_url('journal/view/' . $it['jv_id']) ?>" class="text-decoration-none">
                                <?= esc($it['jv_no']) ?></a></td>
                            <td><?= esc($it['je_summary'] ?: $it['jv_summary']) ?></td>
                            <td><?= esc($it['jv_date']) ?></td>
                            <td><?= esc($it['due_date']) ?></td>
                            <td class="text-end"><?= $fmt($it['open_amt']) ?></td>
                            <td class="text-center">
                                <?php if ($it['overdue_days'] > 0): ?>
                                    <span class="text-danger fw-bold"><?= $it['overdue_days'] ?> 天</span>
                                <?php else: ?>
                                    <span class="text-muted small">未到期</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
