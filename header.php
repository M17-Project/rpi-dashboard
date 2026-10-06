<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
$page = $page ?? '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>M17 Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<script>
(function(){
  try{
    if(localStorage.getItem('t')==='l')
      document.documentElement.classList.add('light');
  }catch(e){}
})();
</script>

<link rel="stylesheet" href="style.css">
<script src="theme.js"></script>
<script src="common.js"></script>
<script src="jquery-3.7.1.min.js"></script>
<link rel="icon" type="image/png" href="img/favicon.png">

</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-header">M17 Dashboard</div>

    <a href="index.php" class="<?php echo ($page=='dashboard')?'active':'';?>"><span class="icon">🏠</span>Dashboard</a>
    <a href="map.php" class="<?php echo ($page=='map')?'active':'';?>"><span class="icon">🗺️</span>Map</a>
    <a href="config_gateway.php" class="<?php echo ($page=='config_gw')?'active':'';?>"><span class="icon">⚙️</span>Gateway</a>
    <a href="config_dashboard.php" class="<?php echo ($page=='config_dash')?'active':'';?>"><span class="icon">🧩</span>Settings</a>
    <a href="messages.php" class="<?php echo ($page=='messages')?'active':'';?>"><span class="icon">💬</span>Messaging</a>
    <a href="help.php" class="<?php echo ($page=='help')?'active':'';?>"><span class="icon">❓</span>Help</a>
    <?php if (function_exists('isAdmin') && isAdmin()): ?>
    <a href="login.php?logout=1"><span class="icon">🔒</span>Log out</a>
    <?php endif; ?>
  
    <div class="sidebar-theme">
      <div class="theme-slider" onclick="toggleTheme()">
        <span class="sun">☀️</span>
        <div class="theme-track"><div class="theme-thumb"></div></div>
        <span class="moon">🌙</span>
      </div>
    </div>
  </aside>


  <main class="main">
