<?php
include 'functions.php';

?>
<!DOCTYPE html>
<html>
<?php include 'header.php';?>
<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<!-- Maplibre GL -->
<link href="https://unpkg.com/maplibre-gl/dist/maplibre-gl.css" rel="stylesheet" />
<script src="https://unpkg.com/maplibre-gl/dist/maplibre-gl.js"></script>

<!-- Maplibre GL Leaflet  -->
<script src="https://unpkg.com/@maplibre/maplibre-gl-leaflet/leaflet-maplibre-gl.js"></script>

<div id="usermap"></div>

<script>
    const map = L.map('usermap').setView([0, 0], 2); // World center, zoomed out

    L.maplibreGL({
      style: 'https://tiles.openfreemap.org/styles/liberty',
    }).addTo(map)

    // Fetch coordinates from PHP and add markers
    fetch('get_coordinates.php')
        .then(response => response.json())
        .then(locations => {
            locations.forEach((loc) => {
                const marker = L.marker([loc.lat, loc.lng])
                    .bindPopup(loc.label)
                    .addTo(map);

                marker.on('click', function(mrk) {
                    map.setView([mrk.latlng.lat, mrk.latlng.lng], 6);
                });
            });
        })
        .catch(error => console.error('Error fetching locations:', error));
</script>

</div>

<?php include 'footer.php';?>

</body>
</html>
