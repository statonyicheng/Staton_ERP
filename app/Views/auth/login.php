<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入 · 仕坦登 ERP</title>
    <?php $icoV = @filemtime(FCPATH . 'favicon.ico'); $pngV = @filemtime(FCPATH . 'staton-icon.png'); ?>
    <link rel="icon" href="<?= base_url('favicon.ico') . '?v=' . $icoV ?>" sizes="any">
    <link rel="icon" type="image/png" href="<?= base_url('staton-icon.png') . '?v=' . $pngV ?>" sizes="256x256">
    <link rel="apple-touch-icon" href="<?= base_url('staton-icon.png') . '?v=' . $pngV ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+TC:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        :root { --navy:#000e2f; --navy-2:#0a1c44; --gold:#f4b702; --gold-600:#d99e00; }
        * { font-family: "Noto Serif TC", "Source Han Serif TC", serif; }
        body {
            min-height: 100vh; margin: 0;
            background:
                radial-gradient(1200px 500px at 15% -10%, rgba(244,183,2,0.10), transparent 60%),
                linear-gradient(135deg, #000e2f 0%, #06183f 55%, #0a1c44 100%);
            display: flex; align-items: center; justify-content: center; padding: 24px;
        }
        .login-card {
            width: 100%; max-width: 420px; background: #fff;
            border-radius: 16px; overflow: hidden;
            box-shadow: 0 24px 60px rgba(0,0,0,0.35);
            border-top: 4px solid var(--gold);
        }
        .login-head { padding: 40px 40px 8px; text-align: center; }
        .brand-mark {
            display:inline-block; width: 44px; height: 44px; line-height:44px;
            background: var(--navy); color: var(--gold); border-radius: 10px;
            font-weight: 700; font-size: 1.4rem; margin-bottom: 16px; letter-spacing:1px;
        }
        .brand-name { color: var(--navy); font-weight: 700; font-size: 1.5rem; letter-spacing: 2px; margin: 0; }
        .brand-sub { color: var(--gold-600); font-size: .72rem; letter-spacing: 4px; margin-top: 6px; text-transform: uppercase; }
        .brand-corp { color: #8a90a0; font-size: .8rem; margin-top: 10px; }
        .login-body { padding: 20px 40px 40px; }
        .form-label { color: var(--navy); font-weight: 500; font-size: .9rem; }
        .form-control { border-radius: 9px; padding: .6rem .8rem; }
        .form-control:focus { border-color: var(--navy-2); box-shadow: 0 0 0 .2rem rgba(0,14,47,.12); }
        .btn-login {
            background: var(--navy); color:#fff; border:none; border-radius: 9px;
            padding: .7rem; font-weight: 600; letter-spacing: 3px; width: 100%;
            transition: all .2s ease;
        }
        .btn-login:hover { background: var(--gold); color: var(--navy); }
        .foot { text-align:center; color:#9aa0ad; font-size:.72rem; margin-top: 18px; letter-spacing:1px; }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="login-head">
            <div class="brand-mark">仕</div>
            <h1 class="brand-name">仕坦登 ERP</h1>
            <div class="brand-sub">Staton Enterprise ERP</div>
            <div class="brand-corp">仕坦登企業管理顧問有限公司</div>
        </div>
        <div class="login-body">
            <?php if (session()->getFlashdata('error')) : ?>
                <div class="alert alert-danger py-2"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('message')) : ?>
                <div class="alert alert-success py-2"><?= esc(session()->getFlashdata('message')) ?></div>
            <?php endif; ?>
            <?php if (isset($validation)) : ?>
                <div class="alert alert-danger py-2"><?= $validation->listErrors() ?></div>
            <?php endif; ?>

            <form method="post" action="<?= site_url('login') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="username" class="form-label">帳號</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?= old('username') ?>" required autofocus>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">密碼</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-login">登 入</button>
            </form>
            <div class="foot">© <?= date('Y') ?> STATON &nbsp;·&nbsp; 企業管理系統</div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
