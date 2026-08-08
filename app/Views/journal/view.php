<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-journal-richtext me-2"></i>分錄傳票 <?= esc($jv['jv_no']) ?></h1>
    <div class="d-flex gap-2">
        <a href="<?= url_to('JournalController::edit', $jv['jv_id']) ?>" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>編輯</a>
        <a href="<?= url_to('JournalController::index') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>返回</a>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3"><small class="text-muted">傳票日期</small><div class="fw-semibold"><?= esc($jv['jv_date']) ?></div></div>
            <div class="col-md-2"><small class="text-muted">類別</small><div><?= esc($jv['jv_type']) ?></div></div>
            <div class="col-md-4"><small class="text-muted">摘要</small><div><?= esc($jv['jv_summary'] ?: '—') ?></div></div>
            <div class="col-md-3"><small class="text-muted">金額</small><div class="fw-bold" style="color:var(--navy);"><?= $fmt($jv['jv_amount']) ?></div></div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light"><tr><th style="width:40px;">#</th><th>會計科目</th><th>子摘要</th><th class="text-end">借方</th><th class="text-end">貸方</th></tr></thead>
                <tbody>
                    <?php $td=0;$tc=0; foreach ($jv['entries'] as $i => $e): $td+=$e['je_debit'];$tc+=$e['je_credit']; ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><code><?= esc($e['ac_code'] ?? '') ?></code> <?= esc($e['ac_name'] ?? '—') ?></td>
                            <td><small class="text-muted"><?= esc($e['je_summary'] ?: '') ?></small></td>
                            <td class="text-end"><?= $e['je_debit'] ? $fmt($e['je_debit']) : '' ?></td>
                            <td class="text-end"><?= $e['je_credit'] ? $fmt($e['je_credit']) : '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="3" class="text-end">合計</td>
                        <td class="text-end"><?= $fmt($td) ?></td>
                        <td class="text-end"><?= $fmt($tc) ?></td>
                    </tr>
                    <tr>
                        <td colspan="5" class="text-center">
                            <?php if ($td === $tc): ?><span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>借貸平衡</span>
                            <?php else: ?><span class="badge bg-danger">借貸不平衡</span><?php endif; ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
