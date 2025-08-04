<?php
header('Content-Type: application/json');

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
?>
