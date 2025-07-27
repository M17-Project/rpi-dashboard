<!DOCTYPE html>
<html>
<?php
include 'header.php';

$configFile = 'config.php';
$config = include $configFile;

// Read the gateway config file
$gateway_config = parse_ini_file($config['gateway_config_file'], true);

// Build the left table on the dashboard
echo '<table id="info_panel">';
echo '<tr>';
echo '<th colspan="2">Device info</th>';
echo '</tr>';
echo '<tr>';
echo '<td>RX frequency</td>';
echo '<td>'.htmlspecialchars($gateway_config['Radio']['RXFrequency']).' Hz</td>';
echo '</tr>';
echo '<tr>';
echo '<td>TX frequency</td>';
echo '<td>'.htmlspecialchars($gateway_config['Radio']['TXFrequency']).' Hz</td>';
echo '</tr>';
echo '<tr>';
echo '<td>TX power</td>';
echo '<td>'.htmlspecialchars($gateway_config['Radio']['Power']).' dBm</td>';
echo '</tr>';
echo '<tr>';
echo '<td>Freq. correction</td>';
echo '<td>'.htmlspecialchars($gateway_config['Radio']['FrequencyCorr']).'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>AFC</td>';
echo '<td>'.htmlspecialchars($gateway_config['Radio']['AFC']).'</td>';
echo '</tr>';
echo '<tr>';
echo '<th colspan="2">Interface info</th>';
echo '</tr>';
echo '<tr>';
echo '<td>Device</td>';
echo '<td>'.htmlspecialchars($gateway_config['Modem']['Port']).'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>Baudrate</td>';
echo '<td>'.htmlspecialchars($gateway_config['Modem']['Speed']).'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>Callsign (ID)</td>';
echo '<td>'.htmlspecialchars($gateway_config['General']['Callsign']).'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>Reflector</td>';
echo '<td>'.htmlspecialchars($gateway_config['Reflector']['Name']).'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>Module</td>';
echo '<td>'.htmlspecialchars($gateway_config['Reflector']['Module']).'</td>';
echo '</tr>';
echo '</table>';
?>

<table id="lastheard">
  <tr>
    <th>Time</th>
    <th>Source</th>
    <th>Destination</th>
    <th>Interface</th>
    <th>CAN</th>
    <th>MER</th>
  </tr>
</table>

<?php include 'footer.php';?>

<script>
    // Pass gateway log file to JavaScript
    const gateway_log_file = <?php echo json_encode($config['gateway_log_file']); ?>;
</script>
<script src="script.js"></script> 
</body>
</html>
