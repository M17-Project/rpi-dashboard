<?php
include 'functions.php';
$page = 'dashboard';
include 'header.php';

$gateway_config = @parse_ini_file($config['gateway_config_file'], true, INI_SCANNER_RAW) ?: [];
function freqMHz($hz) {
    return is_numeric($hz) ? number_format($hz / 1000000, 3) . ' MHz' : 'N/A';
}
$rxfreq = freqMHz($gateway_config['Radio']['RXFrequency'] ?? null);
$txfreq = freqMHz($gateway_config['Radio']['TXFrequency'] ?? null);
$power = $gateway_config['Radio']['Power'] ?? '';
$power = is_numeric($power) ? $power . ' dBm' : 'N/A';
?>
<div class="cards">
<section class="card"><h2>Transceiver info</h2>
<p>RX frequency <strong><?= h($rxfreq) ?></strong></p>
<p>TX frequency <strong><?= h($txfreq) ?></strong></p>
<p>Power <strong><?= h($power) ?></strong></p>
</section>
<section class="card"><h2>Gateway status</h2>
<p>Callsign <strong id="gw_callsign"><?= h($gateway_config['General']['Callsign'] ?? 'N/A') ?></strong></p>
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
}).catch(()=>{});}

// All values from the log are escaped with esc() before they go into HTML:
// call signs and text messages come from RF and from the reflector.
function updateDashboard(){
$.getJSON('get_lastheard.php',data=>{
if(!Array.isArray(data))return;
let rows='';
data.forEach(e=>{
let iface = e.type === 'RF' ? 'RF' : 'Internet';
let src = qrzLink(e.src) + (e.gnss ? ' &#128752;' : '');

// Bit error rate before error correction, in %. The colors are set for
// packets, which are lost whole if one frame fails: clean under 0.3%,
// occasional losses up to 1.5%, many above. Voice tolerates more, since a
// bad frame is only a brief glitch, so orange or red on voice means
// marginal rather than broken.
let ber = '<td></td>';
if (iface === 'RF' && Number.isFinite(e.mer)) {
  let v = e.mer;
  let c = v < 0.3 ? 'mer-good' : v < 1.5 ? 'mer-warn' : 'mer-bad';
  ber = `<td class="${c}">${v.toFixed(1)} %</td>`;
}

rows += `<tr${e.subtype === 'Packet' ? ' class="packet"' : ''}>
  <td>${esc(e.time)}</td>
  <td>${src}</td>
  <td>${esc(e.dst)}</td>
  <td>${iface}</td>
  <td>${esc(e.subtype)}</td>
  <td>${esc(e.can)}</td>
  ${ber}
  <td>${esc(e.duration)}</td>
</tr>`;
});
$('#lastheard tbody').html(rows);
});}

$(function(){updateStatus();updateDashboard();
setInterval(updateStatus,2000);
setInterval(updateDashboard,2000);});
</script>
<?php include 'footer.php'; ?>
