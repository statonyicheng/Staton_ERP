<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-list-ol me-2"></i>會計科目設定</h1>
    <a href="<?= url_to('AccountController::create') ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>新增科目</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-2"></i><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>科目依【嵐石】財務架構之四階損益模型分類（營業收入 → 一階成本 → 二～四階費用），「非-」科目不進損益表、僅計資金餘額。</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form action="<?= url_to('AccountController::index') ?>" method="get" class="row g-2">
            <div class="col-md-7"><input type="text" class="form-control" name="keyword" placeholder="搜尋科目名稱 / 代號..." value="<?= esc($keyword ?? '') ?>"></div>
            <div class="col-md-3">
                <select name="tier" class="form-select">
                    <option value="">全部損益歸屬</option>
                    <?php foreach ($tiers as $t): ?>
                        <option value="<?= $t ?>" <?= ($tier ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
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
            <div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">尚無會計科目</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr><th style="width:110px;">科目代號</th><th>科目名稱</th><th>類別</th><th>損益歸屬</th><th class="text-center">進損益表</th><th style="width:120px;" class="text-center">操作</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $item):
                            $tierColor = ['營業收入'=>'bg-primary','一階成本'=>'bg-secondary','二階費用'=>'bg-info text-dark','三階費用'=>'bg-info text-dark','四階費用'=>'bg-warning text-dark','不進損益'=>'bg-light text-dark border'][$item['ac_tier']] ?? 'bg-secondary';
                        ?>
                            <tr>
                                <td><code><?= esc($item['ac_code'] ?: '—') ?></code></td>
                                <td><strong><?= esc($item['ac_name']) ?></strong><?php if (!empty($item['ac_open_item'])): ?> <span class="badge bg-gold" title="需逐筆立沖帳">立沖帳</span><?php endif; ?></td>
                                <td><?= esc($item['ac_category']) ?></td>
                                <td><span class="badge <?= $tierColor ?>"><?= esc($item['ac_tier']) ?></span></td>
                                <td class="text-center"><?= $item['ac_is_pl'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-dash-circle text-muted"></i>' ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url_to('AccountController::edit', $item['ac_id']) ?>" class="btn btn-outline-primary" title="編輯"><i class="bi bi-pencil"></i></a>
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete('<?= url_to('AccountController::delete', $item['ac_id']) ?>')" title="刪除"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
<?= view('components/pagination', ['pager' => $pager, 'baseUrl' => url_to('AccountController::index'), 'params' => ['keyword' => $keyword ?? '', 'tier' => $tier ?? '']]) ?>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
