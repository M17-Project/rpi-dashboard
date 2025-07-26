<?php
$configFile = 'testdata/config.ini';
$hostsFile = 'testdata/M17Hosts.txt';

// Load config
$config = parse_ini_file($configFile, true);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        // Skip non-config fields (e.g., submit buttons)
        if (in_array($key, ['save', 'save_restart'])) continue;

        // Match "Section__Key" format using regex
        if (preg_match('/^([a-zA-Z0-9_]+)__(.+)$/', $key, $matches)) {
            $section = $matches[1];
            $item = $matches[2];
            $config[$section][$item] = $value;
        } else {
            error_log("Ignored POST key: $key");
        }
    }

    // Save the updated INI
    $output = "";
    foreach ($config as $section => $settings) {
        $output .= "[$section]\n";
        foreach ($settings as $k => $v) {
            $output .= "$k=$v\n";
        }
        $output .= "\n";
    }
    file_put_contents($configFile, $output);

    // Restart if requested
    if (isset($_POST['save_restart'])) {
        exec("sudo systemctl restart m17-gateway");
        $message = "Saved and restarted m17-gateway.";
    } else {
        $message = "Configuration saved.";
    }
}

// Load reflector list
$reflectors = [];

$lines = @file($hostsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    $line = trim($line);

    // Skip comment lines
    if ($line === '' || $line[0] === '#') continue;

    // Split by any whitespace (space or tab)
    $parts = preg_split('/\s+/', $line);

    if (count($parts) >= 3) {
        $name = trim($parts[0]);
        $addr = trim($parts[1]);
        $port = trim($parts[2]);
        $reflectors[$name] = ['address' => $addr, 'port' => $port];
    }
}
?>
<!DOCTYPE html>
<html>
<?php include 'header.php';?>
    <script>
        const reflectorMap = <?= json_encode($reflectors) ?>;
        function updateReflectorFields(select) {
            const val = select.value;
            if (reflectorMap[val]) {
                document.getElementsByName('Reflector__Address')[0].value = reflectorMap[val].address;
                document.getElementsByName('Reflector__Port')[0].value = reflectorMap[val].port;
            }
        }
    </script>

    <h2>&nbsp;</h2>

    <?php if (isset($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="POST">
	<table id="info_panel">
        <?php foreach ($config as $section => $items): ?>
	    <tr>
	    <th colspan="2"><?= htmlspecialchars($section) ?></th>
	    </tr>
                <?php foreach ($items as $key => $val): ?>
		    <tr>
                        <td><?= htmlspecialchars($key) ?>:</td>
			<td>
                        <?php if ($section === 'Reflector' && $key === 'Name'): ?>
                            <select name="<?= $section . '__' . $key ?>" onchange="updateReflectorFields(this)">
                                <?php foreach ($reflectors as $name => $data): ?>
                                    <option value="<?= htmlspecialchars($name) ?>" <?= ($val === $name ? 'selected' : '') ?>>
                                        <?= htmlspecialchars($name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($section === 'Reflector' && $key === 'Module'): ?>
                            <select name="<?= $section . '__' . $key ?>">
                                <?php foreach (range('A', 'Z') as $letter): ?>
                                    <option value="<?= $letter ?>" <?= ($val === $letter ? 'selected' : '') ?>><?= $letter ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" name="<?= $section . '__' . $key ?>" value="<?= htmlspecialchars($val) ?>">
                        <?php endif; ?>
			</td>
                <?php endforeach; ?>
		</tr>
        <?php endforeach; ?>
	    <tr>
	    <th colspan="2">Configuration</th>
	    </tr>
	    <tr>
	    <td>Save Config</td>
	    <td><button type="submit" name="save">Save</button> &nbsp; &nbsp; <button type="submit" name="save_restart">Save and Restart</button></td>
	    </tr>
	</table>
<br><br><br>
    </form>
<br><br><br>
</body>
</html>

