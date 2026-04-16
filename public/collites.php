<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

// Eliminar collita
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db()->prepare("DELETE FROM collites WHERE id = ?")->execute([$id]);
    flash_set("Collita eliminada.", "ok");
    header("Location: collites.php");
    exit;
}

// Crear o Editar collita
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $id = (int)($_POST['id'] ?? 0);

  $parcela_id     = ($_POST['parcela_id'] ?? '') !== '' ? (int)$_POST['parcela_id'] : null;
  $sector_id      = ($_POST['sector_id'] ?? '') !== '' ? (int)$_POST['sector_id'] : null;
  $varietat_text  = trim($_POST['varietat_text'] ?? '');
  $any_campanya   = (int)($_POST['any_campanya'] ?? date('Y'));
  $recollit       = $_POST['data_collita'] ?? date('Y-m-d'); 
  $kg             = (float)($_POST['quantitat_kg'] ?? 0);    
  $grau_qualitat  = trim($_POST['qualitat'] ?? '');          
  $protocol_notes = trim($_POST['notes'] ?? '');             
  // Nota: humitat_pct s'afegirà a la BD si no hi és, però de moment ho capturem
  $humitat        = ($_POST['humitat_pct'] ?? '') !== '' ? (float)$_POST['humitat_pct'] : null;

  if ($action === 'create') {
    $st = db()->prepare("
      INSERT INTO collites
        (parcela_id, sector_id, varietat_text, any_campanya, recollit, kg, grau_qualitat, protocol_notes)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $st->execute([
      $parcela_id, $sector_id, 
      $varietat_text !== '' ? $varietat_text : null,
      $any_campanya, $recollit, $kg,
      $grau_qualitat !== '' ? $grau_qualitat : null,
      $protocol_notes !== '' ? $protocol_notes : null
    ]);
    flash_set("Collita registrada correctament.", "ok");
  } elseif ($action === 'edit' && $id > 0) {
    $st = db()->prepare("
      UPDATE collites SET
        parcela_id=?, sector_id=?, varietat_text=?, any_campanya=?, 
        recollit=?, kg=?, grau_qualitat=?, protocol_notes=?
      WHERE id=?
    ");
    $st->execute([
      $parcela_id, $sector_id, 
      $varietat_text !== '' ? $varietat_text : null,
      $any_campanya, $recollit, $kg,
      $grau_qualitat !== '' ? $grau_qualitat : null,
      $protocol_notes !== '' ? $protocol_notes : null,
      $id
    ]);
    flash_set("Collita actualitzada.", "ok");
  }

  header("Location: collites.php");
  exit;
}

// Detectar edició
$edit_item = null;
if (isset($_GET['edit'])) {
    $st = db()->prepare("SELECT * FROM collites WHERE id = ?");
    $st->execute([(int)$_GET['edit']]);
    $edit_item = $st->fetch(PDO::FETCH_ASSOC);
}

// Dades per als selects
$parceles  = db()->query("SELECT id, name FROM parcela ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$sectors   = db()->query("SELECT id, nom_sector AS name, parcela_id FROM sector_cultiu ORDER BY nom_sector")->fetchAll(PDO::FETCH_ASSOC);
$cultius   = db()->query("SELECT id, name FROM cultius ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Llistar collites (agafant tant el text nou com els IDs vells per retrocompatibilitat)
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
    <h2><?= $edit_item ? 'Editar collita' : 'Nova collita' ?></h2>

    <form method="post">
      <input type="hidden" name="action" value="<?= $edit_item ? 'edit' : 'create' ?>">
      <?php if ($edit_item): ?>
        <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
      <?php endif; ?>

      <label>Parcel·la</label>
      <select name="parcela_id" id="select_parcela">
        <option value="">—</option>
        <?php foreach ($parceles as $p): ?>
          <option value="<?= $p['id'] ?>" <?= ($edit_item && $edit_item['parcela_id'] == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Sector</label>
      <select name="sector_id" id="select_sector">
        <option value="">—</option>
        <?php foreach ($sectors as $s): ?>
          <option value="<?= $s['id'] ?>" data-parcela="<?= $s['parcela_id'] ?>" <?= ($edit_item && $edit_item['sector_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Cultiu</label>
      <select name="cultiu_id">
        <option value="">—</option>
        <?php foreach ($cultius as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Varietat</label>
      <input type="text" name="varietat_text" placeholder="Escriu la varietat (ex: Arbequina)..." value="<?= $edit_item ? htmlspecialchars($edit_item['varietat_text'] ?? '') : '' ?>">

      <label>Any campanya</label>
      <input type="number" name="any_campanya" value="<?= $edit_item ? $edit_item['any_campanya'] : date('Y') ?>" required>

      <label>Data de collita</label>
      <input type="date" name="data_collita" required value="<?= $edit_item ? $edit_item['recollit'] : '' ?>">

      <label>Quantitat (kg)</label>
      <input type="number" step="0.01" name="quantitat_kg" required value="<?= $edit_item ? $edit_item['kg'] : '' ?>">

      <label>Qualitat</label>
      <input name="qualitat" placeholder="Ex: Extra, Primera, Segona" value="<?= $edit_item ? htmlspecialchars($edit_item['grau_qualitat'] ?? '') : '' ?>">

      <label>Humitat (%)</label>
      <input type="number" step="0.01" name="humitat_pct" value="<?= $edit_item ? ($edit_item['humitat_pct'] ?? '') : '' ?>">

      <label>Notes</label>
      <textarea name="notes"><?= $edit_item ? htmlspecialchars($edit_item['protocol_notes'] ?? '') : '' ?></textarea>

      <button class="btn" type="submit"><?= $edit_item ? 'Actualitzar' : 'Desar' ?></button>
      <?php if ($edit_item): ?>
        <a href="collites.php" class="btn secondary" style="margin-left:8px">Cancel·lar</a>
      <?php endif; ?>
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
            <th>Accions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($collites as $c): ?>
            <tr>
              <td><?= htmlspecialchars($c['recollit']) ?></td>
              <td><?= htmlspecialchars($c['cultiu_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($c['varietat_text'] ?? $c['varietat_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($c['parcela_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($c['sector_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($c['kg']) ?></td>
              <td><?= htmlspecialchars($c['grau_qualitat'] ?? '') ?></td>
              <td style="white-space:nowrap">
                <a href="collites.php?edit=<?= $c['id'] ?>" class="btn btn-small">✏️</a>
                <a href="collites.php?delete=<?= $c['id'] ?>" class="btn btn-small" onclick="return confirm('Segur?')">🗑️</a>
              </td>
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