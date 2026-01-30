<?php
session_start();

$configFile = 'config.php';

$defaultConfig = [
    'gateway_log_file' => 'files/dashboard.log',
    'gateway_config_file' => 'files/m17-gateway.ini',
    'hostfile' => 'files/M17Hosts.txt',
    'override_hostfile' => 'files/OverrideHosts.txt',
    'maxlines' => '15',
	'sms_max' => '20',
    'timezone' => 'UTC',
    'unit_system' => 'metric',
    'map_marker_ttl' => '43200',
];

if (!isset($_SESSION['radio_status'])) {
    $_SESSION['radio_status'] = 'Listening';
}

// Create the config file if it doesn't exist
if (!file_exists($configFile)) {
    file_put_contents($configFile, "<?php\nreturn " . var_export($defaultConfig, true) . ";\n");
    $config = $defaultConfig;
} else {
    $config = include $configFile;

    // Add missing keys with default values
    $updated = false;
    foreach ($defaultConfig as $key => $value) {
        if (!array_key_exists($key, $config)) {
            $config[$key] = $value;
            $updated = true;
        }
    }

    // If updates were made, rewrite the config file
    if ($updated) {
        file_put_contents($configFile, "<?php\nreturn " . var_export($config, true) . ";\n");
    }
}


// just read the latest 50 lines from the logfile
// while not touching the rest of the file
function tailFile($filePath, $lines = 50) {
    $f = fopen($filePath, "r");
    if (!$f) return false;

    $buffer = '';
    $chunkSize = 4096; // Read 4KB at a time
    $pos = -1;
    $lineCount = 0;
    $fileSize = filesize($filePath);

    if ($fileSize === 0) {
        fclose($f);
        return [];
    }

    fseek($f, 0, SEEK_END);

    while (ftell($f) > 0 && $lineCount <= $lines) {
        $readSize = ($fileSize - abs($pos) < $chunkSize) ? $fileSize - abs($pos) : $chunkSize;

        // Ensure readSize is never zero or negative
        if ($readSize <= 0) {
            break;
        }

        $pos -= $readSize;
        fseek($f, $pos, SEEK_END);
        $data = fread($f, $readSize);

        if ($data === false) {
            break;  // Stop on fread failure
        }

        $buffer = $data . $buffer;
        $lineCount = substr_count($buffer, "\n");

        if (abs($pos) >= $fileSize) {
            break;  // Stop if we've reached the beginning of the file
        }
    }

    fclose($f);

    $linesArray = explode("\n", $buffer);
    return array_slice($linesArray, -$lines);
}
