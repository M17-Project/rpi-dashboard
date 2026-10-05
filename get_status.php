<?php
header('Content-Type: application/json');
include 'functions.php';

// check if m17_gateway is running
exec('pgrep -x m17-gateway', $out, $rc);
$running = ($rc === 0);

$ref = '-';
$mod = '-';
$radio = 'Listening';

if ($running) {
    // Walk back through the log until both the latest reflector event and the
    // latest voice event are found. This works however long ago the gateway
    // connected; the byte limit keeps a huge log from stalling the request.
    $foundRef = false;
    $foundVoice = false;
    foreach (reverseLines($config['gateway_log_file'], 16 * 1024 * 1024) as $line) {
        $e = json_decode($line, true);
        if (!is_array($e) || !isset($e['type'], $e['subtype'])) continue;

        if (!$foundRef && $e['type'] === 'Reflector') {
            $foundRef = true;
            if ($e['subtype'] === 'Connect') {
                $ref = (string)($e['name'] ?? '-');
                $mod = (string)($e['module'] ?? '-');
            } else {
                $ref = 'Disconnected';
            }
        }

        if (!$foundVoice && ($e['subtype'] === 'Voice Start' || $e['subtype'] === 'Voice End')) {
            $foundVoice = true;
            $start = logTime($e['time'] ?? '');
            // A start with no end for over five minutes means the end was
            // lost (e.g. the gateway restarted), so don't show it forever
            if ($e['subtype'] === 'Voice Start' && $start && time() - $start->getTimestamp() < 300) {
                $radio = ($e['type'] === 'RF' ? 'RX: ' : 'TX: ') . trim((string)($e['src'] ?? ''));
            }
        }

        if ($foundRef && $foundVoice) break;
    }
}

echo json_encode([
    'connected_ref' => $ref,
    'connected_mod' => $mod,
    'radio_status' => $radio,
    'gateway_status' => $running ? 'Running' : 'Inoperational',
]);
