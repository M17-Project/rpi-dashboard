<?php
// Set the dashboard admin password from the command line:
//   sudo -u www-data php /opt/m17/rpi-dashboard/set_password.php
// Run it as the web server user so config.php stays writable for the dashboard.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

include __DIR__ . '/functions.php';

function prompt($label) {
    fwrite(STDOUT, $label);
    $tty = stream_isatty(STDIN);
    if ($tty) shell_exec('stty -echo');
    $line = fgets(STDIN);
    if ($tty) {
        shell_exec('stty echo');
        fwrite(STDOUT, "\n");
    }
    return $line === false ? false : rtrim($line, "\r\n");
}

$pw = prompt('New dashboard admin password: ');
if ($pw === false || strlen($pw) < 8) {
    fwrite(STDERR, "The password must be at least 8 characters long.\n");
    exit(1);
}
if (stream_isatty(STDIN) && prompt('Repeat password: ') !== $pw) {
    fwrite(STDERR, "The passwords do not match.\n");
    exit(1);
}

$config['admin_password_hash'] = password_hash($pw, PASSWORD_DEFAULT);
if (!saveConfig($config)) {
    fwrite(STDERR, "Could not write $configFile. Run this as the web server user (www-data).\n");
    exit(1);
}
echo "Password saved.\n";
