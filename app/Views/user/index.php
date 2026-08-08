<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="bi bi-people-fill me-2"></i>使用者管理</h2>
        <a href="<?= site_url('user/create') ?>" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i>新增使用者
        </a>
    </div>

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
            <?php if (empty($users)) : ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-emoji-neutral" style="font-size: 3rem;"></i>
                    <p class="mt-3">目前尚未建立使用者</p>
                </div>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>帳號</th>
                                <th>姓名</th>
                                <th>角色</th>
                                <th>建立時間</th>
                                <th>更新時間</th>
                                <th class="text-center" style="width: 160px;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user) : ?>
                                <tr>
                                    <td><strong><?= esc($user['u_username']) ?></strong></td>
                                    <td><?= esc($user['u_name']) ?></td>
                                    <td>
                                        <?php if (! empty($user['u_is_admin'])) : ?>
                                            <span class="badge bg-danger">管理員</span>
                                        <?php else : ?>
                                            <span class="badge bg-secondary">一般使用者</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?= esc($user['u_created_at']) ?></small></td>
                                    <td><small class="text-muted"><?= esc($user['u_updated_at']) ?></small></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= site_url('user/edit/' . $user['u_id']) ?>" class="btn btn-outline-primary">
                                                <i class="bi bi-pencil-square"></i> 編輯
                                            </a>
                                            <a href="<?= site_url('user/delete/' . $user['u_id']) ?>" class="btn btn-outline-danger" onclick="return confirm('確定要刪除此使用者嗎？');">
                                                <i class="bi bi-trash"></i> 刪除
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

