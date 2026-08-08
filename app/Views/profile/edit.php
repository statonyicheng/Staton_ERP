<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<?php $validation = $validation ?? session('validation'); ?>

<div class="container-fluid mt-4">
    <h2 class="mb-3"><i class="bi bi-person-circle me-2"></i>個人資料</h2>

    <?php if (session()->getFlashdata('message')) : ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= esc(session()->getFlashdata('message')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="<?= site_url('profile/update') ?>">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label">帳號</label>
                    <input type="text" class="form-control" value="<?= esc($user['u_username']) ?>" disabled>
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
                        <label for="password" class="form-label">新密碼</label>
                        <input type="password" class="form-control <?= $validation?->hasError('password') ? 'is-invalid' : '' ?>" id="password" name="password" autocomplete="new-password">
                        <?php if ($validation?->hasError('password')) : ?>
                            <div class="invalid-feedback">
                                <?= esc($validation->getError('password')) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="password_confirm" class="form-label">確認新密碼</label>
                        <input type="password" class="form-control <?= $validation?->hasError('password_confirm') ? 'is-invalid' : '' ?>" id="password_confirm" name="password_confirm" autocomplete="new-password">
                        <?php if ($validation?->hasError('password_confirm')) : ?>
                            <div class="invalid-feedback">
                                <?= esc($validation->getError('password_confirm')) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>儲存變更
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

