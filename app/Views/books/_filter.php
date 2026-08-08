<?php
/**
 * 會計帳簿共用的期間 / 科目篩選列
 * 參數：$from, $to, $range, $acId, $accounts(選填，有給才顯示科目下拉), $action
 */
$showAccount = !empty($accounts);
?>
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">起始日期</label>
                <input type="date" name="from" value="<?= esc($from) ?>" class="form-control form-control-sm"
                       min="<?= esc($range['min']) ?>" max="<?= esc($range['max']) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">結束日期</label>
                <input type="date" name="to" value="<?= esc($to) ?>" class="form-control form-control-sm"
                       min="<?= esc($range['min']) ?>" max="<?= esc($range['max']) ?>">
            </div>
            <?php if ($showAccount): ?>
                <div class="col-12 col-md-4">
                    <label class="form-label small mb-1">會計科目</label>
                    <select name="ac_id" class="form-select form-select-sm">
                        <option value="">全部科目</option>
                        <?php foreach ($accounts as $a): ?>
                            <option value="<?= $a['ac_id'] ?>" <?= (int) ($acId ?? 0) === (int) $a['ac_id'] ? 'selected' : '' ?>>
                                <?= esc($a['ac_code']) ?>　<?= esc($a['ac_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-search"></i> 查詢</button>
                <a href="<?= current_url() ?>" class="btn btn-outline-secondary btn-sm">重設</a>
            </div>
        </form>
        <div class="small text-muted mt-2">
            <i class="bi bi-info-circle"></i> 資料涵蓋 <?= esc($range['min']) ?> ~ <?= esc($range['max']) ?>。
            本帳簿依複式簿記分錄編製（借貸基礎），與「四階損益／資金餘額」的收付實現制基礎不同。
        </div>
    </div>
</div>
