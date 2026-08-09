<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<h1>
    <i class="bi bi-<?= $isEdit ? 'pencil-square' : 'plus-circle' ?> me-2"></i>
    <?= $isEdit ? '編輯' : '新增' ?>商業模式
</h1>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="post" action="<?= $isEdit
    ? url_to('BusinessSegmentController::update', $data['bs_id'])
    : url_to('BusinessSegmentController::store') ?>">
    <?php if ($isEdit): ?><?= \App\Libraries\EditGuard::field($data) ?><?php endif; ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label" for="bsCode">
                        代號 <span class="text-danger">*</span>
                        <i class="bi bi-info-circle text-muted"
                           title="交易資料存的就是這個代號，被使用後不可更改"></i>
                    </label>
                    <input type="text" class="form-control" id="bsCode" name="bs_code" maxlength="12" required
                           value="<?= esc(old('bs_code', $data['bs_code'] ?? '')) ?>"
                           <?= ($isEdit && ($usage ?? 0) > 0) ? 'readonly' : '' ?>>
                    <?php if ($isEdit && ($usage ?? 0) > 0): ?>
                        <div class="form-text text-warning">
                            已被 <?= number_format($usage) ?> 筆交易使用，代號不可更改（名稱與定義仍可修改）
                        </div>
                    <?php else: ?>
                        <div class="form-text">習慣用 M-0、M-1… 這種編法，方便報表排序</div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label" for="bsName">商業模式名稱 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="bsName" name="bs_name" maxlength="50" required
                           value="<?= esc(old('bs_name', $data['bs_name'] ?? '')) ?>">
                    <div class="form-text">會顯示在四階損益分析的欄位標題與各表單的下拉選單</div>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label" for="bsSort">排序</label>
                    <input type="number" class="form-control" id="bsSort" name="bs_sort"
                           value="<?= esc(old('bs_sort', $data['bs_sort'] ?? 0)) ?>">
                    <div class="form-text">數字小的排前面</div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="bsDefinition">商業模式定義</label>
                <textarea class="form-control" id="bsDefinition" name="bs_definition" rows="4"
                          placeholder="這條業務線在做什麼？收入怎麼來？哪些服務算在這一類？"><?= esc(old('bs_definition', $data['bs_definition'] ?? '')) ?></textarea>
                <div class="form-text">
                    寫清楚一點 —— 之後看四階損益報表的人（包含新同事、會計師）才知道這一欄涵蓋哪些業務。
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="bsInPl" name="bs_in_pl" value="1"
                               <?= old('bs_in_pl', $data['bs_in_pl'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="bsInPl">
                            <strong>列入四階損益分析</strong>
                            <div class="form-text mt-0">
                                打勾才會成為四階損益報表的一欄。非營業性質（股東往來、押金）通常不列入。
                            </div>
                        </label>
                    </div>
                </div>
                <div class="col-md-6 mb-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="bsActive" name="bs_is_active" value="1"
                               <?= old('bs_is_active', $data['bs_is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="bsActive">
                            <strong>啟用</strong>
                            <div class="form-text mt-0">
                                停用後不再出現在下拉選單，<strong>既有資料與歷史報表不受影響</strong>。
                                不再使用的業務線請用停用，不要刪除。
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end mb-4">
        <a href="<?= url_to('BusinessSegmentController::index') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i>取消
        </a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>儲存</button>
    </div>
</form>

<?= $this->endSection() ?>
