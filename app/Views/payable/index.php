<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-credit-card me-2"></i>應付帳款管理</h1>
    <div class="d-flex gap-2">
        <a href="<?= url_to('PayableController::generate') ?>" class="btn btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i>從結案採購單產生</a>
        <a href="<?= url_to('PayableController::create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>新增應付</a>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card shadow-sm"><div class="card-body d-flex justify-content-between align-items-center"><span class="text-muted">應付總額</span><strong class="fs-5" style="color:var(--navy);"><?= $fmt($summary['amount']) ?></strong></div></div></div>
    <div class="col-md-4"><div class="card shadow-sm"><div class="card-body d-flex justify-content-between align-items-center"><span class="text-muted">已付</span><strong class="fs-5 text-success"><?= $fmt($summary['paid']) ?></strong></div></div></div>
    <div class="col-md-4"><div class="card shadow-sm"><div class="card-body d-flex justify-content-between align-items-center"><span class="text-muted">未付餘額</span><strong class="fs-5 text-danger"><?= $fmt($summary['outstanding']) ?></strong></div></div></div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form action="<?= url_to('PayableController::index') ?>" method="get" class="row g-2">
            <div class="col-md-7"><input type="text" class="form-control" name="keyword" placeholder="搜尋單號 / 廠商 / 來源單..." value="<?= esc($keyword ?? '') ?>"></div>
            <div class="col-md-3"><select name="status" class="form-select"><option value="">全部狀態</option><?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= ($status ?? '')===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>查詢</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($data)): ?>
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無應付帳款</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>應付單號</th><th>日期</th><th>廠商</th><th>來源</th><th class="text-end">應付</th><th class="text-end">已付</th><th class="text-end">未付</th><th>狀態</th><th style="width:140px;" class="text-center">操作</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $ap): $remain = (int)$ap['ap_amount'] - (int)$ap['ap_paid'];
                            $cls = ['未付款'=>'bg-danger','部分付款'=>'bg-warning text-dark','已付款'=>'bg-success'][$ap['ap_status']] ?? 'bg-secondary'; ?>
                            <tr>
                                <td><code><?= esc($ap['ap_no']) ?></code></td>
                                <td><small><?= esc($ap['ap_date']) ?></small></td>
                                <td><?= esc($ap['s_name'] ?: '—') ?></td>
                                <td><small class="text-muted"><?= esc($ap['ap_ref_no'] ?: $ap['ap_source']) ?></small></td>
                                <td class="text-end"><?= $fmt($ap['ap_amount']) ?></td>
                                <td class="text-end text-success"><?= $fmt($ap['ap_paid']) ?></td>
                                <td class="text-end fw-semibold <?= $remain>0?'text-danger':'' ?>"><?= $fmt($remain) ?></td>
                                <td><span class="badge <?= $cls ?>"><?= esc($ap['ap_status']) ?></span></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($ap['ap_status'] !== '已付款'): ?><a href="<?= url_to('PayableController::pay', $ap['ap_id']) ?>" class="btn btn-gold" title="付款"><i class="bi bi-cash"></i> 付款</a><?php endif; ?>
                                        <a href="<?= url_to('PayableController::edit', $ap['ap_id']) ?>" class="btn btn-outline-primary" title="編輯"><i class="bi bi-pencil"></i></a>
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete('<?= url_to('PayableController::delete', $ap['ap_id']) ?>')" title="刪除"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
<?= view('components/pagination', ['pager' => $pager, 'baseUrl' => url_to('PayableController::index'), 'params' => ['keyword' => $keyword ?? '', 'status' => $status ?? '']]) ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
