<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

/**
 * ADAPTAT AL TEU SQL (SENSE MODIFICAR-LO):
 * Taula: collites
 * Camps reals: parcela_id, sector_id, varietat_id, any_campanya, recollit, kg, grau_qualitat, protocol_notes
 *
 * Aquest fitxer elimina qualsevol ús de co.cultiu_id (que NO existeix a collites).
 */

// Crear collita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {

  // Mapatge dels noms del formulari (antics) als camps reals de la taula `collites`
  $parcela_id     = ($_POST['parcela_id'] ?? '') !== '' ? $_POST['parcela_id'] : null;
  $sector_id      = ($_POST['sector_id'] ?? '') !== '' ? $_POST['sector_id'] : null;
  $varietat_id    = ($_POST['varietat_id'] ?? '') !== '' ? $_POST['varietat_id'] : null;

  $any_campanya   = (int)($_POST['any_campanya'] ?? date('Y'));
  $recollit       = $_POST['data_collita'] ?? date('Y-m-d'); // abans: data_collita
  $kg             = (float)($_POST['quantitat_kg'] ?? 0);    // abans: quantitat_kg
  $grau_qualitat  = trim($_POST['qualitat'] ?? '');          // abans: qualitat
  $protocol_notes = trim($_POST['notes'] ?? '');             // abans: notes

  $st = db()->prepare("
    INSERT INTO collites
      (parcela_id, sector_id, varietat_id, any_campanya, recollit, kg, grau_qualitat, protocol_notes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  ");

  $st->execute([
    $parcela_id,
    $sector_id,
    $varietat_id,
    $any_campanya,
    $recollit,
    $kg,
    $grau_qualitat !== '' ? $grau_qualitat : null,
    $protocol_notes !== '' ? $protocol_notes : null
  ]);

  flash_set("Collita registrada correctament.", "ok");
  header("Location: collites.php");
  exit;
}

// Dades per als selects
$parceles  = db()->query("SELECT id, name FROM parcela ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$sectors   = db()->query("SELECT id, nom_sector AS name, parcela_id FROM sector_cultiu ORDER BY nom_sector")->fetchAll(PDO::FETCH_ASSOC);
$cultius   = db()->query("SELECT id, name FROM cultius ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$varietats = db()->query("SELECT id, name, cultiu_id FROM varietats ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Llistar collites (SENSE co.cultiu_id)
$collites = db()->query("
  SELECT co.*,
         p.name  AS parcela_name,
         s.nom_sector  AS sector_name,
         cu.name AS cultiu_name,
         v.name  AS varietat_name
  FROM collites co
  LEFT JOIN parcela p   ON p.id = co.parcela_id
  LEFT JOIN sector_cultiu s   ON s.id = co.sector_id
  LEFT JOIN varietats v ON v.id = co.varietat_id
  LEFT JOIN cultius cu  ON cu.id = v.cultiu_id
  ORDER BY co.recollit DESC, co.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Collites · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <div class="card span6">
    <h2>Nova collita</h2>

    <form method="post">
      <input type="hidden" name="action" value="create">

      <label>Parcel·la</label>
      <select name="parcela_id" id="select_parcela">
        <option value="">—</option>
        <?php foreach ($parceles as $p): ?>
          <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Sector</label>
      <select name="sector_id" id="select_sector">
        <option value="">—</option>
        <?php foreach ($sectors as $s): ?>
          <option value="<?= $s['id'] ?>" data-parcela="<?= $s['parcela_id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <!-- Es manté per UX, però NO es desa a `collites` perquè no hi ha el camp al teu SQL -->
      <label>Cultiu</label>
      <select name="cultiu_id">
        <option value="">—</option>
        <?php foreach ($cultius as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Varietat</label>
      <select name="varietat_id">
        <option value="">—</option>
        <?php foreach ($varietats as $v): ?>
          <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Any campanya</label>
      <input type="number" name="any_campanya" value="<?= date('Y') ?>" required>

      <label>Data de collita</label>
      <input type="date" name="data_collita" required>

      <label>Quantitat (kg)</label>
      <input type="number" step="0.01" name="quantitat_kg" required>

      <label>Qualitat</label>
      <input name="qualitat" placeholder="Ex: Extra, Primera, Segona">

      <!-- No existeix a la taula collites: es manté al formulari però no es desa -->
      <label>Humitat (%)</label>
      <input type="number" step="0.01" name="humitat_pct">

      <label>Notes</label>
      <textarea name="notes"></textarea>

      <button class="btn" type="submit">Desar</button>
    </form>
  </div>

  <div class="card span6">
    <h2>Collites registrades</h2>

    <?php if (!$collites): ?>
      <p class="small">Encara no hi ha collites registrades.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Data</th>
            <th>Cultiu</th>
            <th>Varietat</th>
            <th>Parcel·la</th>
            <th>Sector</th>
            <th>Quantitat (kg)</th>
            <th>Qualitat</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($collites as $c): ?>
            <tr>
              <td><?= htmlspecialchars($c['recollit']) ?></td>
              <td><?= htmlspecialchars($c['cultiu_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($c['varietat_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($c['parcela_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($c['sector_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($c['kg']) ?></td>
              <td><?= htmlspecialchars($c['grau_qualitat'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<script>
document.getElementById('select_parcela').addEventListener('change', function() {
    const parcelaId = this.value;
    const sectorSelect = document.getElementById('select_sector');
    const sectors = sectorSelect.querySelectorAll('option');

    sectors.forEach(opt => {
        if (opt.value === "") {
            opt.style.display = "block";
            return;
        }
        if (parcelaId === "" || opt.getAttribute('data-parcela') === parcelaId) {
            opt.style.display = "block";
        } else {
            opt.style.display = "none";
        }
    });

    sectorSelect.value = ""; 
});
</script>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
