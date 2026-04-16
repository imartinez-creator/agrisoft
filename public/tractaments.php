<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

// --- LÒGICA PER ELIMINAR ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db()->prepare("DELETE FROM tractaments WHERE id = ?")->execute([$id]);
    flash_set("Tractament eliminat.", "ok");
    header("Location: tractaments.php");
    exit;
}

// --- LÒGICA PER CREAR O EDITAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    // Recollim i netegem les dades del formulari
    $parcela_id = !empty($_POST['parcela_id']) ? $_POST['parcela_id'] : null;
    $sector_id = !empty($_POST['sector_id']) ? $_POST['sector_id'] : null;
    $fila_id = !empty($_POST['fila_id']) ? $_POST['fila_id'] : null;
    $producte_id = $_POST['producte_id'];
    $aplicat = $_POST['aplicat'];
    $dosis_ha = (float)$_POST['dosis_hectarea'];
    $dosis_tot = (float)$_POST['dosis_total'];
    $temps = trim($_POST['temps'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($action === 'create_tractament') {
        $st = db()->prepare("
            INSERT INTO tractaments
              (parcela_id, sector_id, fila_id, producte_id, aplicat, dosis_hectarea, dosis_total, temps, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $st->execute([
            $parcela_id, $sector_id, $fila_id, $producte_id, $aplicat, 
            $dosis_ha, $dosis_tot, $temps, $notes, $_SESSION['user']['id']
        ]);

        flash_set("Tractament registrat correctament.", "ok");
        header("Location: tractaments.php");
        exit;

    } elseif ($action === 'edit_tractament') {
        $st = db()->prepare("
            UPDATE tractaments 
            SET parcela_id = ?, sector_id = ?, fila_id = ?, producte_id = ?, 
                aplicat = ?, dosis_hectarea = ?, dosis_total = ?, temps = ?, notes = ?
            WHERE id = ?
        ");
        $st->execute([
            $parcela_id, $sector_id, $fila_id, $producte_id, $aplicat, 
            $dosis_ha, $dosis_tot, $temps, $notes, $id
        ]);

        flash_set("Tractament actualitzat.", "ok");
        header("Location: tractaments.php");
        exit;
    }
}

// --- DETECTAR SI ESTEM EDITANT ---
$edit_item = null;
if (isset($_GET['id'])) {
    $st = db()->prepare("SELECT * FROM tractaments WHERE id = ?");
    $st->execute([(int)$_GET['id']]);
    $edit_item = $st->fetch(PDO::FETCH_ASSOC);
}

// Dades per als selects 
$parceles = db()->query("SELECT id, name FROM parcela ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$sectors  = db()->query("SELECT id, nom_sector AS name, parcela_id FROM sector_cultiu ORDER BY nom_sector")->fetchAll(PDO::FETCH_ASSOC);
$files    = db()->query("SELECT id, codi_fila, sector_id FROM files_arbres ORDER BY codi_fila")->fetchAll(PDO::FETCH_ASSOC);
$productes = db()->query("SELECT id, name FROM fito_productes ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Llistar tractaments 
$tractaments = db()->query("
  SELECT t.*, 
         p.name AS parcela_name,
         s.nom_sector AS sector_name,
         f.codi_fila AS fila_name,
         fp.name AS producte_name
  FROM tractaments t
  LEFT JOIN parcela p ON p.id = t.parcela_id
  LEFT JOIN sector_cultiu s ON s.id = t.sector_id
  LEFT JOIN files_arbres f ON f.id = t.fila_id
  LEFT JOIN fito_productes fp ON fp.id = t.producte_id
  ORDER BY t.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Tractaments · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <div class="card span6">
    <h2><?= $edit_item ? "Editar tractament" : "Nou tractament" ?></h2>

    <form method="post">
      <input type="hidden" name="action" value="<?= $edit_item ? 'edit_tractament' : 'create_tractament' ?>">
      <?php if ($edit_item): ?>
          <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
      <?php endif; ?>

      <label>Parcel·la</label>
      <select name="parcela_id" id="select_parcela">
        <option value="">— Selecciona la parcela —</option>
        <?php foreach ($parceles as $p): ?>
          <option value="<?= $p['id'] ?>" <?= (($edit_item && $edit_item['parcela_id'] == $p['id']) || ($_GET['parcela_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Sector</label>
      <select name="sector_id" id="select_sector">
        <option value="">— Selecciona sector —</option>
        <?php foreach ($sectors as $s): ?>
          <option value="<?= $s['id'] ?>" data-parcela="<?= $s['parcela_id'] ?>" <?= (($edit_item && $edit_item['sector_id'] == $s['id']) || ($_GET['sector_id'] ?? '') == $s['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Fila (Opcional)</label>
      <select name="fila_id" id="select_fila">
        <option value="">— Totes / Cap —</option>
        <?php foreach ($files as $f): ?>
          <option value="<?= $f['id'] ?>" data-sector="<?= $f['sector_id'] ?>" <?= ($edit_item && $edit_item['fila_id'] == $f['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($f['codi_fila']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Producte</label>
      <select name="producte_id" required>
        <option value="">— Selecciona un producte —</option>
        <?php foreach ($productes as $p): ?>
          <option value="<?= $p['id'] ?>" <?= (($edit_item && $edit_item['producte_id'] == $p['id']) || ($_GET['producte_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Data aplicació</label>
      <input type="date" name="aplicat" required value="<?= $edit_item ? htmlspecialchars($edit_item['aplicat']) : date('Y-m-d') ?>">

      <div class="grid" style="gap:12px">
        <div class="span3">
          <label>Dosi / ha</label>
          <input type="number" step="0.01" name="dosis_hectarea" required value="<?= $edit_item ? htmlspecialchars($edit_item['dosis_hectarea']) : ($_GET['dosi_ha'] ?? '') ?>">
        </div>
        <div class="span3">
          <label>Dosi total</label>
          <input type="number" step="0.01" name="dosis_total" required value="<?= $edit_item ? htmlspecialchars($edit_item['dosis_total']) : ($_GET['dosi_tot'] ?? '') ?>">
        </div>
      </div>

      <label>Temps</label>
      <input name="temps" placeholder="Ex: 2h 30min" value="<?= $edit_item ? htmlspecialchars($edit_item['temps']) : '' ?>">

      <label>Notes</label>
      <textarea name="notes" rows="3"><?= $edit_item ? htmlspecialchars($edit_item['notes']) : '' ?></textarea>

      <button class="btn" type="submit"><?= $edit_item ? "Actualitzar tractament" : "Desar tractament" ?></button>
      
      <?php if ($edit_item): ?>
          <a href="tractaments.php" class="btn" style="background:#eee; color:#333; margin-left:10px;">Cancel·lar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="card span6">
    <h2>Tractaments Recents</h2>

    <?php if (!$tractaments): ?>
      <p class="small">Encara no hi ha tractaments registrats.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Data</th>
            <th>Producte</th>
            <th>Ubicació</th>
            <th>Dosi Tot.</th>
            <th style="text-align:right">Accions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tractaments as $t): ?>
            <tr style="<?= ($edit_item && $edit_item['id'] == $t['id']) ? 'background: #f0f7ff;' : '' ?>">
              <td style="white-space:nowrap"><?= htmlspecialchars($t['aplicat']) ?></td>
              <td><strong><?= htmlspecialchars($t['producte_name']) ?></strong></td>
              <td class="small">
                <?= htmlspecialchars($t['parcela_name'] ?? '-') ?><br>
                <span class="muted"><?= htmlspecialchars($t['sector_name'] ?? '-') ?></span>
              </td>
              <td><?= htmlspecialchars($t['dosis_total']) ?></td>
              <td style="text-align:right">
                <a href="tractaments.php?id=<?= $t['id'] ?>" class="btn btn-small">
                  <span class="icon">✏️</span> Editar
                </a>
                <a href="tractaments.php?delete=<?= $t['id'] ?>" 
                   class="btn btn-small btn-red" 
                   onclick="return confirm('Segur que vols eliminar aquest tractament?')">
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
// Filtrar sectors segons la parcel·la seleccionada
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
    if(!document.querySelector('input[name="action"][value="edit_tractament"]')) {
      sectorSelect.value = ""; 
    }
});
</script>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
