<?php
/* ===== Detall i Traçabilitat de Lot ===== */
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();
$can_manage = can_manage();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    flash_set("ID de lot no vàlid.", "err");
    header('Location: lots.php');
    exit;
}

// Associar tractament manualment
if ($can_manage && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'link_tractament') {
    $tractament_id = (int)$_POST['tractament_id'];
    if ($tractament_id > 0) {
        $st = db()->prepare("INSERT IGNORE INTO lot_tractaments (lot_id, tractament_id) VALUES (?, ?)");
        $st->execute([$id, $tractament_id]);
        flash_set("Tractament associat correctament a aquest lot de traçabilitat.", "ok");
    }
    header("Location: lot_detall.php?id=$id");
    exit;
}

// Desassociar tractament manualment
if ($can_manage && isset($_GET['unlink'])) {
    $unlink_id = (int)$_GET['unlink'];
    db()->prepare("DELETE FROM lot_tractaments WHERE lot_id = ? AND tractament_id = ?")->execute([$id, $unlink_id]);
    flash_set("Tractament desvinculat de la traçabilitat del lot.", "ok");
    header("Location: lot_detall.php?id=$id");
    exit;
}

// Obtenir dades del lot i connexions restants
$st = db()->prepare("
    SELECT l.*,
           p.name AS parcela_name,
           s.nom AS sector_name,
           c.varietat_text AS collita_varietat,
           c.grau_qualitat AS collita_qualitat
    FROM lots l
    LEFT JOIN parcela p ON p.id = l.parcela_id
    LEFT JOIN sectors s ON s.id = l.sector_id
    LEFT JOIN collites c ON c.id = l.collita_id
    WHERE l.id = ?
");
$st->execute([$id]);
$lot = $st->fetch(PDO::FETCH_ASSOC);

if (!$lot) {
    flash_set("El lot no existeix.", "err");
    header('Location: lots.php');
    exit;
}

// Obtenir tractaments MATEIX SECTOR INFERITS pre-collita
$st_inferits = db()->prepare("
    SELECT t.*, fp.name AS producte_nom 
    FROM tractaments t
    LEFT JOIN fito_productes fp ON fp.id = t.producte_id
    WHERE t.sector_id = ? AND t.aplicat <= ?
    ORDER BY t.aplicat DESC
    LIMIT 20
");
$st_inferits->execute([$lot['sector_id'], $lot['data_collita']]);
$tractaments_inferits = $st_inferits->fetchAll(PDO::FETCH_ASSOC);

// Obtenir tractaments efectivament ASSOCIATS manuals i per inferència realitzada a lot_tractaments
$st_associats = db()->prepare("
    SELECT t.*, fp.name AS producte_nom 
    FROM lot_tractaments lt
    INNER JOIN tractaments t ON t.id = lt.tractament_id
    LEFT JOIN fito_productes fp ON fp.id = t.producte_id
    WHERE lt.lot_id = ?
    ORDER BY t.aplicat DESC
");
$st_associats->execute([$id]);
$tractaments_associats = $st_associats->fetchAll(PDO::FETCH_ASSOC);

// Extreure llista d'IDs associats per no mostrar-los dobles en els inferits o selectors
$associats_ids = array_column($tractaments_associats, 'id');


$titol = "Detall Lot " . htmlspecialchars($lot['codi_lot']) . " · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';

// URL for QR code (the direct link to this page)
$qr_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>

<div class="grid">
    <div class="span12" style="margin-bottom: 5px;">
        <a href="lots.php" class="btn secondary">⬅ Tots els lots</a>
    </div>

    <!-- Dades base del Lot -->
    <div class="card span5">
        <h2>Traçabilitat: <?= htmlspecialchars($lot['codi_lot']) ?></h2>
        
        <div style="background: rgba(22,163,74,0.06); border-radius: 12px; padding: 15px; margin-bottom: 20px; display:flex; align-items:center; gap: 20px;">
           <div id="qrcode"></div>
           <div>
              <p class="small" style="margin:0; font-weight:bold;">Escaneja el QR per accedir ràpidament a l'historial del mòbil.</p>
           </div>
        </div>

        <ul style="list-style: none; padding: 0; line-height: 2;">
            <li><strong>Data Collita:</strong> <?= htmlspecialchars($lot['data_collita'] ?? '-') ?></li>
            <li><strong>Parcel·la:</strong> <?= htmlspecialchars($lot['parcela_name'] ?? 'Sense definir') ?></li>
            <li><strong>Sector:</strong> <?= htmlspecialchars($lot['sector_name'] ?? 'Sense definir') ?></li>
            <li><strong>Varietat:</strong> <?= htmlspecialchars($lot['collita_varietat'] ?? '-') ?></li>
            <li><strong>Quantitat:</strong> <?= htmlspecialchars($lot['quantitat'] ?? '0') ?> kg</li>
            <li><strong>Qualitat Registrada:</strong> <?= htmlspecialchars($lot['qualitat'] ?? $lot['collita_qualitat'] ?? '-') ?></li>
            <li><strong>Collita d'origen:</strong> <a href="collites.php?edit=<?= $lot['collita_id'] ?>" style="text-decoration:underline;">Registre #<?= $lot['collita_id'] ?></a></li>
        </ul>

        <?php if (!empty($lot['observacions'])): ?>
            <div style="margin-top: 15px; padding: 10px; background: rgba(0,0,0,0.03); border-radius: 8px;">
                <strong>Observacions:</strong><br>
                <?= nl2br(htmlspecialchars($lot['observacions'])) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tractaments Tractats i per Tractar -->
    <div class="card span7">
        <h2>Aplicacions Fitosanitàries (Traçabilitat)</h2>
        <p class="small">Per una traçabilitat absoluta al registre oficial, s'han de vincular expressament sobre el lot d'interès quins tractaments ha rebut en pre-collita la planta origen.</p>
        
        <?php if (empty($tractaments_associats)): ?>
            <div style="padding: 15px; background: rgba(220,38,38,0.08); border-radius: 8px; color:#991b1b; margin-bottom: 20px;">
                Aquest lot actualment no té cap informe fitosanitari directament vinculat a la seva vida.
            </div>
        <?php else: ?>
            <table class="table" style="margin-bottom:25px;">
                <tbody>
                  <?php foreach ($tractaments_associats as $ta): ?>
                    <tr>
                      <td><strong style="color:#16a34a;"><?= htmlspecialchars($ta['producte_nom'] ?? 'Producte') ?></strong><br><span class="small"><?= htmlspecialchars($ta['aplicat']) ?></span></td>
                      <td>Dosi: <?= htmlspecialchars($ta['dosis_total'].' '.$ta['unitat']) ?></td>
                      <td>
                         <?php if ($can_manage): ?>
                           <a href="lot_detall.php?id=<?= $id ?>&unlink=<?= $ta['id'] ?>" class="btn btn-small secondary" onclick="return confirm('Segur que vols desfer l\'associació?')">Desfer</a>
                         <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($can_manage): ?>
            <hr style="border:none; border-top:1px solid #ccc; margin:30px 0 20px 0;">
            <h3 style="font-size:16px;">Vincular tractament a la Traçabilitat</h3>
            
            <?php if (!empty($tractaments_inferits)): ?>
                <p class="small">L'A.I. i històric n'obté aquestos <strong>Tractaments Inferits al sector pre-collita</strong> que pots incloure directament:</p>
                <div style="display:flex; flex-direction:column; gap:10px;">
                <?php foreach ($tractaments_inferits as $ti): ?>
                    <?php if (!in_array($ti['id'], $associats_ids)): ?>
                    <form method="post" style="display:flex; width:100%; justify-content:space-between; align-items:center; background:#f9fafb; border:1px solid #e5e7eb; padding:10px 15px; border-radius:8px;">
                        <input type="hidden" name="action" value="link_tractament">
                        <input type="hidden" name="tractament_id" value="<?= $ti['id'] ?>">
                        <div style="line-height:1.4;">
                           <strong style="display:block;"><?= htmlspecialchars($ti['producte_nom'] ?: 'Sense producte') ?></strong>
                           <span class="small" style="color:Gray;">Data aplicació: <?= htmlspecialchars($ti['aplicat']) ?> • <?= htmlspecialchars($ti['dosis_total'].' '.$ti['unitat']) ?></span>
                        </div>
                        <button type="submit" class="btn btn-small">+ VINCULAR</button>
                    </form>
                    <?php endif; ?>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="small">No s'han detectat històrics directes al sector seleccionat pre-collita.</p>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<script src="assets/js/vendor/qrcode.min.js"></script>
<script>
  // Inicialització asíncrona robusta del QR pel Lot
  window.addEventListener('load', function() {
      new QRCode(document.getElementById("qrcode"), {
          text: "<?= addslashes($qr_url) ?>",
          width: 80,
          height: 80,
          colorDark : "#16a34a",
          colorLight : "#ffffff",
          correctLevel : QRCode.CorrectLevel.L
      });
  });
</script>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
