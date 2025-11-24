<?php
include 'functions.php';
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    foreach (['gateway_log_file','gateway_config_file','maxlines','sms_max','timezone','unit_system','map_marker_ttl'] as $k)
        if(isset($_POST[$k])) $config[$k] = $_POST[$k];
    $cfg = "<?php\nreturn " . var_export($config,true) . ";";
    file_put_contents($configFile,$cfg);
    if(function_exists('opcache_invalidate')) opcache_invalidate($configFile,true);
    $message="Configuration saved.";
}
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['run_command'])){
    $cmd=$_POST['run_command'];
    $map=[
        'status'=>'systemctl status m17-gateway.service',
        'start'=>'systemctl start m17-gateway.service',
        'stop'=>'systemctl stop m17-gateway.service',
        'restart'=>'systemctl restart m17-gateway.service',
        'log'=>'tail -n 30 '.$config['gateway_log_file'],
        'showhostfile'=>'cat files/M17Hosts.txt',
        'updatehostfile'=>'curl https://hostfiles.refc....txt -o files/M17Hosts.txt -A "rpi-dashboard"'
    ];
    if(isset($map[$cmd])) $commandOutput=shell_exec($map[$cmd]);
}
$timezones=DateTimeZone::listIdentifiers();
$page='config_dash';
include 'header.php';
?>
<div class="page-content">
<?php if($message):?><div class="card"><p><?=htmlspecialchars($message)?></p></div><?php endif;?>
<div class="card">
<h2>Dashboard configuration</h2>
<form method="post">
<div class="form-grid-2col">
<div class="form-field"><label>M17 Gateway Log File</label><input class="input" name="gateway_log_file" value="<?=htmlspecialchars($config['gateway_log_file'])?>"></div>
<div class="form-field"><label>M17 Gateway Configuration File</label><input class="input" name="gateway_config_file" value="<?=htmlspecialchars($config['gateway_config_file'])?>"></div>
<div class="form-field"><label>Max. Number of Lines</label><input class="input" type="number" name="maxlines" value="<?=htmlspecialchars($config['maxlines'])?>"></div>
<div class="form-field"><label>Max. SMS Messages</label><input class="input" type="number" name="sms_max" value="<?=htmlspecialchars($config['sms_max'])?>"></div>
<div class="form-field"><label>Timezone</label>
<select class="input" name="timezone">
<?php foreach($timezones as $tz):?>
<option value="<?=$tz?>" <?=$tz==$config['timezone']?'selected':''?>><?=$tz?></option>
<?php endforeach;?>
</select></div>
<div class="form-field"><label>Unit System</label>
<select class="input" name="unit_system">
<option value="imperial" <?=$config['unit_system']=='imperial'?'selected':''?>>Imperial</option>
<option value="metric" <?=$config['unit_system']=='metric'?'selected':''?>>Metric</option>
</select></div>
<div class="form-field"><label>Map Marker TTL</label><input class="input" type="number" name="map_marker_ttl" value="<?=htmlspecialchars($config['map_marker_ttl'])?>"></div>
</div>
<div style="margin-top:20px;"><button type="submit" name="save_config" class="btn-primary">Save</button></div>
</form>
</div>
<div class="card">
<h2>Gateway Control</h2>
<form method="post">
<div class="form-grid-2col">
<div class="form-field"><label>M17 Gateway Service</label><br>
<button class="btn-secondary" name="run_command" value="status">Status</button>
<button class="btn-secondary" name="run_command" value="start">Start</button>
<button class="btn-secondary" name="run_command" value="stop">Stop</button>
<button class="btn-secondary" name="run_command" value="restart">Restart</button>
</div>
<div class="form-field"><label>Diagnostics</label><br>
<button class="btn-secondary" name="run_command" value="log">Show Log</button>
<button class="btn-secondary" name="run_command" value="showhostfile">Show Hostfile</button>
<button class="btn-secondary" name="run_command" value="updatehostfile">Update Hostfile</button>
</div>
</div>
</form>
</div>
<?php if(!empty($commandOutput)):?>
<div class="card"><h2>Command Output</h2><pre><?=htmlspecialchars($commandOutput)?></pre></div>
<?php endif;?>
</div>
<?php include 'footer.php'; ?>
