<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<?php
    $isEdit     = isset($user) && $user !== null;
    $validation = $validation ?? session('validation');
?>

<div class="container-fluid mt-4">
    <div class="mb-3 d-flex align-items-center justify-content-between">
        <h2>
            <i class="bi bi-person-lines-fill me-2"></i>
            <?= $isEdit ? '編輯使用者' : '新增使用者' ?>
        </h2>
        <a href="<?= site_url('user') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> 返回列表
        </a>
    </div>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= site_url('user/save') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="u_id" value="<?= old('u_id', $user['u_id'] ?? '') ?>">

                <div class="mb-3">
                    <label for="u_username" class="form-label">帳號</label>
                    <input type="text" class="form-control <?= $validation?->hasError('u_username') ? 'is-invalid' : '' ?>" id="u_username" name="u_username" value="<?= old('u_username', $user['u_username'] ?? '') ?>" <?= $isEdit ? '' : 'autocomplete="username"' ?> required>
                    <?php if ($validation?->hasError('u_username')) : ?>
                        <div class="invalid-feedback">
                            <?= esc($validation->getError('u_username')) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="u_name" class="form-label">姓名</label>
                    <input type="text" class="form-control <?= $validation?->hasError('u_name') ? 'is-invalid' : '' ?>" id="u_name" name="u_name" value="<?= esc(old('u_name', $user['u_name'] ?? '')) ?>" required>
                    <?php if ($validation?->hasError('u_name')) : ?>
                        <div class="invalid-feedback">
                            <?= esc($validation->getError('u_name')) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">
                            密碼 <?= $isEdit ? '<small class="text-muted">(如需變更請重新輸入)</small>' : '' ?>
                        </label>
                        <input type="password" class="form-control <?= $validation?->hasError('password') ? 'is-invalid' : '' ?>" id="password" name="password" <?= $isEdit ? '' : 'required' ?> autocomplete="new-password">
                        <?php if ($validation?->hasError('password')) : ?>
                            <div class="invalid-feedback">
                                <?= esc($validation->getError('password')) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="password_confirm" class="form-label">確認密碼</label>
                        <input type="password" class="form-control <?= $validation?->hasError('password_confirm') ? 'is-invalid' : '' ?>" id="password_confirm" name="password_confirm" <?= $isEdit ? '' : 'required' ?> autocomplete="new-password">
                        <?php if ($validation?->hasError('password_confirm')) : ?>
                            <div class="invalid-feedback">
                                <?= esc($validation->getError('password_confirm')) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" role="switch" id="u_is_admin" name="u_is_admin" value="1" <?= old('u_is_admin', $user['u_is_admin'] ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="u_is_admin">設定為管理員</label>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= site_url('user') ?>" class="btn btn-outline-secondary">取消</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i><?= $isEdit ? '更新' : '新增' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

