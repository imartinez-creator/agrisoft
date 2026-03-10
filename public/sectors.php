<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

$can_manage = can_manage();


// Helpers
function post_float(string $key): ?float {
  $v = trim((string)($_POST[$key] ?? ''));
  if ($v === '') return null;
  // Permet 1,23
  $v = str_replace(',', '.', $v);
  return is_numeric($v) ? (float)$v : null;
}

function post_int(string $key): ?int {
  $v = trim((string)($_POST[$key] ?? ''));
  if ($v === '') return null;
  return is_numeric($v) ? (int)$v : null;
}

$action = $_POST['action'] ?? '';

// CREATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create_sector') {
  if (!$can_manage) {
    http_response_code(403);
    flash_set("No tens permisos per crear sectors.", "bad");
    header('Location: sectors.php');
    exit;
  }
  $parcela_id        = post_int('parcela_id');
  $nom_sector        = trim((string)($_POST['nom_sector'] ?? ''));
  $data_plantacio    = ($_POST['data_plantacio'] ?? '') ?: null;
  $marc_plantacio    = trim((string)($_POST['marc_plantacio'] ?? '')) ?: null;
  $num_arbres        = post_int('num_arbres');
  $origen_material   = trim((string)($_POST['origen_material'] ?? '')) ?: null;
  $superficie        = post_float('superficie');
  $previsio_prod     = post_float('previsio_produccio');
  $sistema_formacio  = trim((string)($_POST['sistema_formacio'] ?? '')) ?: null;
  $cultiu_id         = post_int('cultiu_id');
  $estat_actual      = trim((string)($_POST['estat_actual'] ?? '')) ?: null;
  $inversio_inicial  = post_float('inversio_inicial');

  if (!$parcela_id || $nom_sector === '') {
    flash_set("Cal indicar la parcel·la i el nom del sector.", 'bad');
  } else {
    $st = db()->prepare(
      "INSERT INTO sector_cultiu (
        parcela_id, nom_sector, data_plantacio, marc_plantacio, num_arbres,
        origen_material, superficie, previsio_produccio, sistema_formacio,
        cultiu_id, estat_actual, inversio_inicial
      ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    $st->execute([
      $parcela_id,
      $nom_sector,
      $data_plantacio,
      $marc_plantacio,
      $num_arbres,
      $origen_material,
      $superficie,
      $previsio_prod,
      $sistema_formacio,
      $cultiu_id,
      $estat_actual,
      $inversio_inicial,
    ]);
    flash_set('✅ Sector creat correctament.', 'ok');
    header('Location: sectors.php');
    exit;
  }
}

// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_sector') {
  if (!$can_manage) {
    http_response_code(403);
    flash_set("No tens permisos per editar sectors.", "bad");
    header('Location: sectors.php');
    exit;
  }
  if (!$can_manage) {
    http_response_code(403);
    flash_set("No tens permisos per editar sectors.", "bad");
    header('Location: sectors.php');
    exit;
  }
  $id               = post_int('id');
  $parcela_id       = post_int('parcela_id');
  $nom_sector       = trim((string)($_POST['nom_sector'] ?? ''));
  $data_plantacio   = ($_POST['data_plantacio'] ?? '') ?: null;
  $marc_plantacio   = trim((string)($_POST['marc_plantacio'] ?? '')) ?: null;
  $num_arbres       = post_int('num_arbres');
  $origen_material  = trim((string)($_POST['origen_material'] ?? '')) ?: null;
  $superficie       = post_float('superficie');
  $previsio_prod    = post_float('previsio_produccio');
  $sistema_formacio = trim((string)($_POST['sistema_formacio'] ?? '')) ?: null;
  $cultiu_id        = post_int('cultiu_id');
  $estat_actual     = trim((string)($_POST['estat_actual'] ?? '')) ?: null;
  $inversio_inicial = post_float('inversio_inicial');

  if (!$id || !$parcela_id || $nom_sector === '') {
    flash_set("Falten camps obligatoris (ID, parcel·la o nom).", 'bad');
  } else {
    $st = db()->prepare(
      "UPDATE sector_cultiu SET
        parcela_id=?, nom_sector=?, data_plantacio=?, marc_plantacio=?, num_arbres=?,
        origen_material=?, superficie=?, previsio_produccio=?, sistema_formacio=?,
        cultiu_id=?, estat_actual=?, inversio_inicial=?
       WHERE id=?"
    );
    $st->execute([
      $parcela_id,
      $nom_sector,
      $data_plantacio,
      $marc_plantacio,
      $num_arbres,
      $origen_material,
      $superficie,
      $previsio_prod,
      $sistema_formacio,
      $cultiu_id,
      $estat_actual,
      $inversio_inicial,
      $id,
    ]);
    flash_set('✅ Sector actualitzat.', 'ok');
    header('Location: sectors.php');
    exit;
  }
}

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_sector') {
  if (!$can_manage) {
    http_response_code(403);
    flash_set("No tens permisos per eliminar sectors.", "bad");
    header('Location: sectors.php');
    exit;
  }
  $id = post_int('id');
  if ($id) {
    $st = db()->prepare('DELETE FROM sector_cultiu WHERE id=?');
    $st->execute([$id]);
    flash_set('🗑️ Sector eliminat.', 'ok');
  }
  header('Location: sectors.php');
  exit;
}

