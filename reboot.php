<?php
    exec("sudo reboot", $output, $retval);
	
    header("Location:index.php");
?>
