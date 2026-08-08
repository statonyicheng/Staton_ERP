<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-journal-richtext me-2"></i>分錄傳票（借貸）</h1>
    <a href="<?= url_to('JournalController::create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>新增傳票</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>傳統複式簿記：每張傳票須「借方合計＝貸方合計」，套用會計科目表。</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form action="<?= url_to('JournalController::index') ?>" method="get" class="row g-2">
            <div class="col-md-10"><input type="text" class="form-control" name="keyword" placeholder="搜尋傳票號 / 摘要..." value="<?= esc($keyword ?? '') ?>"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>查詢</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($data)): ?>
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無分錄傳票</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>傳票號</th><th>日期</th><th>類別</th><th>摘要</th><th class="text-end">金額</th><th style="width:150px;" class="text-center">操作</th></tr></thead>
                    <tbody>
                        <?php foreach ($data as $jv): ?>
                            <tr>
                                <td><code><?= esc($jv['jv_no']) ?></code></td>
                                <td><small><?= esc($jv['jv_date']) ?></small></td>
                                <td><span class="badge bg-light text-dark border"><?= esc($jv['jv_type']) ?></span></td>
                                <td><?= esc($jv['jv_summary'] ?: '—') ?></td>
                                <td class="text-end fw-semibold"><?= $fmt($jv['jv_amount']) ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url_to('JournalController::view', $jv['jv_id']) ?>" class="btn btn-outline-info" title="檢視"><i class="bi bi-eye"></i></a>
                                        <a href="<?= url_to('JournalController::edit', $jv['jv_id']) ?>" class="btn btn-outline-primary" title="編輯"><i class="bi bi-pencil"></i></a>
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete('<?= url_to('JournalController::delete', $jv['jv_id']) ?>')" title="刪除"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($pager['totalPages'] > 1): ?><?= view('components/pagination', ['pager' => $pager, 'baseUrl' => url_to('JournalController::index'), 'params' => ['keyword' => $keyword ?? '']]) ?><?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
