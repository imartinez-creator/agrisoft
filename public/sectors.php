<?php
/* ===== Gestió de Sectors i Unitats de Cultiu ===== */
// Permet subdividir les parcel·les en sectors més petits per a una gestió més precisa

require_once __DIR__ . '/../app/config/db.php';       // Connexió a la base de dades
require_once __DIR__ . '/../app/middleware/auth.php';  // Control d'accés
require_once __DIR__ . '/../app/helpers/flash.php';    // Missatges flash
require_once __DIR__ . '/../app/helpers/forms.php';    // Helpers per a formularis (post_float, post_int...)

// Comprova que l'usuari hagi iniciat sessió
require_login();

$can_manage = can_manage();

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  
  // ============================================
  // CREATE SECTOR
  // ============================================
  if ($action === 'create_sector') {
    if (!$can_manage) {
      http_response_code(403);
      flash_set("No tens permisos per crear sectors.", "bad");
      header('Location: sectors.php');
      exit;
    }
    
    $parcela_id        = post_int('parcela_id');
    $name              = trim((string)($_POST['name'] ?? ''));
    
    if (!$parcela_id || $name === '') {
      flash_set("Falten camps obligatoris (parcel·la o nom).", 'bad');
      header('Location: sectors.php');
      exit;
    }
    
    $params = [
      $parcela_id,
      $name,
      ($_POST['data_plantacio'] ?? '') ?: null,
      trim((string)($_POST['marc_plantacio'] ?? '')) ?: null,
      post_int('num_arbres'),
      trim((string)($_POST['origen_material'] ?? '')) ?: null,
      post_float('superficie'),
      post_float('previsio_produccio'),
      trim((string)($_POST['sistema_formacio'] ?? '')) ?: null,
      post_int('cultiu_id'),
      trim((string)($_POST['varietat'] ?? '')) ?: null,
      trim((string)($_POST['estat_actual'] ?? '')) ?: null,
      post_float('inversio_inicial'),
      trim((string)($_POST['observacions'] ?? '')) ?: null
    ];

    $st = db()->prepare("
      INSERT INTO sectors (
        parcela_id, nom, data_plantacio, marc_plantacio, num_arbres,
        origen_material, superficie, previsio_produccio, sistema_formacio,
        cultiu_id, varietat, estat_actual, inversio_inicial, observacions
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $st->execute($params);
    
    flash_set('✅ Sector creat correctament.', 'ok');
    header('Location: sectors.php');
    exit;
  }

  // ============================================
  // UPDATE SECTOR
  // ============================================
  if ($action === 'update_sector') {
    if (!$can_manage) {
      http_response_code(403);
      flash_set("No tens permisos per editar sectors.", "bad");
      header('Location: sectors.php');
      exit;
    }

    $id         = post_int('id');
    $parcela_id = post_int('parcela_id');
    $name       = trim((string)($_POST['name'] ?? ''));

    if (!$id || !$parcela_id || $name === '') {
      flash_set("Falten camps obligatoris (ID, parcel·la o nom).", 'bad');
      header('Location: sectors.php');
      exit;
    }
    
    $params = [
      $parcela_id,
      $name,
      ($_POST['data_plantacio'] ?? '') ?: null,
      trim((string)($_POST['marc_plantacio'] ?? '')) ?: null,
      post_int('num_arbres'),
      trim((string)($_POST['origen_material'] ?? '')) ?: null,
      post_float('superficie'),
      post_float('previsio_produccio'),
      trim((string)($_POST['sistema_formacio'] ?? '')) ?: null,
      post_int('cultiu_id'),
      trim((string)($_POST['varietat'] ?? '')) ?: null,
      trim((string)($_POST['estat_actual'] ?? '')) ?: null,
      post_float('inversio_inicial'),
      trim((string)($_POST['observacions'] ?? '')) ?: null,
      $id
    ];

    $st = db()->prepare("
      UPDATE sectors SET
        parcela_id = ?,
        nom = ?,
        data_plantacio = ?,
        marc_plantacio = ?,
        num_arbres = ?,
        origen_material = ?,
        superficie = ?,
        previsio_produccio = ?,
        sistema_formacio = ?,
        cultiu_id = ?,
        varietat = ?,
        estat_actual = ?,
        inversio_inicial = ?,
        observacions = ?
      WHERE id = ?
    ");
    $st->execute($params);

    flash_set('✅ Sector actualitzat.', 'ok');
    header('Location: sectors.php');
    exit;
  }

  // ============================================
  // DELETE SECTOR
  // ============================================
  if ($action === 'delete_sector') {
    if (!$can_manage) {
      http_response_code(403);
      flash_set("No tens permisos per eliminar sectors.", "bad");
      header('Location: sectors.php');
      exit;
    }
    $id = post_int('id');
    if ($id) {
      $st = db()->prepare('DELETE FROM sectors WHERE id=?');
      $st->execute([$id]);
      flash_set('🗑️ Sector eliminat.', 'ok');
    }
    header('Location: sectors.php');
    exit;
  }
}

// Data for form
$parceles = db()->query("SELECT id, name FROM parcela ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$cultius  = db()->query("SELECT id, name FROM cultius ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editing = null;
if ($edit_id) {
  $st = db()->prepare('SELECT * FROM sectors WHERE id=?');
  $st->execute([$edit_id]);
  $editing = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

$sectors = db()->query(
  "SELECT sc.*, p.name AS parcela_nom, c.name AS cultiu_nom
   FROM sectors sc
   LEFT JOIN parcela p ON p.id = sc.parcela_id
   LEFT JOIN cultius c ON c.id = sc.cultiu_id
   ORDER BY sc.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$titol = 'Sectors · AGRISOFT';
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <?php if ($can_manage): ?>
  <div class="card span6">
    <h2><?= $editing ? 'Editar sector' : 'Nou sector' ?></h2>
    <p class="small">Crea sectors de cultiu dins de cada parcel·la (marc, plantació, arbres, previsió…).</p>

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
      <input name="name" required value="<?= htmlspecialchars($editing['nom'] ?? '') ?>">

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

      <label>Varietat</label>
      <input name="varietat" value="<?= htmlspecialchars($editing['varietat'] ?? '') ?>" placeholder="ex: Arbequina, Merlot...">

      <label>Estat actual</label>
      <input name="estat_actual" value="<?= htmlspecialchars($editing['estat_actual'] ?? '') ?>" placeholder="en producció, jove, replantació…">

      <label>Observacions</label>
      <textarea name="observacions" rows="2" placeholder="Anotacions sobre el sector..."><?= htmlspecialchars($editing['observacions'] ?? '') ?></textarea>

      <div style="display:flex;gap:10px;align-items:center;margin-top:14px">
        <button class="btn" type="submit"><?= $editing ? '💾 Guardar canvis' : 'Crear sector' ?></button>
        <?php if ($editing): ?>
          <a class="btn secondary" href="sectors.php">Cancel·lar</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <div class="card <?= $can_manage ? 'span6' : 'span12' ?>">
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
              <td><?= htmlspecialchars($s['nom']) ?></td>
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