<?php
$page='messages'; include 'header.php';
$cfg=include 'config.php';
$sms_max = $cfg['sms_max'] ?? 20;
?>
<h2>Text messages</h2>
<div class="table-card">
<table id="sms">
<thead><tr><th>Time</th><th>From</th><th>To</th><th>Message</th></tr></thead>
<tbody></tbody>
</table>
</div>
<script>
var sms_max = <?php echo $sms_max; ?>;
function updateSMS(){
 $.getJSON('get_lastheard.php', data=>{
   if(!Array.isArray(data)) return;
   let rows='';
   let msgs=[];
   data.forEach(e=>{
     if(e.subtype==='Packet' && e.smsMessage){
       msgs.push(e);
     }
   });
   msgs = msgs.slice(0, sms_max);
   msgs.forEach(e=>{
     rows += `<tr>
       <td>${e.time||''}</td>
       <td>${e.src||''}</td>
       <td>${e.dst||''}</td>
       <td>${e.smsMessage||''}</td>
     </tr>`;
   });
   $('#sms tbody').html(rows);
 });
}
$(function(){updateSMS(); setInterval(updateSMS,1000);});
</script>
<?php include 'footer.php'; ?>
