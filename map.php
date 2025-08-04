<?php
include 'config_include.php';

?>
<!DOCTYPE html>
<html>
<?php include 'header.php';?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<div id="usermap"></div>

<!-- Initialize & add OpenStreetMap to HTML element -->

<script>
const map = L.map('usermap').setView([52.43098341616264, 20.715232454238752], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png?{foo}', {
    foo: 'bar',
    attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'}
).addTo(map);

// Fetch coordinates from PHP and add markers
fetch('get_coordinates.php?count=20')
    .then(response => response.json())
    .then(locations => {
        locations.forEach((loc) => {
            const marker = L.marker([loc.lat, loc.lng])
                .bindPopup(loc.label)
                .addTo(map);

            marker.on('click', function(mrk) {
                map.setView([mrk.latlng.lat, mrk.latlng.lng], 16);
            });
        });
    })
    .catch(error => console.error('Error fetching locations:', error));

</script>

</div>

<?php include 'footer.php';?>

</body>
</html>