// Data for form
$parceles = db()->query("SELECT id, name FROM parcela ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$cultius  = db()->query("SELECT id, name FROM cultius ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editing = null;
if ($edit_id) {
  $st = db()->prepare('SELECT * FROM sector_cultiu WHERE id=?');
  $st->execute([$edit_id]);
  $editing = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

$sectors = db()->query(
  "SELECT sc.*, p.name AS parcela_nom, c.name AS cultiu_nom
   FROM sector_cultiu sc
   LEFT JOIN parcela p ON p.id = sc.parcela_id
   LEFT JOIN cultius c ON c.id = sc.cultiu_id
   ORDER BY sc.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$titol = 'Sectors · AGRISOFT';
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <div class="card span6">
    <h2><?= $editing ? 'Editar sector' : 'Nou sector' ?></h2>
    <p class="small">Crea sectors de cultiu dins de cada parcel·la (marc, plantació, arbres, previsió…).</p>

    <?php if ($can_manage): ?>
    <form method="post">
      <input type="hidden" name="action" value="<?= $editing ? 'update_sector' : 'create_sector' ?>">
      <?php if ($editing): ?>
        <input type="hidden" name="id" value="<?= (int)$editing['id'] ?>">
      <?php endif; ?>

      <label>Parcel·la *</label>
      <select name="parcela_id" required>
        <option value="">— Selecciona —</option>
        <?php foreach ($parceles as $p): ?>
          <?php $sel = ($editing && (int)$editing['parcela_id'] === (int)$p['id']) ? 'selected' : ''; ?>
          <option value="<?= (int)$p['id'] ?>" <?= $sel ?>><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Nom sector *</label>
      <input name="nom_sector" required value="<?= htmlspecialchars($editing['nom_sector'] ?? '') ?>">

      <div class="grid" style="gap:12px">
        <div class="span3">
          <label>Data plantació</label>
          <input type="date" name="data_plantacio" value="<?= htmlspecialchars($editing['data_plantacio'] ?? '') ?>">
        </div>
        <div class="span3">
          <label>Marc de plantació</label>
          <input name="marc_plantacio" value="<?= htmlspecialchars($editing['marc_plantacio'] ?? '') ?>" placeholder="ex: 6x4">
        </div>
      </div>

      <div class="grid" style="gap:12px">
        <div class="span2">
          <label>Nº arbres</label>
          <input name="num_arbres" inputmode="numeric" value="<?= htmlspecialchars($editing['num_arbres'] ?? '') ?>">
        </div>
        <div class="span4">
          <label>Origen material</label>
          <input name="origen_material" value="<?= htmlspecialchars($editing['origen_material'] ?? '') ?>" placeholder="viver, varietat, lot…">
        </div>
      </div>

      <div class="grid" style="gap:12px">
        <div class="span2">
          <label>Superfície (ha)</label>
          <input name="superficie" inputmode="decimal" value="<?= htmlspecialchars($editing['superficie'] ?? '') ?>">
        </div>
        <div class="span2">
          <label>Prev. producció (kg)</label>
          <input name="previsio_produccio" inputmode="decimal" value="<?= htmlspecialchars($editing['previsio_produccio'] ?? '') ?>">
        </div>
        <div class="span2">
          <label>Inversió inicial (€)</label>
          <input name="inversio_inicial" inputmode="decimal" value="<?= htmlspecialchars($editing['inversio_inicial'] ?? '') ?>">
        </div>
      </div>

      <label>Sistema de formació</label>
      <input name="sistema_formacio" value="<?= htmlspecialchars($editing['sistema_formacio'] ?? '') ?>" placeholder="vas, eix central, palmeta…">

      <label>Cultiu</label>
      <select name="cultiu_id">
        <option value="">— (Opcional) —</option>
        <?php foreach ($cultius as $c): ?>
          <?php $sel = ($editing && (int)$editing['cultiu_id'] === (int)$c['id']) ? 'selected' : ''; ?>
          <option value="<?= (int)$c['id'] ?>" <?= $sel ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Estat actual</label>
      <input name="estat_actual" value="<?= htmlspecialchars($editing['estat_actual'] ?? '') ?>" placeholder="en producció, jove, replantació…">

      <div style="display:flex;gap:10px;align-items:center;margin-top:14px">
        <button class="btn" type="submit"><?= $editing ? '💾 Guardar canvis' : 'Crear sector' ?></button>
        <?php if ($editing): ?>
          <a class="btn secondary" href="sectors.php">Cancel·lar</a>
        <?php endif; ?>
      </div>
    </form>
    <?php else: ?>
      <p class="small">Mode lectura: no tens permisos per crear/editar/eliminar sectors.</p>
    <?php endif; ?>
  </div>

  <div class="card span6">
    <h2>Sectors</h2>
    <?php if (!$sectors): ?>
      <p class="small">Encara no hi ha sectors creats.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Sector</th>
            <th>Parcel·la</th>
            <th>Cultiu</th>
            <th>Ha</th>
            <th>Arbres</th>
            <th>Accions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sectors as $s): ?>
            <tr>
              <td><?= (int)$s['id'] ?></td>
              <td><?= htmlspecialchars($s['nom_sector']) ?></td>
              <td><?= htmlspecialchars($s['parcela_nom'] ?? '-') ?></td>
              <td><?= htmlspecialchars($s['cultiu_nom'] ?? '-') ?></td>
              <td><?= htmlspecialchars($s['superficie'] ?? '-') ?></td>
              <td><?= htmlspecialchars($s['num_arbres'] ?? '-') ?></td>
              <td style="white-space:nowrap">
                <?php if ($can_manage): ?>
                  <a class="btn secondary" href="sectors.php?edit=<?= (int)$s['id'] ?>">✏️ Editar</a>
                  <form method="post" style="display:inline" onsubmit="return confirm('Eliminar aquest sector?')">
                    <input type="hidden" name="action" value="delete_sector">
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <button class="btn" type="submit" style="margin-left:6px">🗑️ Eliminar</button>
                  </form>
                <?php else: ?>
                  <span class="small">—</span>
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
