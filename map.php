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
var map = L.map('map').setView([20,0], 2);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    maxZoom:18
}).addTo(map);

fetch('get_coordinates.php')
  .then(r=>r.json())
  .then(data=>{
    if(!Array.isArray(data)) return;
    data.forEach(p=>{
      if(!p.lat || !p.lon) return;
      L.marker([p.lat, p.lon]).addTo(map)
       .bindPopup(`<strong>${p.callsign||''}</strong><br>${p.location||''}`);
    });
});
</script>

<?php include 'footer.php'; ?>
