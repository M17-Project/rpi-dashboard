<?php
include 'functions.php';
include 'auth.php';

$allowedNext = ['config_gateway.php', 'config_dashboard.php'];
$next = $_GET['next'] ?? '';
if (!in_array($next, $allowedNext, true)) $next = 'config_dashboard.php';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_regenerate_id(true);
    header('Location: index.php');
    exit;
}

$error = '';
$noPassword = ($config['admin_password_hash'] === '');

if (!$noPassword && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $pw = $_POST['password'] ?? '';
    if (is_string($pw) && password_verify($pw, $config['admin_password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        unset($_SESSION['csrf']);
        header('Location: ' . $next);
        exit;
    }
    sleep(2); // slow down password guessing
    $error = 'Wrong password.';
}

$page = 'login';
include 'header.php';
?>
<div class="page-content">
<div class="card">
<h2>Admin login</h2>
<?php if ($noPassword): ?>
<p>No admin password has been set yet, so the configuration pages are locked.</p>
<p>To set one, log in to the Raspberry Pi and run:</p>
<pre>sudo -u www-data php <?= h(__DIR__) ?>/set_password.php</pre>
<?php else: ?>
<?php if ($error): ?><p class="status-bad"><?= h($error) ?></p><?php endif; ?>
<form method="post" action="login.php?next=<?= h(urlencode($next)) ?>">
<?= csrfField() ?>
<div class="form-field"><label>Password</label><input class="input" type="password" name="password" autofocus autocomplete="current-password"></div>
<div style="margin-top:20px;"><button type="submit" class="btn-primary">Log in</button></div>
</form>
<?php endif; ?>
</div>
</div>
<?php include 'footer.php'; ?>
