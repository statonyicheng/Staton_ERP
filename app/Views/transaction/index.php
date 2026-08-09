<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-journal-text me-2"></i>會計交易登錄（傳票）</h1>
    <a href="<?= url_to('TransactionController::create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>新增交易</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>所有收入登「收」、支出登「付」，各掛會計科目與商業模式；四階損益分析、資金餘額表、總帳皆由本表自動計算。</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form action="<?= url_to('TransactionController::index') ?>" method="get" class="row g-2">
            <div class="col-md-6"><input type="text" class="form-control" name="keyword" placeholder="搜尋摘要 / 對象 / 科目..." value="<?= esc($keyword ?? '') ?>"></div>
            <div class="col-md-4">
                <select name="ym" class="form-select">
                    <option value="">全部月份</option>
                    <?php foreach ($months as $mo): ?>
                        <option value="<?= $mo ?>" <?= ($ym ?? '') === $mo ? 'selected' : '' ?>><?= $mo ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>查詢</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($data)): ?>
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無交易資料</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>日期</th><th>摘要</th><th>商業模式</th><th>會計科目</th><th class="text-center">收/付</th><th class="text-end">未稅</th><th class="text-end">稅額</th><th class="text-center">收付</th><th style="width:110px;" class="text-center">操作</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $t): ?>
                            <tr>
                                <td><small><?= esc($t['t_date']) ?></small></td>
                                <td><strong><?= esc($t['t_summary']) ?></strong><?php if ($t['t_partner']): ?><br><small class="text-muted"><?= esc($t['t_partner']) ?></small><?php endif; ?></td>
                                <td><span class="badge bg-light text-dark border"><?= esc($t['t_segment']) ?></span></td>
                                <td><small><code><?= esc($t['ac_code'] ?? '') ?></code> <?= esc($t['ac_name'] ?? '—') ?></small></td>
                                <td class="text-center"><?= $t['t_direction'] === '收' ? '<span class="badge bg-primary">收</span>' : '<span class="badge bg-secondary">付</span>' ?></td>
                                <td class="text-end"><?= number_format((int)$t['t_amount']) ?></td>
                                <td class="text-end text-muted"><?= number_format((int)$t['t_tax']) ?></td>
                                <td class="text-center"><?= $t['t_settle_status'] === '已收付' ? '<i class="bi bi-check-circle-fill text-success" title="已收付"></i>' : '<i class="bi bi-clock text-warning" title="未收付"></i>' ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url_to('TransactionController::edit', $t['t_id']) ?>" class="btn btn-outline-primary" title="編輯"><i class="bi bi-pencil"></i></a>
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete('<?= url_to('TransactionController::delete', $t['t_id']) ?>')" title="刪除"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
<?= view('components/pagination', ['pager' => $pager, 'baseUrl' => url_to('TransactionController::index'), 'params' => ['keyword' => $keyword ?? '', 'ym' => $ym ?? '']]) ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
