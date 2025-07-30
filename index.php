<!DOCTYPE html>
<html>
<script src="jquery-3.7.1.min.js"></script>
<script>
function updateDashboard() {
    $.ajax({
        url: 'process_log.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            // Only update if there are new entries
            if (data && data.length > 0) {
                // Clear existing table rows except the header
                $('#lastheard tr:not(:first)').remove();

                data.forEach(function(entry) {
                    $('#lastheard').append(
                        `<tr>
                            <td>${entry.time}</td>
                            <td>${entry.src}</td>
                            <td>${entry.dst}</td>
                            <td>${entry.type}</td>
                            <td>${entry.can}</td>
                            <td>${entry.mer || 'N/A'}</td>
                            <td>${entry.duration}</td>
                        </tr>`
                    );
                });
            }
        },
        error: function() {
            console.error('Failed to fetch dashboard data');
        }
    });
}

$(document).ready(function() {
    updateDashboard(); // Initial load
    setInterval(updateDashboard, 1000);
});
</script>

<?php

include 'header.php';
$configFile = 'config.php';
$config = include $configFile;

// Read the gateway config file
$gateway_config = parse_ini_file($config['gateway_config_file'], true);

// Build the left table on the dashboard
echo '<table id="info_panel">';
echo '<tr>';
echo '  <th colspan="2">Device info</th>';
echo '</tr>';
echo '<tr>';
echo '  <td>RX frequency</td>';
echo '  <td>'.htmlspecialchars($gateway_config['Radio']['RXFrequency']).' Hz</td>';
echo '</tr>';
echo '<tr>';
echo '  <td>TX frequency</td>';
echo '  <td>'.htmlspecialchars($gateway_config['Radio']['TXFrequency']).' Hz</td>';
echo '</tr>';
echo '<tr>';
echo '  <td>TX power</td>';
echo '  <td>'.htmlspecialchars($gateway_config['Radio']['Power']).' dBm</td>';
echo '</tr>';
echo '<tr>';
echo '  <td>Freq. correction</td>';
echo '  <td>'.htmlspecialchars($gateway_config['Radio']['FrequencyCorr']).'</td>';
echo '</tr>';
echo '<tr>';
echo '  <td>AFC</td>';
echo '  <td>'.htmlspecialchars($gateway_config['Radio']['AFC']).'</td>';
echo '</tr>';
echo '<tr>';
echo '  <th colspan="2">Interface info</th>';
echo '  </tr>';
echo '<tr>';
echo '  <td>Device</td>';
echo '  <td>'.htmlspecialchars($gateway_config['Modem']['Port']).'</td>';
echo '</tr>';
echo '<tr>';
echo '  <td>Baudrate</td>';
echo '  <td>'.htmlspecialchars($gateway_config['Modem']['Speed']).'</td>';
echo '</tr>';
echo '<tr>';
echo '  <td>Callsign (ID)</td>';
echo '  <td>'.htmlspecialchars($gateway_config['General']['Callsign']).'</td>';
echo '</tr>';
echo '<tr>';
echo '  <td>Reflector</td>';
echo '  <td>'.htmlspecialchars($gateway_config['Reflector']['Name']).'</td>';
echo '</tr>';
echo '<tr>';
echo '  <td>Module</td>';
echo '  <td>'.htmlspecialchars($gateway_config['Reflector']['Module']).'</td>';
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
    <th>Duration</th>
  </tr>
</table>

<?php include 'footer.php';?>

</body>
</html>
