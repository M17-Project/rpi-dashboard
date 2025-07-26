<!DOCTYPE html>
<html>
<?php include 'header.php';?>
<br><br><br>

<?php
function get_string_between($string, $start, $end)
{
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

//defaults
$uart='unknown';
$rxf='unknown';
$rxf='unknown';
$txp='unknown';
$fcorr='unknown';
$afc='unknown';
$brate='460800';
$name='unknown';
$module='unknown';

if(strlen($_GET['cfg'])>0)
	$cfg_path='files/'.$_GET['cfg'].'.txt';
else
	$cfg_path='files/default_cfg.txt';
$fp = fopen($cfg_path, "r");

if($fp)
{
    while(!feof($fp))
    {
        $line=fgets($fp, 4096);

        if(strstr($line, "device"))
        {
            $uart=get_string_between($line, "\"", "\"");
        }
        else if(strstr($line, "speed"))
        {
            $brate=get_string_between($line, "=", " ");
        }
        else if(strstr($line, "node"))
        {
            $node=get_string_between($line, "\"", "\"");
        }
        else if(strstr($line, "reflector"))
        {
            $refl=get_string_between($line, "\"", "\"");
        }		
        else if(strstr($line, "module"))
        {
            $module=get_string_between($line, "\"", "\"");
        }
        else if(strstr($line, "rx_freq"))
        {
            $rxf=get_string_between($line, "=", " ");
        }
        else if(strstr($line, "tx_freq"))
        {
            $txf=get_string_between($line, "=", " ");
        }
        else if(strstr($line, "tx_pwr"))
        {
            $txp=get_string_between($line, "=", " ");
        }
        else if(strstr($line, "freq_corr"))
        {
            $fcorr=get_string_between($line, "=", " ");
        }
        else if(strstr($line, "afc"))
        {
            $afc=get_string_between($line, "=", " ");
            if($afc=='1')
                $afc='enabled';
            else
                $afc='disabled';
        }
    }

    fclose($fp);
}

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

<div class="footer">
  <p>SP5WWP's Dashboard<br>M17 Project</p>
</div>

<script src="script.js"></script> 
</body>
</html>
