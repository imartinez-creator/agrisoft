<?php
/* ===== Gestió de Parcel·les i Mapes ===== */
// Permet dibuixar, editar i llistar les parcel·les de l'explotació sobre un mapa interactiu

require_once __DIR__ . '/../app/config/db.php';       // Connexió a la base de dades
require_once __DIR__ . '/../app/middleware/auth.php';  // Control d'accés (autenticació)
require_once __DIR__ . '/../app/helpers/flash.php';    // Missatges flash (avisos a l'usuari)
require_once __DIR__ . '/../app/helpers/forms.php';    // Helpers per a formularis (post_float, post_int...)

// Comprova que l'usuari hagi iniciat sessió
require_login();

// Mirem si l'usuari té permisos de gestió (crear/editar/eliminar)
$can_manage = can_manage();

/* ===== Funció: Construir un polígon GeoJSON ===== */
// Rep una llista de punts [lat, lng] i retorna un JSON amb format GeoJSON Polygon
function build_geojson_polygon(array $ptsLatLng): string {
  $coords = [];
  foreach ($ptsLatLng as $pt) {
    $coords[] = [(float)$pt[1], (float)$pt[0]]; // GeoJSON usa [lng, lat] (invertit)
  }
  $first = $coords[0]; // Primer punt

  // Si el polígon no està tancat, afegim el primer punt al final per tancar-lo
  $last = $coords[count($coords)-1];
  if ($last[0] !== $first[0] || $last[1] !== $first[1]) {
    $coords[] = $first;
  }

  // Retornem el GeoJSON com a string
  return json_encode([
    "type" => "Polygon",
    "coordinates" => [ $coords ]
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/* ===== Funció: Validar i netejar els punts del polígon ===== */
// Rep el JSON cru dels punts i retorna una llista neta de coordenades vàlides
// Retorna [] si les dades no són vàlides o hi ha menys de 3 punts
function sanitize_polygon_points(string $polygon_raw): array {
  $points = json_decode($polygon_raw, true);
  if (!is_array($points) || count($points) < 3) return []; // Mínim 3 punts per fer un polígon

  $clean = [];
  foreach ($points as $pt) {
    if (!is_array($pt) || count($pt) < 2) continue; // Cada punt ha de tenir lat i lng
    $lat = (float)$pt[0];
    $lng = (float)$pt[1];
    // Comprovem que les coordenades siguin vàlides (rang real de latitud i longitud)
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) continue;
    $clean[] = [$lat, $lng];
  }
  return (count($clean) >= 3) ? $clean : [];
}

/* ===== Funció: Calcular el centre (centroide) d'un polígon ===== */
// Fa la mitjana de totes les latituds i longituds per trobar el punt central
function centroid_latlng(array $ptsLatLng): array {
  $sumLat = 0.0; $sumLng = 0.0;
  foreach ($ptsLatLng as $pt) { $sumLat += $pt[0]; $sumLng += $pt[1]; }
  return [$sumLat / max(count($ptsLatLng), 1), $sumLng / max(count($ptsLatLng), 1)];
}

/* ===== Crear una nova parcel·la (formulari POST) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_parcela') {
  // Comprovem permisos
  if (!$can_manage) {
    http_response_code(403);
    flash_set("No tens permisos per crear parcel·les.", "bad");
    header('Location: parcelles.php');
    exit;
  }

  // Recollim les dades del formulari
  $name = trim($_POST['name'] ?? '');           // Nom de la parcel·la
  $notes = trim($_POST['notes'] ?? '');          // Descripció / notes
  $area_ha = (float)($_POST['area_ha'] ?? 0);   // Àrea en hectàrees
  $polygon_raw = $_POST['polygon'] ?? '';         // Punts del polígon (JSON)

  // El nom és obligatori
  if ($name === '') {
    flash_set("El nom és obligatori.", "err");
    header("Location: parcelles.php");
    exit;
  }

  // Validem el polígon (mínim 3 punts vàlids)
  $clean = sanitize_polygon_points($polygon_raw);
  if (count($clean) < 3) {
    flash_set("El polígon no és vàlid.", "err");
    header("Location: parcelles.php");
    exit;
  }

  // Calculem el centre del polígon per guardar les coordenades GPS
  [$gps_lat, $gps_lng] = centroid_latlng($clean);

  // Convertim els punts a format GeoJSON
  $polygon_geojson = build_geojson_polygon($clean);

  // Valors per defecte dels camps addicionals
  $tipus_sol = '';
  $pendent_pct = null;
  $infraestructures = '';

  $lat = post_float('lat');
  $lng = post_float('lng');

  // Inserim la parcel·la dins una transacció (si falla, es desfà tot)
  $pdo = db();
  $pdo->beginTransaction();

  try {
    // Inserim la parcel·la a la taula 'parcela'
    $st = $pdo->prepare("
      INSERT INTO `parcela`
        (`name`, `lat`, `lng`, `gps_lat`, `gps_lng`, `area_ha`, `tipus_sòl`, `pendent_pct`, `infraestructures`, `notes`, `polygon_geojson`)
      VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $st->execute([
      $name, $lat, $lng, $gps_lat, $gps_lng, $area_ha, $tipus_sol, $pendent_pct, $infraestructures, $notes, $polygon_geojson
    ]);

    // Obtenim l'ID de la parcel·la acabada de crear
    $parcela_id = (int)$pdo->lastInsertId();

    // Guardem cada punt del polígon a la taula 'parcela_punt'
    $stp = $pdo->prepare("
      INSERT INTO `parcela_punt` (`parcela_id`, `idx`, `lat`, `lng`)
      VALUES (?, ?, ?, ?)
    ");
    foreach ($clean as $i => $pt) {
      $stp->execute([$parcela_id, $i, $pt[0], $pt[1]]);
    }

    $pdo->commit(); // Confirmem tots els canvis
    flash_set("Parcel·la creada correctament.", "ok");
  } catch (Throwable $e) {
    $pdo->rollBack(); // Si hi ha error, desfem tots els canvis
    flash_set("No s'ha pogut crear la parcel·la: " . $e->getMessage(), "err");
  }

  header("Location: parcelles.php");
  exit;
}

/* ===== Actualitzar una parcel·la existent (formulari POST) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_parcela') {
  // Comprovem permisos
  if (!$can_manage) {
    http_response_code(403);
    flash_set("No tens permisos per editar parcel·les.", "bad");
    header('Location: parcelles.php');
    exit;
  }

  // Recollim les dades del formulari
  $id = (int)($_POST['parcela_id'] ?? 0);        // ID de la parcel·la a editar
  $name = trim($_POST['name'] ?? '');              // Nou nom
  $notes = trim($_POST['notes'] ?? '');            // Nova descripció
  $area_ha = (float)($_POST['area_ha'] ?? 0);     // Nova àrea
  $polygon_raw = $_POST['polygon'] ?? '';           // Nous punts del polígon

  $lat = post_float('lat');
  $lng = post_float('lng');

  // Validem que hi hagi ID i nom
  if ($id <= 0 || $name === '') {
    flash_set("Falten dades per actualitzar la parcel·la.", "err");
    header("Location: parcelles.php");
    exit;
  }

  // Validem el polígon
  $clean = sanitize_polygon_points($polygon_raw);
  if (count($clean) > 0 && count($clean) < 3) { // Allow empty polygon if manual coords given
    flash_set("El polígon no és vàlid (mínim 3 punts).", "err");
    header("Location: parcelles.php");
    exit;
  }

  // Recalculem el centre i el GeoJSON nomes si hi ha poligon
  $gps_lat = null;
  $gps_lng = null;
  $polygon_geojson = '';
  if (count($clean) >= 3) {
      [$gps_lat, $gps_lng] = centroid_latlng($clean);
      $polygon_geojson = build_geojson_polygon($clean);
  }

  // Actualitzem dins una transacció
  $pdo = db();
  $pdo->beginTransaction();
  try {
    // Actualitzem les dades de la parcel·la
    $st = $pdo->prepare("UPDATE `parcela` SET `name`=?, `lat`=?, `lng`=?, `gps_lat`=?, `gps_lng`=?, `area_ha`=?, `notes`=?, `polygon_geojson`=? WHERE `id`=?");
    $st->execute([$name, $lat, $lng, $gps_lat, $gps_lng, $area_ha, $notes, $polygon_geojson, $id]);

    // Esborrem els punts antics del polígon
    $pdo->prepare("DELETE FROM `parcela_punt` WHERE `parcela_id`=?")->execute([$id]);

    // Inserim els nous punts del polígon (si n'hi ha)
    if (count($clean) >= 3) {
      $stp = $pdo->prepare("INSERT INTO `parcela_punt` (`parcela_id`, `idx`, `lat`, `lng`) VALUES (?, ?, ?, ?)");
      foreach ($clean as $i => $pt) {
        $stp->execute([$id, $i, $pt[0], $pt[1]]);
      }
    }

    $pdo->commit(); // Confirmem
    flash_set("Parcel·la actualitzada correctament.", "ok");
  } catch (Throwable $e) {
    $pdo->rollBack(); // Si falla, desfem
    flash_set("No s'ha pogut actualitzar la parcel·la: " . $e->getMessage(), "err");
  }

  header("Location: parcelles.php");
  exit;
}

/* ===== Eliminar una parcel·la (formulari POST) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_parcela') {
  // Comprovem permisos
  if (!$can_manage) {
    http_response_code(403);
    flash_set("No tens permisos per eliminar parcel·les.", "bad");
    header('Location: parcelles.php');
    exit;
  }

  // Obtenim l'ID de la parcel·la a eliminar
  $id = (int)($_POST['parcela_id'] ?? 0);
  if ($id <= 0) {
    flash_set("ID de parcel·la invàlid.", "err");
    header("Location: parcelles.php");
    exit;
  }

  try {
    // Esborrem la parcel·la de la BD
    db()->prepare("DELETE FROM `parcela` WHERE `id`=?")->execute([$id]);
    flash_set("Parcel·la eliminada.", "ok");
  } catch (Throwable $e) {
    flash_set("No s'ha pogut eliminar la parcel·la: " . $e->getMessage(), "err");
  }

  header("Location: parcelles.php");
  exit;
}


/* ===== Carregar totes les parcel·les de la BD ===== */
$parcelles = db()->query("SELECT * FROM `parcela` ORDER BY `id` DESC")->fetchAll(PDO::FETCH_ASSOC);


/* ===== Construir el GeoJSON FeatureCollection per al mapa ===== */
// Convertim les parcel·les a un format que Leaflet pugui pintar al mapa
$features = [];
foreach ($parcelles as $p) {
  $geo = $p['polygon_geojson'] ?? '';
  if (!$geo) continue; // Si no té polígon, la saltem

  $geom = json_decode($geo, true);
  if (!is_array($geom) || !isset($geom['type'])) continue; // Validem el format

  // Afegim cada parcel·la com a "Feature" del GeoJSON
  $features[] = [
    "type" => "Feature",
    "properties" => [
      "id" => (int)$p['id'],
      "name" => (string)($p['name'] ?? ''),
      "area_ha" => (string)($p['area_ha'] ?? ''),
      "notes" => (string)($p['notes'] ?? '')
    ],
    "geometry" => $geom
  ];
}

// Empaquem totes les features en una FeatureCollection
$parcelles_fc = [
  "type" => "FeatureCollection",
  "features" => $features
];

/* ===== Títol de la pàgina i capçalera HTML ===== */
$titol = "Parcel·les · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<!-- ===== Mapa de parcel·les ===== -->
<div class="grid">
  <div class="card span12">
    <h2>Mapa de parcel·les</h2>
    <p class="small">Dibuixa, edita o elimina parcel·les directament sobre el mapa. El sistema calcula l'àrea automàticament.</p>

    <!-- Barra superior amb hora local i meteo -->
    <div class="parcelles-topbar">
      <div class="parcelles-stat">
        <div class="parcelles-stat__label">Hora local</div>
        <div class="parcelles-stat__value" id="nowLocal">--:--:--</div>
      </div>
      <div class="parcelles-stat">
        <div class="parcelles-stat__label">Meteo (centre mapa)</div>
        <div class="parcelles-stat__value" id="meteoNow">Carregant...</div>
      </div>
    </div>

    <!-- Contenidor del mapa Leaflet -->
    <div id="map" class="parcelles-map"></div>

    <?php if ($can_manage): ?>
    <!-- Formulari per crear/editar parcel·les -->
    <form method="post" id="parcelaForm" class="parcela-form">
      <!-- Camps ocults amb les dades del polígon -->
      <input type="hidden" name="action" id="form_action" value="create_parcela">
      <input type="hidden" name="parcela_id" id="parcela_id" value="">
      <input type="hidden" name="polygon" id="polygon">
      <input type="hidden" name="area_ha" id="area_ha" value="0">

      <div class="parcela-form-grid">
        <!-- Camp del nom -->
        <div class="parcela-field parcela-field--name">
          <label for="name">Nom</label>
          <input name="name" id="name" required placeholder="Ex: Parcela Nord">
          <div class="parcela-mode" id="form_mode">Mode: crear</div>
        </div>

        <!-- Camp de l'àrea (calculada automàticament, no editable) -->
        <div class="parcela-field parcela-field--area">
          <label for="area_ha_view">Àrea (ha)</label>
          <input id="area_ha_view" type="number" step="0.0001" value="0" disabled>
        </div>

        <!-- Coordenades manuals -->
        <div class="parcela-field">
          <label for="lat">Latitud</label>
          <input name="lat" id="lat" placeholder="41.38506">
        </div>
        <div class="parcela-field">
          <label for="lng">Longitud</label>
          <input name="lng" id="lng" placeholder="2.17340">
        </div>

        <!-- Camp de descripció -->
        <div class="parcela-field parcela-field--notes" style="grid-column: span 12;">
          <label for="notes">Descripció</label>
          <textarea name="notes" id="notes" placeholder="Descripció / notes..."></textarea>
        </div>
      </div>

      <!-- Botons d'acció (només si l'usuari té permisos) -->
      <div class="parcela-form-actions">
        <button class="btn" id="btnSave" type="submit" disabled>Guardar</button>
        <button class="btn btn-secondary" id="btnCancel" type="button" style="display:none;">↩️ Cancel·lar</button>
        <button class="btn btn-secondary" id="btnClear" type="button">Esborrar dibuix</button>
      </div>
    </form>
    <?php endif; ?>
  </div>

  <!-- ===== Taula amb la llista de totes les parcel·les ===== -->
  <div class="card span12">
    <h2>Parcel·les</h2>
    <?php if (!$parcelles): ?>
      <p class="small">Encara no hi ha parcel·les.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Hectàrees</th>
            <th>Infraestructures</th>
            <th>Descripció</th>
            <th>GPS</th>
            <th>Creat</th>
            <th>Accions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($parcelles as $p): ?>
            <!-- Cada fila és clicable per fer zoom al mapa -->
            <tr style="cursor:pointer" onclick="zoomToParcela(<?= (int)$p['id'] ?>)">
              <td><?= (int)$p['id'] ?></td>
              <td class="parcela-name"><?= htmlspecialchars($p['name'] ?? '') ?></td>
              <td class="parcela-area"><?= htmlspecialchars($p['area_ha'] ?? '') ?></td>
              <td><?= htmlspecialchars($p['infraestructures'] ?? '') ?></td>
              <td class="parcela-desc"><?= nl2br(htmlspecialchars($p['notes'] ?? '')) ?></td>
              <td class="small"><?= htmlspecialchars($p['gps_lat'] ?? '') ?>, <?= htmlspecialchars($p['gps_lng'] ?? '') ?></td>
              <td class="small"><?= htmlspecialchars($p['creat'] ?? '') ?></td>
              <td>
                <?php if ($can_manage): ?>
                  <!-- Botó veure detall -->
                  <a class="btn btn-action" href="parcela_detall.php?id=<?= (int)$p['id'] ?>" onclick="event.stopPropagation();" title="Veure detall">👁️</a>
                  <!-- Botó editar -->
                  <button class="btn btn-action btn-action--edit" type="button" onclick="event.stopPropagation(); editParcela(<?= (int)$p['id'] ?>)">✏️</button>
                  <!-- Formulari per eliminar -->
                  <form method="post" style="display:inline" onsubmit="return confirm('Segur que vols eliminar aquesta parcel·la?');">
                    <input type="hidden" name="action" value="delete_parcela">
                    <input type="hidden" name="parcela_id" value="<?= (int)$p['id'] ?>">
                    <button class="btn btn-action btn-action--delete" type="submit" onclick="event.stopPropagation();" title="Eliminar" aria-label="Eliminar">🗑️</button>
                  </form>
                <?php else: ?>
                  <a class="btn btn-action" href="parcela_detall.php?id=<?= (int)$p['id'] ?>" onclick="event.stopPropagation();" title="Veure detall">👁️</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<!-- ===== CSS i JS externs per al mapa Leaflet i els seus plugins ===== -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.locatecontrol@0.79.0/dist/L.Control.Locate.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-minimap@3.6.1/dist/Control.MiniMap.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-measure@3.3.1/dist/leaflet-measure.css">

<!-- CSS propi del mapa -->
<link rel="stylesheet" href="assets/css/parcelles-map.css">

<!-- Scripts de Leaflet i plugins -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<script src="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.js"></script>
<script src="https://unpkg.com/leaflet.locatecontrol@0.79.0/dist/L.Control.Locate.min.js"></script>
<script src="https://unpkg.com/leaflet-minimap@3.6.1/dist/Control.MiniMap.min.js"></script>
<script src="https://unpkg.com/leaflet-measure@3.3.1/dist/leaflet-measure.js"></script>
<script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>

<!-- Passem les dades de parcel·les i permisos al JavaScript -->
<script>
  window.AGRISOFT_PARCELLES = <?= json_encode($parcelles_fc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  window.AGRISOFT_RAW_PARCELLES = <?= json_encode($parcelles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  window.AGRISOFT_CAN_MANAGE = <?= $can_manage ? 'true' : 'false' ?>;
</script>

<!-- Script principal del mapa de parcel·les -->
<script src="assets/js/parcelles-map.js"></script>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
