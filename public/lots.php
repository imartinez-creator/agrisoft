<?php
/* ===== Càrrega de fitxers necessaris ===== */
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();
require_role(['admin', 'manager']);
$can_manage = can_manage();

// Eliminar un lot (si tenim permisos)
if ($can_manage && isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db()->prepare("DELETE FROM lots WHERE id = ?")->execute([$id]);
    flash_set("Lot eliminat.", "ok");
    header("Location: lots.php");
    exit;
}

// Filtres
$collita_id_filter = isset($_GET['collita_id']) ? (int)$_GET['collita_id'] : null;

// Obtenir lots
$query = "
  SELECT l.*,
         p.name  AS parcela_name,
         s.nom  AS sector_name,
         c.varietat_text AS collita_varietat,
         c.grau_qualitat AS collita_qualitat
  FROM lots l
  LEFT JOIN parcela p ON p.id = l.parcela_id
  LEFT JOIN sectors s ON s.id = l.sector_id
  LEFT JOIN collites c ON c.id = l.collita_id
";

// Aplicar filtres de cerca simples si aplica
$params = [];
if ($collita_id_filter) {
    $query .= " WHERE l.collita_id = ?";
    $params[] = $collita_id_filter;
}
$query .= " ORDER BY l.created_at DESC";

$st = db()->prepare($query);
$st->execute($params);
$lots = $st->fetchAll(PDO::FETCH_ASSOC);

$titol = "Traçabilitat i Lots · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">
  <div class="card span12">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <h2>Gestió de Traçabilitat (Lots)</h2>
        <a href="collites.php" class="btn secondary">🌾 Anar a Collites</a>
    </div>
    
    <p class="small">Llista de lots de collita generats per traçar l'origen, qualitat i tractaments associats.</p>
    
    <?php if ($collita_id_filter): ?>
       <div class="flash ok">Filtrant pel Lot procedent de la collita ID #<?= $collita_id_filter ?>. <a href="lots.php" style="font-weight:bold; text-decoration:underline;">Netejar filtre</a></div>
    <?php endif; ?>

    <?php if (!$lots): ?>
      <p class="small">Encara no hi ha cap lot registrat. Guarda una collita per generar el teu primer lot!</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Codi Lot</th>
            <th>Data Collita</th>
            <th>Parcel·la</th>
            <th>Sector</th>
            <th>Quantitat (kg)</th>
            <th>Qualitat</th>
            <th>Accions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lots as $l): ?>
            <tr>
              <td><span style="font-family: monospace; background:rgba(0,0,0,0.05); padding:3px 6px; border-radius:4px; font-weight:bold;"><?= htmlspecialchars($l['codi_lot']) ?></span></td>
              <td><?= htmlspecialchars($l['data_collita'] ?? '-') ?></td>
              <td><?= htmlspecialchars($l['parcela_name'] ?? '-') ?></td>
              <td><?= htmlspecialchars($l['sector_name'] ?? '-') ?></td>
              <td><?= htmlspecialchars($l['quantitat'] ?? '0') ?></td>
              <td><?= htmlspecialchars($l['qualitat'] ?? $l['collita_qualitat'] ?? '-') ?></td>
              <td style="white-space:nowrap">
                <a href="lot_detall.php?id=<?= $l['id'] ?>" class="btn btn-small" style="background:#16a34a; color:white; border:none;" title="Obrir Traçabilitat">🔍 OBRIR</a>
                <?php if ($can_manage): ?>
                   <a href="lots.php?delete=<?= $l['id'] ?>" class="btn btn-small secondary" onclick="return confirm('Segur? Si elimines el lot perdràs totes les relacions amb tractaments per aquest lot.')">🗑️</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
