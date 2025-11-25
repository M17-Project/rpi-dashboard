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
<p>Gateway <strong id="gw_status" class="status-good">Operational</strong></p>
<p>Reflector <strong id="gw_ref">N/A</strong></p>
<p>Module <strong id="gw_mod">N/A</strong></p>
<p>State <strong id="gw_radio" class="status-good">Listening</strong></p>
</section></div>
<h2>Recent activity</h2>
<div class="table-card"><table id="lastheard">
<thead><tr>
<th>Time</th><th>Source</th><th>Destination</th><th>Interface</th>
<th>Type</th><th>CAN</th><th>MER</th><th>Duration</th>
</tr></thead><tbody></tbody></table></div>
<script>
function updateStatus(){fetch('get_status.php').then(r=>r.json()).then(d=>{
if(!d)return;
let g=document.getElementById('gw_status');
let r=document.getElementById('gw_radio');
document.getElementById('gw_ref').textContent=d.connected_ref||'-';
document.getElementById('gw_mod').textContent=d.connected_mod||'-';
r.textContent=d.radio_status||'Unknown';
g.textContent=d.gateway_status||'unknown';
g.className=(g.textContent.toLowerCase()==='operational')?'status-good':'status-bad';
r.className=(r.textContent.toLowerCase()==='listening')?'status-good':'status-bad';
});}

function updateDashboard(){
$.getJSON('get_lastheard.php',data=>{
let b=$('#lastheard tbody');b.empty();
if(!Array.isArray(data))return;
data.forEach(e=>{
let rc=e.src?e.src.replace(/[^A-Za-z0-9].*$/,''):'';
let mer='';
if(e.iface!=='Internet'&&e.mer!==undefined){
let v=parseFloat(e.mer);let c='';if(!isNaN(v)){
if(v<5)c='mer-good';else if(v<10)c='mer-warn';else c='mer-bad';}
mer=`<td class="${c}">${e.mer}</td>`;
}else mer='<td></td>';
b.append(`<tr>
<td>${e.time||''}</td>
<td><a href="https://www.qrz.com/db/${rc}" target="_blank">${e.src||''}</a></td>
<td>${e.dst||''}</td>
<td>${e.type === "RF" ? "RF" : "Internet"}</td>
<td>${e.subtype || ''}</td>
<td>${e.can !== undefined ? e.can : ''}</td>
${mer}
<td>${e.duration||''}</td></tr>`);
});
});}

$(function(){updateStatus();updateDashboard();
setInterval(updateStatus,5000);
setInterval(updateDashboard,5000);});
</script>
<?php include 'footer.php'; ?>
