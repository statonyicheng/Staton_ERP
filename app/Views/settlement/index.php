<?php $fmt = fn($n) => number_format((int) $n); ?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-bank2 me-2"></i>收付款作業</h1>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>所有收款（來自應收）與付款（來自應付）的紀錄；於應收/應付帳款頁面點「收款/付款」即會登錄至此。</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form action="<?= url_to('SettlementController::index') ?>" method="get" class="row g-2">
            <div class="col-md-8"><input type="text" class="form-control" name="keyword" placeholder="搜尋收付單號 / 對象 / 憑單號..." value="<?= esc($keyword ?? '') ?>"></div>
            <div class="col-md-2"><select name="dir" class="form-select"><option value="">全部</option><option value="收" <?= ($direction ?? '')==='收'?'selected':'' ?>>收款</option><option value="付" <?= ($direction ?? '')==='付'?'selected':'' ?>>付款</option></select></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>查詢</button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($data)): ?>
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無收付款紀錄</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th>收付單號</th><th>日期</th><th class="text-center">收/付</th><th>對象</th><th>憑單號</th><th>方式</th><th class="text-end">金額</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $st): ?>
                            <tr>
                                <td><code><?= esc($st['st_no']) ?></code></td>
                                <td><small><?= esc($st['st_date']) ?></small></td>
                                <td class="text-center"><?= $st['st_direction']==='收' ? '<span class="badge bg-success">收</span>' : '<span class="badge bg-danger">付</span>' ?></td>
                                <td><?= esc($st['st_partner'] ?: '—') ?></td>
                                <td><small class="text-muted"><?= esc($st['st_ref_no'] ?: '—') ?></small></td>
                                <td><?= esc($st['st_method']) ?></td>
                                <td class="text-end fw-semibold <?= $st['st_direction']==='收'?'text-success':'text-danger' ?>"><?= $st['st_direction']==='付'?'-':'+' ?><?= $fmt($st['st_amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
<?= view('components/pagination', ['pager' => $pager, 'baseUrl' => url_to('SettlementController::index'), 'params' => ['keyword' => $keyword ?? '', 'dir' => $direction ?? '']]) ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
