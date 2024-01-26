<?php
    exec("sudo shutdown now", $output, $retval);
	
    header("Location:index.php");
?>
