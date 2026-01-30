<?php
session_start();
header('Content-Type: application/json');

$_SESSION['radio_status']  ??= 'Listening';
$_SESSION['connected_ref'] ??= '-';
$_SESSION['connected_mod'] ??= '-';

// check if m17_gateway is running
exec('pgrep -x m17-gateway', $out, $rc);
$gw_status = ($rc === 0) ? 'Running' : 'Inoperational';

echo json_encode([
    'connected_ref'  => $_SESSION['connected_ref'],
    'connected_mod'  => $_SESSION['connected_mod'],
    'radio_status' => $_SESSION['radio_status'] ?: 'Listening',
    'gateway_status' => $gw_status
]);
?>
