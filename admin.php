<?php
$configFile = 'config.php';
$config = include $configFile;

$message = "";

// Handle form submission to update config
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_config'])) {
    $newDashboardLogFile = $_POST['gateway_log_file'] ?? $config['gateway_log_file'];
    $newNodeConfigFile = $_POST['gateway_config_file'] ?? $config['gateway_config_file'];
    $newHostfile = $_POST['hostfile'] ?? $config['hostfile'];

    // Safely escape input for use in PHP code
    $newDashboardLogFile = addslashes($newDashboardLogFile);
    $newNodeConfigFile = addslashes($newNodeConfigFile);
    $newHostfile = addslashes($newHostfile);

    $newConfig = <<<PHP
<?php
return [
    'gateway_log_file' => '$newDashboardLogFile',
    'gateway_config_file' => '$newNodeConfigFile',
    'hostfile' => '$newHostfile',
];
PHP;

    // Write new configuration
    file_put_contents($configFile, $newConfig);

    // Invalidate PHP opcode cache
    if (function_exists('opcache_invalidate')) {
        opcache_invalidate($configFile, true);
    }

    // Re-load updated config
    $config = include $configFile;
    $message = "Configuration updated successfully.";

    } elseif (isset($_POST['run_command'])) {
        $selectedCommand = $_POST['run_command'];

        switch ($selectedCommand) {
            case 'status':
                $command = 'systemctl status m17-gateway.service';
                break;
            case 'processes':
                $command = 'ps aux';
                break;
            case 'pwd':
                $command = 'pwd';
                break;
            default:
                $command = '';
        }

        if ($command) {
            $commandOutput = shell_exec($command);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<?php include 'header.php';?>

<?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>

<table><tr><td>


<form method="post">
    <table id="config_panel">

    <tr>
        <th colspan="2">Dashboard Configuration</th>
    </tr>
    <tr>
        <td>M17 Gateway Log File</td>
        <td><input type="text" id="gateway_log_file" name="gateway_log_file" value="<?= htmlspecialchars($config['gateway_log_file']) ?>" required></td>
    </tr>
    <tr>
        <td>M17 Gateway Configuration File</td>
        <td><input type="text" id="gateway_config_file" name="gateway_config_file" value="<?= htmlspecialchars($config['gateway_config_file']) ?>" required></td>
    </tr>
    <tr>
        <td>Hostfile</td>
        <td><input type="text" id="hostfile" name="hostfile" value="<?= htmlspecialchars($config['hostfile']) ?>" required></td>
    </tr>
    <tr>
        <th colspan="2"><input name="save_config" type="submit" value="Save Configuration"></th>
    </tr>
    </table>
</form>

</td></tr>
<tr><td>...</td></tr>

<tr><td>

<form method="post" style="display: flex; gap: 10px; flex-wrap: wrap;">
    <table id="config_panel">
    <tr>
        <th colspan="2">Execute Commands</th>
    </tr>
    <tr>
        <td>test 1</td>
        <td><button type="submit" name="run_command" value="status">M17 Gateway Status</button></td>
    </tr>
    <tr>
        <td>test 2</td>
        <td><button type="submit" name="run_command" value="processes">Show Running Processes</button></td>
    </tr>
    <tr>
        <td>test 3</td>
        <td><button type="submit" name="run_command" value="pwd">Show Current Directory</button></td>
    </tr>
    </table>
</form>
</td></tr>
</table>

<?php if (!empty($commandOutput)): ?>
    <h3>Command Output:</h3>
    <pre><?= htmlspecialchars($commandOutput) ?></pre>
<?php endif; ?>
<?php include 'footer.php';?>
</body>
</html>
