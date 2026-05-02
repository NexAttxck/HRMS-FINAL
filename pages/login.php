<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php?page=dashboard');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (Auth::login($email, $password)) {
        header('Location: ' . BASE_URL . '/index.php?page=dashboard');
        exit;
    } else {
        $error = 'Invalid credentials or account inactive.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login &mdash; <?php echo APP_NAME; ?></title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/web/css/hrms.css?v=<?php echo time(); ?>">
<script>
(function(){
    var dm = localStorage.getItem('hrms_dark_mode') || 'on';
    if (dm === 'off') {
        document.documentElement.setAttribute('data-theme','light');
        var s = document.createElement('style'); s.textContent=':root[data-theme="light"]{--bg:#f4f6f4;--card:#fff;--text:#1a1a1a;--text-muted:#555;--border:rgba(0,0,0,.1);}body{background:var(--bg)!important;}'; document.head.appendChild(s);
    }
})();
</script>
</head>
<body class="hrms-body" style="display:flex;align-items:center;justify-content:center;min-height:100vh;">
<div style="width:100%;max-width:420px;padding:24px;">
    <div class="hrms-card" style="padding:40px;">
        <div style="text-align:center;margin-bottom:32px;">
            <div style="font-size:32px;font-weight:900;letter-spacing:2px;color:var(--accent);">STAFFORA</div>
            <div style="color:var(--text-muted);font-size:13px;margin-top:4px;">Human Resource Management System</div>
        </div>
        <?php if($error): ?>
        <div style="background:rgba(239,83,80,0.12);border-left:3px solid #ef5350;border-radius:0 8px 8px 0;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#ef5350;">
            <?php echo e($error); ?>
        </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">Email Address</label>
                <input type="email" name="email" id="email" required autocomplete="email"
                    value="<?php echo e($_POST['email'] ?? ''); ?>"
                    style="width:100%;padding:11px 14px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px;outline:none;transition:border .2s;"
                    onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'"
                    placeholder="you@staffora.com">
            </div>
            <div style="margin-bottom:24px;">
                <label style="display:block;font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">Password</label>
                <input type="password" name="password" id="password" required autocomplete="current-password"
                    style="width:100%;padding:11px 14px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px;outline:none;transition:border .2s;"
                    onfocus="this.style.borderColor='var(--accent)'" onblur="this.style.borderColor='var(--border)'"
                    placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
            </div>
            <button type="submit" class="btn btn-accent" style="width:100%;padding:12px;font-size:15px;font-weight:700;border-radius:8px;">
                Sign In &rarr;
            </button>
        </form>
        <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border);font-size:12px;color:var(--text-muted);text-align:center;">
            &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> &mdash; All rights reserved
        </div>
    </div>
</div>
</body>
</html>
