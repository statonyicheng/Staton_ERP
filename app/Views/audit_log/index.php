<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1><i class="bi bi-clock-history me-2"></i>操作紀錄</h1>

<div class="alert alert-info py-2"><i class="bi bi-info-circle me-1"></i>
    記錄所有透過系統異動的資料：誰、何時、改了哪一筆、改了什麼。
    稽核紀錄不可修改或刪除。目前共 <strong><?= number_format($total) ?></strong> 筆。
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">操作者</label>
                <select name="user" class="form-select form-select-sm">
                    <option value="">全部</option>
                    <?php foreach ($users as $u): if ($u === null || $u === '') continue; ?>
                        <option value="<?= esc($u) ?>" <?= $user === $u ? 'selected' : '' ?>><?= esc($u) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">資料表</label>
                <select name="table" class="form-select form-select-sm">
                    <option value="">全部</option>
                    <?php foreach ($tables as $t): ?>
                        <option value="<?= esc($t) ?>" <?= $table === $t ? 'selected' : '' ?>><?= esc($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">動作</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">全部</option>
                    <?php foreach (['新增', '修改', '刪除'] as $a): ?>
                        <option value="<?= $a ?>" <?= $action === $a ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">起始日</label>
                <input type="date" name="from" value="<?= esc($from) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">結束日</label>
                <input type="date" name="to" value="<?= esc($to) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2 d-flex gap-2">
                <input type="text" name="keyword" value="<?= esc($keyword) ?>" class="form-control form-control-sm" placeholder="關鍵字">
                <button class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:150px;">時間</th>
                        <th style="width:110px;">操作者</th>
                        <th style="width:70px;">動作</th>
                        <th style="width:170px;">資料表 / 主鍵</th>
                        <th style="width:170px;">摘要</th>
                        <th>變更內容</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">查無紀錄</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                        <?php
                        $badge = ['新增' => 'bg-success', '修改' => 'bg-primary', '刪除' => 'bg-danger'][$r['al_action']] ?? 'bg-secondary';
                        $changes = $r['al_changes'] ? json_decode($r['al_changes'], true) : null;
                        ?>
                        <tr>
                            <td><small><?= esc($r['al_at']) ?></small></td>
                            <td><?= esc($r['al_username'] ?: '—') ?></td>
                            <td><span class="badge <?= $badge ?>"><?= esc($r['al_action']) ?></span></td>
                            <td><small><code><?= esc($r['al_table']) ?></code><?= $r['al_row_id'] !== null ? ' #' . esc($r['al_row_id']) : '' ?></small></td>
                            <td><small><?= esc($r['al_summary'] ?: '—') ?></small></td>
                            <td>
                                <?php if (empty($changes)): ?>
                                    <span class="text-muted small">—</span>
                                <?php else: ?>
                                    <div style="max-height:140px; overflow-y:auto;">
                                        <?php foreach ($changes as $field => $pair): ?>
                                            <div class="small">
                                                <code><?= esc($field) ?></code>
                                                <?php if ($pair[0] === null): ?>
                                                    <span class="text-success">＋<?= esc((string) $pair[1]) ?></span>
                                                <?php elseif ($pair[1] === null): ?>
                                                    <span class="text-danger">−<?= esc((string) $pair[0]) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted"><?= esc((string) $pair[0]) ?></span>
                                                    <i class="bi bi-arrow-right mx-1"></i>
                                                    <strong><?= esc((string) $pair[1]) ?></strong>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= view('components/pagination', ['pager' => $pager, 'baseUrl' => 'audit-log', 'params' => $_GET]) ?>
    </div>
</div>

<?= $this->endSection() ?>
