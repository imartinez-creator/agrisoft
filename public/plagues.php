<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

// --- LÒGICA PER ELIMINAR ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db()->prepare("DELETE FROM plans_tractament WHERE id = ?")->execute([$id]);
    flash_set("Pla eliminat correctament.", "ok");
    header("Location: plagues.php");
    exit;
}

// --- LÒGICA PER CREAR O EDITAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'], ['create_plan', 'edit_plan'])) {
    $title = trim($_POST['title'] ?? '');
    $planned_on = $_POST['planned_on'] ?? '';
    $parcela_id = ($_POST['parcela_id'] ?? '') !== '' ? (int)$_POST['parcela_id'] : null;
    $sector_id  = ($_POST['sector_id'] ?? '') !== '' ? (int)$_POST['sector_id'] : null;
    $notes = trim($_POST['notes'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($title === '' || $planned_on === '') {
        flash_set("Títol i data són obligatoris.", "bad");
    } else {
        if ($_POST['action'] === 'create_plan') {
            $st = db()->prepare("
                INSERT INTO plans_tractament (title, planned_on, parcela_id, sector_id, notes, status, creat)
                VALUES (?, ?, ?, ?, ?, 'pendent', ?)
            ");
            $st->execute([$title, $planned_on, $parcela_id, $sector_id, $notes, $_SESSION['user']['id']]);
            flash_set("Pla de tractament creat.", "ok");
        } else {
            $st = db()->prepare("
                UPDATE plans_tractament 
                SET title = ?, planned_on = ?, parcela_id = ?, sector_id = ?, notes = ?
                WHERE id = ?
            ");
            $st->execute([$title, $planned_on, $parcela_id, $sector_id, $notes, $id]);
            flash_set("Pla actualitzat correctament.", "ok");
        }
        header("Location: plagues.php");
        exit;
    }
}

// Canviar estat ràpid (pendent/fet/cancelat)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_status') {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'pendent';
    if ($id > 0) {
        db()->prepare("UPDATE plans_tractament SET status = ? WHERE id = ?")->execute([$status, $id]);
        flash_set("Estat actualitzat.", "ok");
    }
    header("Location: plagues.php");
    exit;
}

// --- DETECTAR SI ESTEM EDITANT ---
$edit_item = null;
if (isset($_GET['edit'])) {
    $st = db()->prepare("SELECT * FROM plans_tractament WHERE id = ?");
    $st->execute([(int)$_GET['edit']]);
    $edit_item = $st->fetch(PDO::FETCH_ASSOC);
}

// Dades per selects
$parceles = db()->query("SELECT id, name FROM parcela ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$sectors  = db()->query("SELECT id, nom_sector AS name, parcela_id FROM sector_cultiu ORDER BY nom_sector")->fetchAll(PDO::FETCH_ASSOC);

// Llista de plans
$plans = db()->query("
  SELECT pt.*, p.name AS parcela_name, s.nom_sector AS sector_name, u.name AS creat_per
  FROM plans_tractament pt
  LEFT JOIN parcela p ON p.id = pt.parcela_id
  LEFT JOIN sector_cultiu s ON s.id = pt.sector_id
  LEFT JOIN usuaris u ON u.id = pt.creat
  ORDER BY pt.planned_on DESC, pt.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Plagues / Plans · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <div class="card span4">
    <h2><?= $edit_item ? "Editar pla" : "Nou pla" ?></h2>

    <form method="post">
      <input type="hidden" name="action" value="<?= $edit_item ? 'edit_plan' : 'create_plan' ?>">
      <?php if ($edit_item): ?>
          <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
      <?php endif; ?>

      <label>Títol</label>
      <input name="title" value="<?= $edit_item ? htmlspecialchars($edit_item['title']) : '' ?>" required>

      <label>Data planificada</label>
      <input type="date" name="planned_on" value="<?= $edit_item ? $edit_item['planned_on'] : '' ?>" required>

      <label>Parcel·la</label>
      <select name="parcela_id" id="select_parcela">
        <option value="">—</option>
        <?php foreach ($parceles as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= ($edit_item && $edit_item['parcela_id'] == $p['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Sector</label>
      <select name="sector_id" id="select_sector">
        <option value="">—</option>
        <?php foreach ($sectors as $s): ?>
          <option value="<?= (int)$s['id'] ?>" data-parcela="<?= $s['parcela_id'] ?>" <?= ($edit_item && $edit_item['sector_id'] == $s['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Notes</label>
      <textarea name="notes" rows="3"><?= $edit_item ? htmlspecialchars($edit_item['notes']) : '' ?></textarea>

      <div style="margin-top:15px;">
        <button class="btn" type="submit"><?= $edit_item ? "Guardar canvis" : "Crear pla" ?></button>
        <?php if ($edit_item): ?>
            <a href="plagues.php" class="btn" style="background:#eee; color:#333;">Cancel·lar</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="card span8">
    <h2>Plans</h2>
    <?php if (!$plans): ?>
      <p class="small">Encara no hi ha plans creats.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Data/Títol</th>
            <th>Ubicació</th>
            <th>Estat</th>
            <th style="text-align:right">Accions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($plans as $pt): ?>
            <tr style="<?= ($edit_item && $edit_item['id'] == $pt['id']) ? 'background: #f0f7ff;' : '' ?>">
              <td>
                <strong><?= date('d/m/Y', strtotime($pt['planned_on'])) ?></strong><br>
                <?= htmlspecialchars($pt['title']) ?>
              </td>
              <td>
                <small><?= htmlspecialchars($pt['parcela_name'] ?? '—') ?></small>
                <?php if (!empty($pt['sector_name'])): ?>
                  <div class="small text-muted">Sec: <?= htmlspecialchars($pt['sector_name']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <form method="post" style="display:flex;gap:4px;">
                  <input type="hidden" name="action" value="set_status">
                  <input type="hidden" name="id" value="<?= (int)$pt['id'] ?>">
                  <select name="status" onchange="this.form.submit()" style="padding:2px; font-size:11px;">
                    <option value="pendent" <?= $pt['status']==='pendent'?'selected':'' ?>>Pendent</option>
                    <option value="fet" <?= $pt['status']==='fet'?'selected':'' ?>>Fet</option>
                    <option value="cancelat" <?= $pt['status']==='cancelat'?'selected':'' ?>>Cancel·lat</option>
                  </select>
                </form>
              </td>
              <td style="text-align:right; white-space:nowrap;">
                <a href="plagues.php?edit=<?= $pt['id'] ?>" class="btn btn-small">
                  <span class="icon">✏️</span>
                </a>
                <a href="plagues.php?delete=<?= $pt['id'] ?>" 
                   class="btn btn-small btn-red" 
                   onclick="return confirm('Eliminar aquest pla?')">
                  <span class="icon">🗑️</span>
                </a>
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

    // Només reiniciem el sector si no estem en mode d'edició carregant la pàgina
    if(!document.querySelector('input[name="action"][value="edit_plan"]')) {
      sectorSelect.value = ""; 
    }
});

// Llançcar l'esdeveniment change en carregar per si ja hi ha una parcela seleccionada (ex: edició)
document.getElementById('select_parcela').dispatchEvent(new Event('change'));
</script>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>