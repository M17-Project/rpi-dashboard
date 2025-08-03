<?php
session_start();

$configFile = 'config.php';

$defaultConfig = [
    'gateway_log_file' => 'files/dashboard.log',
    'gateway_config_file' => 'files/m17-gateway.ini',
    'hostfile' => 'files/M17Hosts.txt',
    'maxlines' => '15',
    'timezone' => 'UTC',
];

if (!$_SESSION['radio_status']) {
    $_SESSION['radio_status'] = "Listening";
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
