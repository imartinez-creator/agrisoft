<?php
/* ===== Càrrega de fitxers necessaris ===== */
require_once __DIR__ . '/../app/config/db.php';       // Connexió a la base de dades
require_once __DIR__ . '/../app/middleware/auth.php';  // Control d'accés
require_once __DIR__ . '/../app/helpers/flash.php';    // Missatges flash

// Comprova que l'usuari hagi iniciat sessió
require_login();


/* ===== Funció per comptar registres d'una taula ===== */
// Rep el nom de la taula i una condició opcional, retorna el nombre de files
function count_table(string $table, string $where = '1=1'): int {
  try {
    $st = db()->query("SELECT COUNT(*) AS c FROM `$table` WHERE $where");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return (int)($row['c'] ?? 0);
  } catch (PDOException $e) {
    return 0; // Si la taula no existeix o hi ha error, retornem 0
  }
}

/* ===== KPIs (indicadors clau) per al tauler ===== */
// Comptem el total de cada entitat principal per mostrar-ho com a resum
$kpis = [
  'parcel·les'           => count_table('parcela'),
  'tractaments'          => count_table('tractaments'),
  'treballadors'         => count_table('treballadors'),
  'hores registrades'    => count_table('resgistres_treball'),
];


/* ===== Obtenir parcel·les amb coordenades GPS per al mapa ===== */
$st = db()->query("SELECT id, name, gps_lat, gps_lng FROM parcela WHERE gps_lat IS NOT NULL AND gps_lng IS NOT NULL");
$parcelles = $st->fetchAll(PDO::FETCH_ASSOC);

/* ===== Títol de la pàgina i capçalera HTML ===== */
$titol = "Tauler · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<!-- ===== Targetes amb els KPIs ===== -->
<div class="grid">
  <?php foreach($kpis as $k=>$v): ?>
    <div class="card span3">
      <div class="kpi">
        <div>
          <div class="small"><?= htmlspecialchars($k) ?></div>
          <div class="n"><?= (int)$v ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- ===== Mapa amb les parcel·les ===== -->
  <div class="card span8">
    <h2>Mapa de parcel·les</h2>
    <div id="map" style="height:320px;border-radius:14px;border:1px solid var(--border);"></div>
  </div>

  <!-- Leaflet CSS i JS per al mapa -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Creem el mapa centrat a Catalunya
      const map = L.map('map').setView([41.5, 1.5], 8);

      // Afegim la capa de mapa d'OpenStreetMap
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
      }).addTo(map);

      // Passem les parcel·les de PHP a JavaScript
      const parcelles = <?= json_encode($parcelles) ?>;
      const markers = [];

      // Creem un marcador per cada parcel·la que tingui coordenades
      parcelles.forEach(p => {
        if (p.gps_lat && p.gps_lng) {
          const m = L.marker([p.gps_lat, p.gps_lng])
            .bindPopup(`<b>${p.name}</b><br><a href="parcelles.php?id=${p.id}">Veure detalls</a>`)
            .addTo(map);
          markers.push(m);
        }
      });

      // Ajustem el zoom perquè es vegin totes les parcel·les
      if (markers.length > 0) {
        const group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.1));
      }
    });
  </script>

  <!-- ===== Accions ràpides ===== -->
  <div class="card span4">
    <h2>Accions ràpides</h2>
    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
      <a class="btn" href="parcelles.php">+ Nova parcel·la</a>
      <a class="btn" href="tractaments.php">+ Tractament</a>
    </div>
    <hr>
    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
      <a class="btn" href="personal.php">Veure personal</a>
      <a class="btn" href="registre_hores.php">+ Registre hores</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
