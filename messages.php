<?php
include 'functions.php';
$page='messages'; include 'header.php';
?>
<h2>Messaging</h2>
<div class="table-card">
<table id="sms">
<thead><tr><th>Time</th><th>From</th><th>To</th><th>Message</th></tr></thead>
<tbody></tbody>
</table>
</div>
<script>
// Message text comes from RF and from the reflector: always escape it
function updateSMS(){
 $.getJSON('get_lastheard.php?view=sms', data=>{
   if(!Array.isArray(data)) return;
   let rows='';
   data.forEach(e=>{
     rows += `<tr>
       <td>${esc(e.time)}</td>
       <td>${qrzLink(e.src)}</td>
       <td>${esc(e.dst)}</td>
       <td>${esc(e.smsMessage)}</td>
     </tr>`;
   });
   $('#sms tbody').html(rows);
 });
}
$(function(){updateSMS(); setInterval(updateSMS,2000);});
</script>
<?php include 'footer.php'; ?>
