<?php
include 'functions.php';
include 'auth.php';
requireAdmin();

$message = "";
$error = "";
$timezones = DateTimeZone::listIdentifiers();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    $new = $config;
    foreach (ALLOWED_GATEWAY_FILES as $k => $allowed) {
        if (isset($_POST[$k])) {
            if (in_array($_POST[$k], $allowed, true)) {
                $new[$k] = $_POST[$k];
            } else {
                $error = "Invalid path for $k.";
            }
        }
    }
    foreach (['maxlines', 'sms_max', 'map_marker_ttl'] as $k) {
        if (isset($_POST[$k])) {
            $v = filter_var($_POST[$k], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $k === 'map_marker_ttl' ? 5256000 : 500]]);
            if ($v === false) {
                $error = "Invalid value for $k.";
            } else {
                $new[$k] = (string)$v;
            }
        }
    }
    if (isset($_POST['timezone'])) {
        if (in_array($_POST['timezone'], $timezones, true)) $new['timezone'] = $_POST['timezone'];
        else $error = "Invalid timezone.";
    }
    if (isset($_POST['unit_system'])) {
        if (in_array($_POST['unit_system'], ['metric', 'imperial'], true)) $new['unit_system'] = $_POST['unit_system'];
        else $error = "Invalid unit system.";
    }
    if (!$error) {
        if (saveConfig($new)) {
            $config = $new;
            $message = "Configuration saved.";
        } else {
            $error = "Could not write the configuration file.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $cur = $_POST['current_password'] ?? '';
    $pw1 = $_POST['new_password'] ?? '';
    $pw2 = $_POST['new_password2'] ?? '';
    if (!is_string($cur) || !password_verify($cur, $config['admin_password_hash'])) {
        sleep(2);
        $error = "The current password is wrong.";
    } else if (!is_string($pw1) || strlen($pw1) < 8) {
        $error = "The new password must be at least 8 characters long.";
    } else if ($pw1 !== $pw2) {
        $error = "The new passwords do not match.";
    } else {
        $config['admin_password_hash'] = password_hash($pw1, PASSWORD_DEFAULT);
        if (saveConfig($config)) {
            session_regenerate_id(true);
            $message = "Password changed.";
        } else {
            $error = "Could not write the configuration file.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['device_command'])) {
    // Needs a polkit rule allowing www-data to reboot and power off.
    // systemctl returns as soon as the shutdown has been queued, so this page
    // is still sent before nginx stops.
    $deviceCommands = [
        'reboot' => ['systemctl reboot 2>&1', 'The hotspot is rebooting. This page will work again in about a minute.'],
        'poweroff' => ['systemctl poweroff 2>&1', 'The hotspot is shutting down. Wait until the green activity LED on the Raspberry Pi has stopped blinking before you unplug it.'],
    ];
    $cmd = $_POST['device_command'];
    if (is_string($cmd) && isset($deviceCommands[$cmd])) {
        exec($deviceCommands[$cmd][0], $out, $rc);
        if ($rc === 0) {
            $message = $deviceCommands[$cmd][1];
        } else {
            $error = 'The command failed. Does the web server have permission to ' . ($cmd === 'reboot' ? 'reboot' : 'shut down') . ' the system?';
            $commandOutput = implode("\n", $out);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_command'])) {
    // Fixed commands only; nothing from the request reaches a shell
    $services = [
        'status' => 'systemctl status m17-gateway.service 2>&1',
        'start' => 'systemctl start m17-gateway.service 2>&1',
        'stop' => 'systemctl stop m17-gateway.service 2>&1',
        'restart' => 'systemctl restart m17-gateway.service 2>&1',
        'updatehostfile' => 'curl -sS "https://m17-project.github.io/hostfiles/M17Hosts.txt" -o "files/M17Hosts.txt" -z "files/M17Hosts.txt" -A "rpi-dashboard" 2>&1',
    ];
    $cmd = $_POST['run_command'];
    if ($cmd === 'log') {
        $commandOutput = implode("\n", tailFile($config['gateway_log_file'], 30));
    } else if ($cmd === 'showhostfile') {
        $commandOutput = @file_get_contents('files/M17Hosts.txt');
    } else if (is_string($cmd) && isset($services[$cmd])) {
        $commandOutput = shell_exec($services[$cmd]);
        if ($cmd === 'updatehostfile' && !$commandOutput) $commandOutput = 'Hostfile is up to date.';
    }
}

$page = 'config_dash';
include 'header.php';

function pathSelect($name, $config) {
    $out = '<select class="input" name="' . h($name) . '">';
    foreach (ALLOWED_GATEWAY_FILES[$name] as $p) {
        $out .= '<option value="' . h($p) . '"' . ($p === $config[$name] ? ' selected' : '') . '>' . h($p) . '</option>';
    }
    return $out . '</select>';
}
?>
<div class="page-content">
<?php if ($message): ?><div class="card"><p><?= h($message) ?></p></div><?php endif; ?>
<?php if ($error): ?><div class="card"><p class="status-bad"><?= h($error) ?></p></div><?php endif; ?>
<div class="card">
<h2>Dashboard settings</h2>
<form method="post">
<?= csrfField() ?>
<div class="form-grid-2col">
<div class="form-field"><label>M17 Gateway log file</label><?= pathSelect('gateway_log_file', $config) ?></div>
<div class="form-field"><label>M17 Gateway configuration file</label><?= pathSelect('gateway_config_file', $config) ?></div>
<div class="form-field"><label>Max. "Recent activity" entries</label><input class="input" type="number" min="1" max="500" name="maxlines" value="<?= h($config['maxlines']) ?>"></div>
<div class="form-field"><label>Max. text messages</label><input class="input" type="number" min="1" max="500" name="sms_max" value="<?= h($config['sms_max']) ?>"></div>
<div class="form-field"><label>Timezone</label>
<select class="input" name="timezone">
<?php foreach ($timezones as $tz): ?>
<option value="<?= h($tz) ?>" <?= $tz === $config['timezone'] ? 'selected' : '' ?>><?= h($tz) ?></option>
<?php endforeach; ?>
</select></div>
<div class="form-field"><label>Unit system</label>
<select class="input" name="unit_system">
<option value="imperial" <?= $config['unit_system'] === 'imperial' ? 'selected' : '' ?>>Imperial</option>
<option value="metric" <?= $config['unit_system'] === 'metric' ? 'selected' : '' ?>>Metric</option>
</select></div>
<div class="form-field"><label>Map marker TTL (minutes)</label><input class="input" type="number" min="1" name="map_marker_ttl" value="<?= h($config['map_marker_ttl']) ?>"></div>
</div>
<div style="margin-top:20px;"><button type="submit" name="save_config" class="btn-primary">Save</button></div>
</form>
</div>
<div class="card">
<h2>Gateway control</h2>
<form method="post">
<?= csrfField() ?>
<div class="form-grid-2col">
<div class="form-field"><label>M17 Gateway service</label><br>
<button class="btn-secondary" name="run_command" value="status">Status</button>
<button class="btn-secondary" name="run_command" value="start">Start</button>
<button class="btn-secondary" name="run_command" value="stop">Stop</button>
<button class="btn-secondary" name="run_command" value="restart">Restart</button>
</div>
<div class="form-field"><label>Diagnostics</label><br>
<button class="btn-secondary" name="run_command" value="log">Show log</button>
<button class="btn-secondary" name="run_command" value="showhostfile">Show hostfile</button>
<button class="btn-secondary" name="run_command" value="updatehostfile">Update hostfile</button>
</div>
</div>
</form>
</div>
<div class="card">
<h2>Device control</h2>
<form method="post">
<?= csrfField() ?>
<div class="form-grid-2col">
<div class="form-field"><label>Raspberry Pi</label><br>
<button class="btn-secondary" name="device_command" value="reboot" onclick="return confirm('Reboot the hotspot now?')">Reboot</button>
<button class="btn-secondary" name="device_command" value="poweroff" onclick="return confirm('Shut down the hotspot now? You will have to unplug and reconnect the power to start it again.')">Shut down</button>
</div>
</div>
</form>
</div>
<?php if (!empty($commandOutput)): ?>
<div class="card"><h2>Command output</h2><pre><?= h($commandOutput) ?></pre></div>
<?php endif; ?>
<div class="card">
<h2>Admin password</h2>
<form method="post">
<?= csrfField() ?>
<div class="form-grid-2col">
<div class="form-field"><label>Current password</label><input class="input" type="password" name="current_password" autocomplete="current-password"></div>
<div class="form-field"></div>
<div class="form-field"><label>New password</label><input class="input" type="password" name="new_password" autocomplete="new-password"></div>
<div class="form-field"><label>Repeat new password</label><input class="input" type="password" name="new_password2" autocomplete="new-password"></div>
</div>
<div style="margin-top:20px;"><button type="submit" name="change_password" class="btn-secondary">Change password</button></div>
</form>
</div>
</div>
<?php include 'footer.php'; ?>
