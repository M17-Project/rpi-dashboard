<?php ?>
</main>
<div class="footer">
  <div class="footer-left">
    M17 Dashboard by SP5WWP, N1ADJ & DK1MI | m17-gateway <?php echo trim(shell_exec("dpkg -s m17-gateway | grep '^Version:' | cut -d' ' -f2")); ?>
  </div>
</div>
</body>
</html>
