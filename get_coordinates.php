<?php
header('Content-Type: application/json');
include 'functions.php';

$logFile = $config['gateway_log_file'];
$unit_system = $config['unit_system'];

// Track new entries to return
$locations = [];

// Read log file
$lines = tailFile($logFile, 1000);

// Function to add new lines with additional info
// to map marker labels, checks if key is there
function addToLabel($e, $key, $unit_metric, $unit_imperial, $system){
    $ret = "";
    if (isset($e[$key])){
        $val = $e[$key];
        if ($system == "metric") {
            $ret = "<br>".ucfirst($key).": ".$val.$unit_metric;
        } else {
            if ($key == "altitude") {
                $val = round($e[$key] * 3.28084, 0);
            } else if ($key == "speed") {
                $val = round($e[$key] * 0.621371, 0);
            }
            $ret = "<br>".ucfirst($key).": ".$val.$unit_imperial;
        }
    }
    return $ret;
}

foreach ($lines as $line) {
    $entry = json_decode($line, true);
	if (!$entry || !isset($entry['time'], $entry['src'])) continue;

    $dt = new DateTime($entry['time']);
    $timestamp = $dt->getTimestamp();

    // Skip if entry is invalid
    if (!$entry) continue;
    if (!isset($entry['src'])) continue;
    if (($timestamp + ($config['map_marker_ttl'] * 60)) < time() ) continue;

    // Check if the event is of the subtype GNSS
    if ($entry['subtype'] === 'GNSS') {
        // Delete all previous locations of the current call sign
        // as we only want to see the latest location
        foreach ($locations as $key => $location) {
            if ($location['call'] === $entry['src']) {
                unset($locations[$key]);
            }
        }

        $locations = array_values($locations);

        if ($unit_system == "metric") {
            $time = $dt->format('d/m/Y H:i');
        } else {
            $time = $dt->format('m/d/Y h:i A');
        }

        // Contruct the label that you will see when you click a pin on the map
        $label = "<b><a href='https://www.qrz.com/db/".$entry['src']."' target=r'_blank'>".$entry['src']."</a></b>"; 
        $label = $label.addToLabel($entry, 'bearing', '°', '°', $unit_system);
        $label = $label.addToLabel($entry, 'speed', ' km/h', ' mph', $unit_system);
        $label = $label.addToLabel($entry, 'altitude', ' m', ' ft', $unit_system);
        $label = $label."<br>Time: ".$time;

        // add pin/location to $locations
        $locations[] = [
            'lat' => $entry['latitude'],
            'lon' => $entry['longitude'],
            'callsign' => $entry['src'],
            'location' => $label
        ];
    }
}
echo json_encode($locations);
?>
