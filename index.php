<?php
$page = 'dashboard';
include 'header.php';

include 'functions.php';
$gateway_config = parse_ini_file($config['gateway_config_file'], true);
$txfreq = number_format($gateway_config['Radio']['TXFrequency']/1000000,3);
$rxfreq = number_format($gateway_config['Radio']['RXFrequency']/1000000,3);
?>
<div class="cards">
<section class="card"><h2>Transceiver info</h2>
<p>RX frequency <strong><?php echo $rxfreq; ?> MHz</strong></p>
<p>TX frequency <strong><?php echo $txfreq; ?> MHz</strong></p>
<p>Power <strong>0 dBm</strong></p>
</section>
<section class="card"><h2>Gateway status</h2>
<p>Callsign <strong id="gw_callsign"><?php echo $gateway_config['General']['Callsign'] ?? 'N/A'; ?></strong></p>
<p>Gateway <strong id="gw_status" class="status-good"></strong></p>
<p>Reflector <strong id="gw_ref"></strong></p>
<p>Module <strong id="gw_mod"></strong></p>
<p>Radio state <strong id="gw_radio" class="status-good"></strong></p>
</section></div>
<h2>Recent activity</h2>
<div class="table-card"><table id="lastheard">
<thead><tr>
<th>Time</th><th>Source</th><th>Destination</th><th>Interface</th>
<th>Type</th><th>CAN</th><th title="Bit error rate before error correction">BER</th><th>Duration</th>
</tr></thead><tbody></tbody></table></div>
<script>
function updateStatus(){fetch('get_status.php').then(r=>r.json()).then(d=>{
if(!d)return;
let g=document.getElementById('gw_status');
let r=document.getElementById('gw_radio');
document.getElementById('gw_ref').textContent=d.connected_ref||'-';
document.getElementById('gw_mod').textContent=d.connected_mod||'-';
r.textContent=d.radio_status||'unknown';
g.textContent=d.gateway_status||'unknown';
g.className=(g.textContent.toLowerCase()==='running')?'status-good':'status-bad';
r.className=(r.textContent.toLowerCase()==='listening')?'status-good':'status-bad';
});}

function updateDashboard(){
$.getJSON('get_lastheard.php',data=>{
let b=$('#lastheard tbody');b.empty();
if(!Array.isArray(data))return;
data.forEach(e=>{
let rc = e.src ? e.src.replace(/[^A-Za-z0-9].*$/, '') : '';
let iface = e.type === 'RF' ? 'RF' : 'Internet';

// Bit error rate before error correction, in %. The colors are set for
// packets, which are lost whole if one frame fails: clean under 0.3%,
// occasional losses up to 1.5%, many above. Voice tolerates more, since a
// bad frame is only a brief glitch, so orange or red on voice means
// marginal rather than broken.
let ber = '<td></td>';
if (iface === 'RF' && Number.isFinite(parseFloat(e.mer))) {
  let v = parseFloat(e.mer);
  let c = v < 0.3 ? 'mer-good' : v < 1.5 ? 'mer-warn' : 'mer-bad';
  ber = `<td class="${c}">${v.toFixed(1)} %</td>`;
}

if (e.subtype === 'Packet') {
  b.append(`<tr class="packet">
    <td>${e.time || ''}</td>
    <td><a href="https://www.qrz.com/db/${rc}" target="_blank">${e.src || ''}</a></td>
    <td>${e.dst || ''}</td>
    <td>${iface}</td>
    <td>Packet</td>
    <td>${e.can ?? ''}</td>
    ${ber}
	<td></td>
  </tr>`);
  return;
}

b.append(`<tr>
  <td>${e.time || ''}</td>
  <td><a href="https://www.qrz.com/db/${rc}" target="_blank">${e.src || ''}</a></td>
  <td>${e.dst || ''}</td>
  <td>${iface}</td>
  <td>${e.subtype || ''}</td>
  <td>${e.can ?? ''}</td>
  ${ber}
  <td>${e.duration || ''}</td>
</tr>`);
});
});}

$(function(){updateStatus();updateDashboard();
setInterval(updateStatus,2000);
setInterval(updateDashboard,2000);});
</script>
<?php include 'footer.php'; ?>
