<?php

include 'header.php';
include 'functions.php';

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

function updateStatus() {
    // Fetch the current session values from the server
    fetch('get_status_info.php') 
        .then(response => response.json())
        .then(data => {
            const refCell = document.getElementById("ref");
            const modCell = document.getElementById("mod");
            const radioStatusCell = document.getElementById("radio_status");

            // Update the text content of the cells
            refCell.textContent = data.connected_ref;
            modCell.textContent = data.connected_mod;
            radioStatusCell.textContent = data.radio_status;

            // Change background color based on the status
            if (refCell.textContent === "Disconnected") {
                refCell.style.backgroundColor = "red";
                modCell.style.backgroundColor = "red";
            } else {
                refCell.style.backgroundColor = "";
                modCell.style.backgroundColor = "";
            }

            if (radioStatusCell.textContent.startsWith("TX")) {
                radioStatusCell.style.backgroundColor = "red";
            } else if (radioStatusCell.textContent.startsWith("RX")) {
                radioStatusCell.style.backgroundColor = "green";
            } else {
                radioStatusCell.style.backgroundColor = "";
            }
        })
        .catch(error => console.error('Error fetching session values:', error));
}

function updateDashboard() {
    $.ajax({
        url: 'get_lastheard.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            // Only update if there are new entries
            if (data && data.length > 0) {
                // Clear existing table rows except the header
                $('#lastheard tr:not(:first)').remove();
                $('#sms tr:not(:first)').remove();

                data.forEach(function(entry) {
		    real_call = entry.src.replace(/[^A-Za-z0-9].*$/, '');
                    $('#lastheard').append(
                        `<tr>
                            <td>${entry.time}</td>
                            <td class='callsign'><a href="https://www.qrz.com/db/${real_call}" target="_blank">${entry.src}</a></td>
                            <td>${entry.dst}</td>
                            <td>${entry.type}</td>
                            <td>${entry.subtype}</td>
                            <td>${entry.can}</td>
                            <td>${entry.mer || 'N/A'}</td>
                            <td>${entry.duration}</td>
                        </tr>`
                    );

		    if (entry.subtype == "Packet" && entry.smsMessage) {
                        $('#sms').append(
                            `<tr>
                                <td><i>${entry.shorttime} ${entry.src} > ${entry.dst}:</i> ${entry.smsMessage}</td>
                            </tr>`
                        );
	            }
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
    updateStatus();
    setInterval(updateStatus, 500);
    setInterval(updateDashboard, 1000);
});
</script>

<div class="db-container">
  <div class="db-left-column">

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
    </table>

    <table class="dashboard" id="info_panel">
      <tr>
        <th colspan="2">M17 Status</th>
      </tr>
      <tr>
        <td>Callsign (ID)</td>
        <td><?= htmlspecialchars($gateway_config['General']['Callsign']) ?></td>
      </tr>
      <tr>
        <td>Reflector</td>
        <td id="ref">N/A</td>
      </tr>
      <tr>
        <td>Module</td>
        <td id="mod">N/A</td>
      </tr>
      <tr>
        <td>Radio Status</td>
        <td id="radio_status">Listening</td>
      </tr>
    </table>

    <table class="dashboard" id="sms">
      <tr>
        <th>Text Messages</th>
      </tr>
      </tr>
    </table>
  </div>

  <div class="db-right-column">
    <table class="dashboard" id="lastheard">
      <tr>
        <th>Time</th>
        <th>Source</th>
        <th>Destination</th>
        <th>Interface</th>
        <th>Type</th>
        <th>CAN</th>
        <th>MER</th>
        <th>Duration</th>
      </tr>
    </table>
  </div>
</div>


<?php include 'footer.php';?>

</body>
</html>
