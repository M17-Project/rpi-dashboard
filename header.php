<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-language: en");
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
</ul>
</div>
<div id="main">
