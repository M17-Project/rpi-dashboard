<?php
session_start();
header('Content-Type: application/json');

// check if m17_gateway is running and if it uses at least 30% of the CPU
$command = "ps -C m17-gateway -o %cpu= | awk 'NR==1 {if (\$1 > 30.0) exit 0; else exit 1} END {if (NR==0) exit 1}'";
exec($command, $output, $return_code);

$gw_status = 'inoperational';

if ($return_code === 0) {
     $gw_status = 'operational';
}

echo json_encode([
    'connected_ref' => $_SESSION['connected_ref'],
    'connected_mod' => $_SESSION['connected_mod'],
    'radio_status' => $_SESSION['radio_status'],
    'gateway_status' => $gw_status
]);
?>
