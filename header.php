<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<head>
  <link rel="stylesheet" href="style.css">
  <title>Dashboard</title>
  <link rel="icon" type="image/x-icon" href="img/favicon.png">
</head>
<body>
<div class="header">
<ul>
  <li><a class="active" href="index.php">Dashboard</a></li>
  <li><a href="config_gateway.php">Gateway Config</a></li>
  <li><a href="config_dashboard.php">Dashboard Config</a></li>
  <li><a href="https://m17project.org" target=”_blank”>About</a></li>
  <li><a href="https://www.paypal.com/donate/?hosted_button_id=4HTHZCS8UYPU6" target=”_blank”>Donate</a></li>
</ul>
</div>
<div id="main">
