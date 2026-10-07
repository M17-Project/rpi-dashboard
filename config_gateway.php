<?php
include 'functions.php';
include 'auth.php';
requireAdmin();

$iniFile = $config['gateway_config_file'];

// Read the INI file line by line, keeping comments, blank lines and order,
// so saving only changes the values that were edited.
// Returns [lines, fields] where fields maps "Section__Key" to
// [section, key, value, line index].
function readIni($file) {
    $lines = @file($file, FILE_IGNORE_NEW_LINES);
    if ($lines === false) return [null, []];
    $fields = [];
    $section = '';
    foreach ($lines as $i => $line) {
        if (preg_match('/^\s*\[([^\]]+)\]\s*$/', $line, $m)) {
            $section = trim($m[1]);
        } else if (preg_match('/^\s*([^=;#\s][^=]*?)\s*=\s*(.*?)\s*$/', $line, $m)) {
            $fields[$section . '__' . $m[1]] = [$section, $m[1], $m[2], $i];
        }
    }
    return [$lines, $fields];
}

// A value is written verbatim, so it must not contain anything that would
// change how the line is parsed: line breaks or other control characters,
// comment markers or quotes.
function validIniValue($v) {
    return !preg_match('/[\x00-\x1f\x7f;#"`\\\\]/', $v);
}

[$lines, $fields] = readIni($iniFile);
$message = '';

// These settings get a list of the system's sound devices instead of a text
// field, so nobody has to know the format m17-gateway expects
$alsaFields = [
    'Modem__ALSACaptureDevice' => 'record',
    'Modem__ALSAPlaybackDevice' => 'play',
];
$alsaDevices = alsaDevices();
$errors = [];

if ($lines !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $changed = false;
    // Only keys that already exist in the file can be changed
    foreach ($fields as $name => [$section, $key, $old, $i]) {
        // PHP turns spaces and dots in POST names into underscores
        $postName = str_replace([' ', '.'], '_', $name);
        if (!isset($_POST[$postName]) || !is_string($_POST[$postName])) continue;
        $value = trim($_POST[$postName]);
        if ($value === $old) continue;
        if (!validIniValue($value)) {
            $errors[] = "$section / $key: line breaks, quotes, backslashes, ';' and '#' are not allowed.";
            continue;
        }
        $lines[$i] = preg_replace('/^(\s*[^=]*?\s*=\s*).*$/', '${1}', $lines[$i]) . $value;
        $changed = true;
    }

    if (!$errors && $changed) {
        $content = implode("\n", $lines) . "\n";
        // The file is a symlink into /etc, so it can't be replaced atomically
        // with rename(); at least check that everything was written.
        if (file_put_contents($iniFile, $content, LOCK_EX) !== strlen($content)) {
            $errors[] = 'Could not write the gateway configuration file.';
        } else {
            $message = 'Configuration saved.';
        }
    }

    if (!$errors && isset($_POST['save_restart'])) {
        shell_exec('systemctl restart m17-gateway.service 2>&1');
        $message .= ' Gateway restarted.';
    }

    [$lines, $fields] = readIni($iniFile);
}

$page = 'config_gw';
include 'header.php';
?>
<div class="page-content">
  <?php if ($message): ?><div class="card"><p><?= h($message) ?></p></div><?php endif; ?>
  <?php if ($errors): ?><div class="card"><?php foreach ($errors as $e): ?><p class="status-bad"><?= h($e) ?></p><?php endforeach; ?></div><?php endif; ?>
  <div class="card">
    <h2>Gateway configuration</h2>
    <?php if ($lines === null): ?>
    <p class="status-bad">Cannot read <?= h($iniFile) ?>.</p>
    <?php else: ?>
    <form method="POST">
      <?= csrfField() ?>
      <div class="form-grid-2col">
        <?php foreach ($fields as $name => [$section, $key, $val]): ?>
          <div class="form-field">
            <label><?= h($section . ' / ' . $key) ?></label>
            <?php if (isset($alsaFields[$name])): ?>
            <select class="input" name="<?= h($name) ?>">
              <option value=""<?= $val === '' ? ' selected' : '' ?>>Automatic (recommended)</option>
              <?php $found = ($val === ''); ?>
              <?php foreach ($alsaDevices as $d):
                  if (!$d[$alsaFields[$name]]) continue;
                  // The current setting may also be a device path or hw:CARD,DEVICE;
                  // saving then stores the name, which doesn't change between boots
                  $hw = 'hw:' . $d['card'] . ',' . $d['device'];
                  $path = '/dev/snd/pcmC' . $d['card'] . 'D' . $d['device'] . ($alsaFields[$name] === 'play' ? 'p' : 'c');
                  $selected = in_array($val, [$d['name'], $hw, $path], true);
                  $found = $found || $selected; ?>
              <option value="<?= h($d['name']) ?>"<?= $selected ? ' selected' : '' ?>><?= h($d['card_name'] . ': ' . $d['name'] . ' (' . $hw . ')') ?></option>
              <?php endforeach; ?>
              <?php if (!$found): ?>
              <option value="<?= h($val) ?>" selected><?= h($val) ?> (current setting, not found)</option>
              <?php endif; ?>
            </select>
            <?php else: ?>
            <input class="input" type="text" name="<?= h($name) ?>" value="<?= h($val) ?>">
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="floating-actions">
        <button type="submit" name="save" class="btn-secondary">Save</button>
        <button type="submit" name="save_restart" class="btn-primary">Save &amp; Restart</button>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php include 'footer.php'; ?>
