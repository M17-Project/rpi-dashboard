<?php
$page = 'map';
include 'header.php';
?>
<div class="cards">
  <section class="card" style="width:100%;height:600px;position:relative;">
    <h2>Last heard stations map</h2>
    <div id="map" style="height:540px;border-radius:12px;"></div>
  </section>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<script>
const smallIcon = L.icon({
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',

  iconSize:     [16, 26], // default is [25, 41]
  iconAnchor:   [8, 26],
  popupAnchor:  [0, -26],
  shadowSize:   [26, 26]
});

var map = L.map('map').setView([20,0], 2);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    maxZoom:18
}).addTo(map);

fetch('get_coordinates.php')
  .then(r => r.json())
  .then(data => {
    if (!Array.isArray(data)) return;

    const bounds = [];

    data.forEach(p => {
      const lat = Number(p.lat);
      const lon = Number(p.lon);
      if (!Number.isFinite(lat) || !Number.isFinite(lon)) return;

      bounds.push([lat, lon]);

      L.marker([lat, lon], { icon: smallIcon })
        .addTo(map)
        .bindPopup(p.location || '');
    });

    //sounds good in theory, but for a single QSO, it looks ridiculous
    /*if (bounds.length) {
      map.fitBounds(bounds, { padding: [30, 30] });
    }*/
  });
</script>

<?php include 'footer.php'; ?>
