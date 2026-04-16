<?php
/* ===== Mapa de Parcel·les (Mòdul d'Interfície Geogràfica) ===== */
// Permet veure la llista de parcel·les en una interfície global

require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

// Obté les parcel·les que tinguin `lat` i `lng` de forma prioritària, 
// i per compatibilitat previà, també les que tinguin gps_lat.
// S'utilitza un sub-select o GROUP BY per agafar un cultiu relacionat.
$query = "
  SELECT 
    p.id, p.name, 
    COALESCE(p.lat, p.gps_lat) AS final_lat, 
    COALESCE(p.lng, p.gps_lng) AS final_lng,
    p.area_ha,
    (SELECT c.name FROM sectors s LEFT JOIN cultius c ON s.cultiu_id = c.id WHERE s.parcela_id = p.id AND c.name IS NOT NULL LIMIT 1) as main_crop
  FROM `parcela` p
  HAVING final_lat IS NOT NULL AND final_lng IS NOT NULL
";

$parcels = db()->query($query)->fetchAll(PDO::FETCH_ASSOC);

$titol = 'Mapa General · AGRISOFT';
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">
  <div class="card span12">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <div>
        <h2>Mapa d'Explotació</h2>
        <p class="small">Llista gràfica del terreny i parcel·les per cultiu.</p>
      </div>
      <a href="parcelles.php" class="btn secondary">Gestionar Parcel·les</a>
    </div>

    <!-- Contenidor per al mapa -->
    <div id="generalMap" style="width: 100%; height: 600px; border-radius: 8px; z-index: 1;"></div>
    
  </div>
</div>

<!-- Estils per Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<!-- Script de Leaflet -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
  var rawParcels = <?= json_encode($parcels, JSON_UNESCAPED_UNICODE) ?>;
  
  // 1. Inicialitzar el mapa
  var map = L.map('generalMap');
  
  // 2. Capa base OpenStreetMap
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap contributors'
  }).addTo(map);

  // Funcions auxiliars per pintar marcadors amb colors per defecte de Leaflet o un color sòlid si vull
  function getCustomMarkerColor(cropName) {
      if (!cropName) return 'grey';
      
      const crop = cropName.toLowerCase();
      if (crop.includes('olivera') || crop.includes('oliveres')) return 'green';
      if (crop.includes('ametller')) return 'orange';
      if (crop.includes('vinya')) return 'purple';
      if (crop.includes('cereal') || crop.includes('blat')) return 'gold';
      if (crop.includes('fruiter') || crop.includes('poma')) return 'red';
      return 'blue';
  }

  // Define marker custom (Leaflet no té canviador i per defecte fa servir icones SVG URL)
  var markerColors = {};
  var bounds = L.latLngBounds();
  var featuresCount = 0;

  // 3. Crear marcadors
  rawParcels.forEach(function(p) {
    var lat = parseFloat(p.final_lat);
    var lng = parseFloat(p.final_lng);
    var crop = p.main_crop ? p.main_crop : 'Sense Cultiu Específicat';
    
    // Custom Icon SVG per tenir color
    var color = getCustomMarkerColor(crop);
    var svgIcon = L.divIcon({
      className: "custom-svg-icon",
      html: `<svg width="24" height="36" viewBox="0 0 24 36" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 0C5.372 0 0 5.373 0 12c0 8.25 12 24 12 24s12-15.75 12-24c0-6.627-5.373-12-12-12zm0 18c-3.314 0-6-2.686-6-6s2.686-6 6-6 6 2.686 6 6-2.686 6-6 6z" fill="${color}"/>
             </svg>`,
      iconSize: [24, 36],
      iconAnchor: [12, 36],
      popupAnchor: [0, -36]
    });

    // Crear marcador amb HTML descripció popup
    var marker = L.marker([lat, lng], { icon: svgIcon }).addTo(map);
    
    var popupContent = `
      <div style="font-family: var(--font-base), sans-serif; min-width: 150px;">
        <h4 style="margin: 0 0 5px 0;">${p.name} <small style="color: #666; font-weight: normal;">(ID: ${p.id})</small></h4>
        <p style="margin: 0 0 5px 0; font-size: 0.9em;"><strong>Cultiu:</strong> ${crop}</p>
        <p style="margin: 0 0 10px 0; font-size: 0.9em;"><strong>Ha:</strong> ${p.area_ha || '0'}</p>
        <a href="parcela_detall.php?id=${p.id}" style="display:inline-block; padding: 4px 8px; background: var(--primary-color); color: white; border-radius: 4px; text-decoration: none; font-size: 0.85em;">Veure Detalls</a>
      </div>
    `;
    
    marker.bindPopup(popupContent);
    bounds.extend([lat, lng]);
    featuresCount++;
  });

  // 4. Centrar mapa automàticament
  if (featuresCount > 0) {
      map.fitBounds(bounds, { padding: [30, 30] });
  } else {
      // Default center si no hi ha marcadores (bàsic per a Catalunya / Espanya com a exemple referència original)
      map.setView([41.59, 1.52], 7);
  }
  
  /* 
  ===========================================
  PREPARAT PER A FASE 2: POLÍGONS (GEOJSON)
  ===========================================
  - Es podria realitzar una petició per renderitzar polygon_geojson original que teniu
  L.geoJSON(parsedGeojsonData, {
    style: function (feature) {
        return {color: feature.properties.color || "blue", weight: 2, opacity: 0.8};
    }
  }).addTo(map);
  */
});
</script>

<style>
.custom-svg-icon svg {
  filter: drop-shadow(1px 2px 2px rgba(0,0,0,0.3));
}
</style>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
