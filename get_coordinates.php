<?php
header('Content-Type: application/json');
/**
// Generate random coordinates around a base point
function generateRandomLocations($baseLat, $baseLng, $count) {
    $locations = [];
    for ($i = 0; $i < $count; $i++) {
        $randomLat = $baseLat + (mt_rand(-5000, 5000) / 100000.0); // +/- ~0.05 deg (~5km)
        $randomLng = $baseLng + (mt_rand(-5000, 5000) / 100000.0);
        $locations[] = [
            'lat' => $randomLat,
            'lng' => $randomLng,
            'label' => "<b>SP5WWP-" . ($i+1) . "</b><br>From PHP"
        ];
    }
    return $locations;
}

$baseLat = 52.43098341616264;
$baseLng = 20.715232454238752;
$count = isset($_GET['count']) ? intval($_GET['count']) : 10;

echo json_encode(generateRandomLocations($baseLat, $baseLng, $count));
**/

header('Content-Type: application/json');

function generateRandomWorldLocations($count) {
    $locations = [];
    for ($i = 0; $i < $count; $i++) {
        // Latitude: -90 to 90
        $randomLat = mt_rand(-9000000, 9000000) / 100000.0;
        // Longitude: -180 to 180
        $randomLng = mt_rand(-18000000, 18000000) / 100000.0;

        $locations[] = [
            'lat' => $randomLat,
            'lng' => $randomLng,
            'label' => "<b>Marker-" . ($i + 1) . "</b><br>Random Global Location"
        ];
    }
    return $locations;
}

$count = isset($_GET['count']) ? intval($_GET['count']) : 50;
echo json_encode(generateRandomWorldLocations($count));
?>
