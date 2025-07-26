<!DOCTYPE html>
<html>
<?php
include 'header.php';

$configFile = 'config.php';
$config = include $configFile;

function get_string_between($string, $start, $end)
{
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

// set defaults in case we don't have the data at hand
$uart='unknown';
$rxf='unknown';
$rxf='unknown';
$txp='unknown';
$fcorr='unknown';
$afc='unknown';
$brate='460800';
$name='unknown';
$module='unknown';

// Read the gateway config file
$gateway_config = parse_ini_file($config['gateway_config_file'], true);

// populate all variables for the device info and interface info panel
// with the values from the gateway config file
if (isset($gateway_config['Radio'])) {
    $radio = $gateway_config['Radio'];
    if (isset($radio['RXFrequency'])) {
        $rxf=$radio['RXFrequency'];
    }
    if (isset($radio['TXFrequency'])) {
        $txf=$radio['TXFrequency'];
    }
    if (isset($radio['Power'])) {
        $txp=$radio['Power'];
    }
    if (isset($radio['FrequencyCorr'])) {
        $fcorr=$radio['FrequencyCorr'];
    }
    if (isset($radio['AFC'])) {
        $afc=$radio['AFC'];
    }
}
if (isset($gateway_config['Modem'])) {
    $modem = $gateway_config['Modem'];
    if (isset($modem['Speed'])) {
        $brate=$modem['Speed'];
    }
    if (isset($modem['Port'])) {
        $uart=$modem['Port'];
    }
}
if (isset($gateway_config['General'])) {
    $general = $gateway_config['General'];
    if (isset($general['Callsign'])) {
        $node=$general['Callsign'];
    }
}
if (isset($gateway_config['Reflector'])) {
    $reflector = $gateway_config['Reflector'];
    if (isset($reflector['Name'])) {
        $refl=$reflector['Name'];
    }
    if (isset($reflector['Module'])) {
        $module=$reflector['Module'];
    }
}

// Build the left table on the dashboard
echo '<table id="info_panel">';
echo '<tr>';
echo '<th colspan="2">Device info</th>';
echo '</tr>';
echo '<tr>';
echo '<td>RX frequency</td>';
echo '<td>'.$rxf.' Hz</td>';
echo '</tr>';
echo '<tr>';
echo '<td>TX frequency</td>';
echo '<td>'.$txf.' Hz</td>';
echo '</tr>';
echo '<tr>';
echo '<td>TX power</td>';
echo '<td>'.$txp.' dBm</td>';
echo '</tr>';
echo '<tr>';
echo '<td>Freq. correction</td>';
echo '<td>'.$fcorr.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>AFC</td>';
echo '<td>'.$afc.'</td>';
echo '</tr>';
echo '<tr>';
echo '<th colspan="2">Interface info</th>';
echo '</tr>';
echo '<tr>';
echo '<td>Device</td>';
echo '<td>'.$uart.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>Baudrate</td>';
echo '<td>'.$brate.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>Callsign (ID)</td>';
echo '<td>'.$node.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>Reflector</td>';
echo '<td>'.$refl.'</td>';
echo '</tr>';
echo '<tr>';
echo '<td>Module</td>';
echo '<td>'.$module.'</td>';
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
    // Pass dashboard log file to JavaScript
    const foo = <?php echo json_encode($config['gateway_log_file']); ?>;
</script>
<script src="script.js"></script> 
</body>
</html>
