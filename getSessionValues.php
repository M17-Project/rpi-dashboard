<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'connected_ref' => $_SESSION['connected_ref'],
    'connected_mod' => $_SESSION['connected_mod']
]);
?>
