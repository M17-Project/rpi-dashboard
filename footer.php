<?php ?>
</main>
<div class="footer">
  <div class="footer-left">
    <a href="https://github.com/M17-Project/rpi-dashboard" target="_blank" rel="noopener">rpi-dashboard <?php echo DASHBOARD_VERSION; ?></a>|
	<a href="https://github.com/jancona/m17" target="_blank" rel="noopener">m17-gateway <?php echo trim(shell_exec("dpkg -s m17-gateway | grep '^Version:' | cut -d' ' -f2")); ?></a>
  </div>
</div>
</body>
</html>
