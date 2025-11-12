<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-language: en");

// Fetch the gateway's version
$gateway_version = trim(shell_exec("dpkg -s m17-gateway | grep '^Version:' | cut -d' ' -f2"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="stylesheet" href="style.css">
  <title>Dashboard</title>
  <link rel="icon" type="image/x-icon" href="img/favicon.png">
</head>
<body>
<div class="header">
<ul>
  <li><a class="active" href="index.php">Dashboard</a></li>
  <li><a href="map.php">Map</a></li>
  <li><a href="config_gateway.php">Gateway Config</a></li>
  <li><a href="config_dashboard.php">Dashboard Config</a></li>
  <li><a href="help.php">Help</a></li>
  <li class="right"><span class="static">m17-gateway v<?= htmlspecialchars($gateway_version) ?></span></li>
</ul>
</div>
<div id="main">
