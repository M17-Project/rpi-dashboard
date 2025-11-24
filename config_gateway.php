<?php
include 'functions.php';

// Load config
$gateway_config = parse_ini_file($config['gateway_config_file'], true, INI_SCANNER_RAW);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updated = $gateway_config;

    foreach ($_POST as $key => $value) {
        // Skip non-config fields (e.g., submit buttons)
        if (in_array($key, ['save', 'save_restart'])) {
            continue;
        }

        // Match "Section__Key" format using regex
        if (preg_match('/^([a-zA-Z0-9_]+)__(.+)$/', $key, $matches)) {
            $section = $matches[1];
            $item    = $matches[2];

            if (!isset($updated[$section])) {
                $updated[$section] = [];
            }

            // Store value as-is (raw), but strip newlines
            $value = str_replace(["\r", "\n"], ' ', $value);
            $updated[$section][$item] = $value;
        }
    }

    // Write updated configuration back to the INI file
    $content = '';
    foreach ($updated as $section => $pairs) {
        $content .= '[' . $section . "]\n";
        foreach ($pairs as $k => $v) {
            $content .= $k . ' = ' . $v . "\n";
        }
        $content .= "\n";
    }

    file_put_contents($config['gateway_config_file'], $content);

    // Optionally restart the gateway service
    if (isset($_POST['save_restart'])) {
        @shell_exec('systemctl restart m17-gateway');
    }

    // Reload config for display
    $gateway_config = parse_ini_file($config['gateway_config_file'], true, INI_SCANNER_RAW);
}

$page = 'config_gw';
include 'header.php';
?>
<div class="page-content">
  <div class="card">
    <h2>Gateway configuration</h2>

    <form method="POST">
      <div class="form-grid-2col">
        <?php foreach ($gateway_config as $section => $pairs): ?>
          <?php foreach ($pairs as $key => $val): ?>
            <div class="form-field">
              <label><?php echo htmlspecialchars($section . ' / ' . $key); ?></label>
              <input
                class="input"
                type="text"
                name="<?php echo htmlspecialchars($section . '__' . $key); ?>"
                value="<?php echo htmlspecialchars($val); ?>"
              >
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>

      <div class="floating-actions">
        <button type="submit" name="save" class="btn-secondary">Save</button>
        <button type="submit" name="save_restart" class="btn-primary">Save &amp; Restart</button>
      </div>
    </form>
  </div>
</div>
<?php include 'footer.php'; ?>
