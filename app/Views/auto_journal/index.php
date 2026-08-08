<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-lightning-charge me-2"></i>自動分錄</h1>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>依標準分錄範本，一鍵把營運單據轉為借貸傳票；已過帳者不可重複。範本：訂單 借應收/貸銷貨；採購 借存貨/貸應付；收款 借現金/貸應收；付款 借應付/貸現金。</div>

<?php $gl = $glStat ?? ['total' => 0, 'posted' => 0, 'pending' => 0]; ?>
<div class="card shadow-sm mb-3" style="border-left:4px solid var(--gold);">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h5 class="mb-1"><i class="bi bi-arrow-left-right me-1"></i>交易登錄（收付制）→ 借貸傳票</h5>
                <p class="text-muted small mb-2">
                    把「交易登錄（收付）」的公司內帳整批過帳為複式簿記傳票，四大財務報表才會反映真實帳務。<br>
                    分錄範本（含稅拆分，確保 Σ借＝Σ貸）：<br>
                    收・已收付：借 <strong>銀行存款</strong>(含稅) ／ 貸 <strong>收入科目</strong>(未稅) ＋ 貸 <strong>應付稅款</strong>(銷項稅)<br>
                    付・已收付：借 <strong>費用科目</strong>(未稅) ＋ 借 <strong>應付稅款</strong>(進項稅) ／ 貸 <strong>銀行存款</strong>(含稅)<br>
                    未收付者，對方科目改掛 <strong>應收帳款 / 應付帳款</strong>。
                </p>
                <span class="badge bg-primary me-1">交易總數 <?= $fmt($gl['total']) ?></span>
                <span class="badge bg-gold me-1">已過帳 <?= $fmt($gl['posted']) ?></span>
                <span class="badge bg-secondary">待過帳 <?= $fmt($gl['pending']) ?></span>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <?php if ($gl['pending'] > 0): ?>
                    <a href="<?= site_url('auto-journal/generate-gl') ?>" class="btn btn-gold"
                       onclick="return confirm('將 <?= $fmt($gl['pending']) ?> 筆收付交易過帳為借貸傳票？')">
                        <i class="bi bi-lightning-charge"></i> 一鍵全部過帳
                    </a>
                <?php else: ?>
                    <span class="text-success"><i class="bi bi-check-circle"></i> 全部已過帳</span>
                <?php endif; ?>
                <?php if ($gl['posted'] > 0): ?>
                    <a href="<?= site_url('auto-journal/clear-gl') ?>" class="btn btn-outline-danger btn-sm"
                       onclick="return confirm('清除 <?= $fmt($gl['posted']) ?> 張由收付交易產生的傳票？手動傳票不受影響。')">
                        <i class="bi bi-trash"></i> 清除自動傳票
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$sections = [
    ['訂單（借 應收帳款 / 貸 銷貨收入）', 'order', $orders, function ($o) { return [$o['o_number'], $o['o_date'], $o['c_name'] ?? '—', (int) round((float) $o['o_total_amount']), $o['o_id']]; }],
    ['採購單（借 存貨 / 貸 應付帳款）', 'po', $pos, function ($p) { return [$p['po_no'], $p['po_date'], $p['s_name'] ?? '—', (int) $p['po_total'], $p['po_id']]; }],
];
?>

<?php foreach ($sections as [$title, $type, $rows, $map]): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h5 class="mb-3"><?= esc($title) ?></h5>
            <?php if (empty($rows)): ?>
                <p class="text-muted mb-0">無可過帳單據</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th>單號</th><th>日期</th><th>對象</th><th class="text-end">金額</th><th style="width:140px;" class="text-center">分錄</th></tr></thead>
                        <tbody>
                            <?php foreach ($rows as $r): [$no, $date, $partner, $amt, $id] = $map($r); $isPosted = isset($posted[$type . ':' . $id]); ?>
                                <tr>
                                    <td><code><?= esc($no) ?></code></td>
                                    <td><small><?= esc($date) ?></small></td>
                                    <td><?= esc($partner) ?></td>
                                    <td class="text-end fw-semibold"><?= $fmt($amt) ?></td>
                                    <td class="text-center">
                                        <?php if ($isPosted): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>已過帳</span>
                                        <?php else: ?>
                                            <a href="<?= site_url("auto-journal/generate/{$type}/{$id}") ?>" class="btn btn-sm btn-gold"><i class="bi bi-lightning-charge me-1"></i>產生分錄</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h5 class="mb-3">收付款（收款 借現金/貸應收；付款 借應付/貸現金）</h5>
        <?php if (empty($settlements)): ?>
            <p class="text-muted mb-0">無收付款紀錄</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>收付單號</th><th>日期</th><th class="text-center">收/付</th><th>對象</th><th class="text-end">金額</th><th style="width:140px;" class="text-center">分錄</th></tr></thead>
                    <tbody>
                        <?php foreach ($settlements as $s): $type = $s['st_direction'] === '收' ? 'receipt' : 'payment'; $isPosted = isset($posted[$type . ':' . $s['st_id']]); ?>
                            <tr>
                                <td><code><?= esc($s['st_no']) ?></code></td>
                                <td><small><?= esc($s['st_date']) ?></small></td>
                                <td class="text-center"><?= $s['st_direction']==='收'?'<span class="badge bg-success">收</span>':'<span class="badge bg-danger">付</span>' ?></td>
                                <td><?= esc($s['st_partner'] ?: '—') ?></td>
                                <td class="text-end fw-semibold"><?= $fmt($s['st_amount']) ?></td>
                                <td class="text-center">
                                    <?php if ($isPosted): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>已過帳</span>
                                    <?php else: ?>
                                        <a href="<?= site_url("auto-journal/generate/{$type}/{$s['st_id']}") ?>" class="btn btn-sm btn-gold"><i class="bi bi-lightning-charge me-1"></i>產生分錄</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
