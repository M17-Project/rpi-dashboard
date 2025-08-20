<?php
header('Content-Type: application/json');
include 'functions.php';

$logFile = $config['gateway_log_file'];

// Track new entries to return
$locations = [];

// Read log file
$lines = tailFile($logFile, 1000);

// Function to add new lines with additional info
// to map marker labels, checks if key is there
function addToLabel($e, $key, $unit){
    $ret = "";
    if (isset($e[$key])){
        $ret = "<br>".ucfirst($key).": ".$e[$key].$unit;
    }
    return $ret;
}

foreach ($lines as $line) {
    $entry = json_decode($line, true);

    // Skip if entry is invalid
    if (!$entry) continue;
    if (!isset($entry['src'])) continue;

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

        $time = date('d/m/Y H:i', strtotime($entry['time']));

        // Contruct the label that you will see when you click a pin on the map
        $label = "<b><a href='https://www.qrz.com/db/".$entry['src']."' target=r'_blank'>".$entry['src']."</a></b>"; 
        $label = $label.addToLabel($entry, 'bearing', '°');
        $label = $label.addToLabel($entry, 'speed', ' km/h');
        $label = $label.addToLabel($entry, 'altitude', ' m');
        $label = $label."<br>Time: ".$time;

        // add pin/location to $locations
        $locations[] = [
            'lat' => $entry['latitude'],
            'lng' => $entry['longitude'],
            'call' => $entry['src'],
            'label' => $label
        ];
    }
}
echo json_encode($locations);
?>
