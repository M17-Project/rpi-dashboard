<?php

include 'header.php';

$configFile = 'config.php';
$config = include $configFile;

// Read the gateway config file
$gateway_config = parse_ini_file($config['gateway_config_file'], true);

$txfreq = $gateway_config['Radio']['TXFrequency'];
$txfreq = $txfreq / 1000000; // convert to MHz
$txfreq = number_format($txfreq, 3); // format with 3 decimal places

$rxfreq = $gateway_config['Radio']['RXFrequency'];
$rxfreq = $rxfreq / 1000000; // convert to MHz
$rxfreq = number_format($rxfreq, 3); // format with 3 decimal places
?>

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

<table id="info_panel">
<tr>
  <th colspan="2">Node info</th>
</tr>
<tr>
  <td>RX frequency</td>
  <td><?= htmlspecialchars($rxfreq) ?> MHz</td>
</tr>
<tr>
  <td>TX frequency</td>
  <td><?= htmlspecialchars($txfreq) ?> MHz</td>
</tr>
<tr>
  <th colspan="2">M17 Status</th>
</tr>
<tr>
  <td>Callsign (ID)</td>
  <td><?= htmlspecialchars($gateway_config['General']['Callsign']) ?></td>
</tr>
<tr>
  <td>Reflector</td>
  <td><?= htmlspecialchars($gateway_config['Reflector']['Name']) ?></td>
</tr>
<tr>
  <td>Module</td>
  <td><?= htmlspecialchars($gateway_config['Reflector']['Module']) ?></td>
</tr>
</table>

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
