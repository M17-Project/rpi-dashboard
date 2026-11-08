</main>
<div class="footer">
  <div class="footer-left">
    <a href="https://github.com/M17-Project/rpi-dashboard" target="_blank" rel="noopener">rpi-dashboard <?= h(DASHBOARD_VERSION) ?></a>|
	<a href="https://github.com/jancona/m17" target="_blank" rel="noopener">m17-gateway <?= h(gatewayVersion()) ?></a>|
<?php
// The modem as the gateway last reported it: the short form here, the whole
// ident on hover when the short form leaves something out
$footerModem = modemIdent($config['gateway_log_file']);
$hwFull = identOneLine($footerModem['ident']);
$hwShort = identShort($footerModem['modem'], $footerModem['ident']);
?>
	<?php if ($hwFull === ''): ?>
	<span>HW: unknown</span>
	<?php else: ?>
	<span<?= $hwShort !== $hwFull ? ' title="' . h($hwFull) . '"' : '' ?>><?= h($hwShort) ?></span>
	<?php endif; ?>
  </div>
</div>
</body>
</html>
