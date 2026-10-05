<?php
header('Content-Type: application/json');
include 'functions.php';

$metric = ($config['unit_system'] === 'metric');
$oldest = time() - $config['map_marker_ttl'] * 60;

// Latest position per call sign
$locations = [];

foreach (readLogEntries($config['gateway_log_file'], 1000) as $e) {
    if ($e['subtype'] !== 'GNSS' || !isset($e['src'], $e['latitude'], $e['longitude'])) continue;
    if (!is_numeric($e['latitude']) || !is_numeric($e['longitude'])) continue;

    $dt = logTime($e['time']);
    if (!$dt || $dt->getTimestamp() < $oldest) continue;

    $call = trim((string)$e['src']);
    $base = preg_replace('/[^A-Za-z0-9].*$/', '', $call);

    // Label shown when a pin is clicked. Everything from the log is escaped.
    $label = '<b><a href="https://www.qrz.com/db/' . h(rawurlencode($base)) . '" target="_blank" rel="noopener">' . h($call) . '</a></b>';
    if (isset($e['bearing']) && is_numeric($e['bearing'])) {
        $label .= '<br>Bearing: ' . (int)$e['bearing'] . '°';
    }
    if (isset($e['speed']) && is_numeric($e['speed'])) {
        $label .= $metric ? '<br>Speed: ' . round((float)$e['speed'], 1) . ' km/h'
                          : '<br>Speed: ' . round($e['speed'] * 0.621371) . ' mph';
    }
    if (isset($e['altitude']) && is_numeric($e['altitude'])) {
        $label .= $metric ? '<br>Altitude: ' . round((float)$e['altitude'], 1) . ' m'
                          : '<br>Altitude: ' . round($e['altitude'] * 3.28084) . ' ft';
    }
    $label .= '<br>Time: ' . h($dt->format($metric ? 'd/m/Y H:i' : 'm/d/Y h:i A'));

    // Later entries replace earlier ones for the same call sign
    $locations[$call] = [
        'lat' => (float)$e['latitude'],
        'lon' => (float)$e['longitude'],
        'callsign' => $call,
        'location' => $label,
    ];
}

echo json_encode(array_values($locations));
