<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

// Eliminar pla o observació
if (isset($_GET['delete_plan'])) {
    $id = (int)$_GET['delete_plan'];
    db()->prepare("DELETE FROM plans_tractament WHERE id = ?")->execute([$id]);
    flash_set("Pla eliminat.", "ok");
    header("Location: plagues.php");
    exit;
}
if (isset($_GET['delete_obs'])) {
    $id = (int)$_GET['delete_obs'];
    db()->prepare("DELETE FROM observacio_plagues WHERE id = ?")->execute([$id]);
    flash_set("Observació eliminada.", "ok");
    header("Location: plagues.php");
    exit;
}

// Crear o Editar pla
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
            flash_set("Pla actualitzat.", "ok");
        }
        header("Location: plagues.php");
        exit;
    }
}

// Crear o Editar observació
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'], ['create_obs', 'edit_obs'])) {
    $nom_plaga  = trim($_POST['nom_plaga'] ?? '');
    $observat   = $_POST['observat'] ?? date('Y-m-d');
    $parcela_id = ($_POST['parcela_id'] ?? '') !== '' ? (int)$_POST['parcela_id'] : null;
    $sector_id  = ($_POST['sector_id'] ?? '') !== '' ? (int)$_POST['sector_id'] : null;
    $gravetat   = $_POST['gravetat'] ?? 'baixa';
    $notes      = trim($_POST['notes'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($nom_plaga === '' || $observat === '') {
        flash_set("Cal indicar la plaga i la data.", "bad");
    } else {
        if ($_POST['action'] === 'create_obs') {
            $st = db()->prepare("
                INSERT INTO observacio_plagues (nom_plaga, observat, parcela_id, sector_id, gravetat, notes, creat)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $st->execute([$nom_plaga, $observat, $parcela_id, $sector_id, $gravetat, $notes, $_SESSION['user']['id']]);
            flash_set("Observació registrada.", "ok");
        } else {
            $st = db()->prepare("
                UPDATE observacio_plagues 
                SET nom_plaga=?, observat=?, parcela_id=?, sector_id=?, gravetat=?, notes=?
                WHERE id=?
            ");
            $st->execute([$nom_plaga, $observat, $parcela_id, $sector_id, $gravetat, $notes, $id]);
            flash_set("Observació actualitzada.", "ok");
        }
        header("Location: plagues.php#obs");
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

// Detectar edició
$edit_plan = null;
if (isset($_GET['edit_plan'])) {
    $st = db()->prepare("SELECT * FROM plans_tractament WHERE id = ?");
    $st->execute([(int)$_GET['edit_plan']]);
    $edit_plan = $st->fetch(PDO::FETCH_ASSOC);
}
$edit_obs = null;
if (isset($_GET['edit_obs'])) {
    $st = db()->prepare("SELECT * FROM observacio_plagues WHERE id = ?");
    $st->execute([(int)$_GET['edit_obs']]);
    $edit_obs = $st->fetch(PDO::FETCH_ASSOC);
}

// Dades per selects
$parceles = db()->query("SELECT id, name FROM parcela ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$sectors  = db()->query("SELECT id, nom_sector AS name, parcela_id FROM sector_cultiu ORDER BY nom_sector")->fetchAll(PDO::FETCH_ASSOC);

// Llista de plans
$plans = db()->query("
  SELECT pt.*, p.name AS parcela_name, s.nom_sector AS sector_name
  FROM plans_tractament pt
  LEFT JOIN parcela p ON p.id = pt.parcela_id
  LEFT JOIN sector_cultiu s ON s.id = pt.sector_id
  ORDER BY pt.planned_on DESC, pt.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Llista d'observacions
$observacions = db()->query("
  SELECT o.*, p.name AS parcela_name, s.nom_sector AS sector_name
  FROM observacio_plagues o
  LEFT JOIN parcela p ON p.id = o.parcela_id
  LEFT JOIN sector_cultiu s ON s.id = o.sector_id
  ORDER BY o.observat DESC, o.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Plagues / Plans · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>


  <div class="tabs-nav">
    <div class="tab-link <?= (!$edit_obs) ? 'active' : '' ?>" onclick="switchTab('plans')">🗓️ Plans de tractament</div>
    <div class="tab-link <?= ($edit_obs) ? 'active' : '' ?>" onclick="switchTab('observacions')">🔍 Observacions de plagues</div>
  </div>

  <div id="plans" class="tab-content <?= (!$edit_obs) ? 'active' : '' ?>">
    <div class="grid">
      <div class="card span4">
        <h2><?= $edit_plan ? "Editar pla" : "Nou pla" ?></h2>
        <form method="post">
          <input type="hidden" name="action" value="<?= $edit_plan ? 'edit_plan' : 'create_plan' ?>">
          <?php if ($edit_plan): ?>
              <input type="hidden" name="id" value="<?= $edit_plan['id'] ?>">
          <?php endif; ?>

          <label>Títol</label>
          <input name="title" value="<?= $edit_plan ? htmlspecialchars($edit_plan['title']) : '' ?>" required>

          <label>Data planificada</label>
          <input type="date" name="planned_on" value="<?= $edit_plan ? $edit_plan['planned_on'] : '' ?>" required>

          <label>Parcel·la</label>
          <select name="parcela_id" class="select-parcela">
            <option value="">—</option>
            <?php foreach ($parceles as $p): ?>
              <option value="<?= (int)$p['id'] ?>" <?= ($edit_plan && $edit_plan['parcela_id'] == $p['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label>Sector</label>
          <select name="sector_id" class="select-sector">
            <option value="">—</option>
            <?php foreach ($sectors as $s): ?>
              <option value="<?= (int)$s['id'] ?>" data-parcela="<?= $s['parcela_id'] ?>" <?= ($edit_plan && $edit_plan['sector_id'] == $s['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label>Notes</label>
          <textarea name="notes" rows="3"><?= $edit_plan ? htmlspecialchars($edit_plan['notes']) : '' ?></textarea>

          <div style="margin-top:15px;">
            <button class="btn" type="submit"><?= $edit_plan ? "Guardar" : "Crear pla" ?></button>
            <?php if ($edit_plan): ?>
                <a href="plagues.php" class="btn secondary">Cancel·lar</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <div class="card span8">
        <h2>Plans pendents</h2>
        <?php if (!$plans): ?>
          <p class="small">No hi ha plans pendents.</p>
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
                <tr style="<?= ($edit_plan && $edit_plan['id'] == $pt['id']) ? 'background: rgba(var(--primary-color-rgb), 0.1);' : '' ?>">
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
                        <option value="cancelat" <?= $pt['status']==='cancel·lat'||$pt['status']==='cancelat'?'selected':'' ?>>Cancel·lat</option>
                      </select>
                    </form>
                  </td>
                  <td style="text-align:right; white-space:nowrap;">
                    <a href="plagues.php?edit_plan=<?= $pt['id'] ?>" class="btn btn-small">✏️</a>
                    <a href="plagues.php?delete_plan=<?= $pt['id'] ?>" class="btn btn-small" onclick="return confirm('Eliminar?')">🗑️</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div id="observacions" class="tab-content <?= ($edit_obs) ? 'active' : '' ?>">
    <div class="grid">
      <div class="card span4">
        <h2><?= $edit_obs ? "Editar observació" : "Nova observació" ?></h2>
        <form method="post">
          <input type="hidden" name="action" value="<?= $edit_obs ? 'edit_obs' : 'create_obs' ?>">
          <?php if ($edit_obs): ?>
              <input type="hidden" name="id" value="<?= $edit_obs['id'] ?>">
          <?php endif; ?>

          <label>Plaga detectada</label>
          <input name="nom_plaga" value="<?= $edit_obs ? htmlspecialchars($edit_obs['nom_plaga']) : '' ?>" required>

          <label>Data detecció</label>
          <input type="date" name="observat" value="<?= $edit_obs ? $edit_obs['observat'] : date('Y-m-d') ?>" required>

          <label>Parcel·la</label>
          <select name="parcela_id" class="select-parcela">
            <option value="">—</option>
            <?php foreach ($parceles as $p): ?>
              <option value="<?= (int)$p['id'] ?>" <?= ($edit_obs && $edit_obs['parcela_id'] == $p['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label>Sector</label>
          <select name="sector_id" class="select-sector">
            <option value="">—</option>
            <?php foreach ($sectors as $s): ?>
              <option value="<?= (int)$s['id'] ?>" data-parcela="<?= $s['parcela_id'] ?>" <?= ($edit_obs && $edit_obs['sector_id'] == $s['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label>Gravetat</label>
          <select name="gravetat">
            <option value="baixa" <?= ($edit_obs && $edit_obs['gravetat']=='baixa') ? 'selected' : '' ?>>Baixa</option>
            <option value="mitjana" <?= ($edit_obs && $edit_obs['gravetat']=='mitjana') ? 'selected' : '' ?>>Mitjana</option>
            <option value="alta" <?= ($edit_obs && $edit_obs['gravetat']=='alta') ? 'selected' : '' ?>>Alta</option>
          </select>

          <label>Notes</label>
          <textarea name="notes" rows="3"><?= $edit_obs ? htmlspecialchars($edit_obs['notes']) : '' ?></textarea>

          <div style="margin-top:15px;">
            <button class="btn" type="submit"><?= $edit_obs ? "Actualitzar" : "Registrar" ?></button>
            <?php if ($edit_obs): ?>
                <a href="plagues.php" class="btn secondary">Cancel·lar</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <div class="card span8">
        <h2>Historial d'observacions</h2>
        <?php if (!$observacions): ?>
          <p class="small">Cap observació recent.</p>
        <?php else: ?>
          <table class="table">
            <thead>
              <tr>
                <th>Data/Plaga</th>
                <th>Ubicació</th>
                <th>Gravetat</th>
                <th style="text-align:right">Accions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($observacions as $o): ?>
                <tr style="<?= ($edit_obs && $edit_obs['id'] == $o['id']) ? 'background: rgba(var(--primary-color-rgb), 0.1);' : '' ?>">
                  <td>
                    <strong><?= date('d/m/Y', strtotime($o['observat'])) ?></strong><br>
                    <?= htmlspecialchars($o['nom_plaga']) ?>
                  </td>
                  <td>
                    <small><?= htmlspecialchars($o['parcela_name'] ?? '—') ?></small>
                    <?php if (!empty($o['sector_name'])): ?>
                      <div class="small">Sec: <?= htmlspecialchars($o['sector_name']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge" style="background:<?= $o['gravetat']==='alta'?'#fee2e2':($o['gravetat']==='mitjana'?'#fef3c7':'#f0fdf4') ?>; color:<?= $o['gravetat']==='alta'?'#991b1b':($o['gravetat']==='mitjana'?'#92400e':'#166534') ?>">
                      <?= ucfirst($o['gravetat']) ?>
                    </span>
                  </td>
                  <td style="text-align:right">
                    <a href="plagues.php?edit_obs=<?= $o['id'] ?>" class="btn btn-small">✏️</a>
                    <a href="plagues.php?delete_obs=<?= $o['id'] ?>" class="btn btn-small" onclick="return confirm('Eliminar?')">🗑️</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
    
    document.getElementById(tabId).classList.add('active');
    event.currentTarget.classList.add('active');
}

document.querySelectorAll('.select-parcela').forEach(sel => {
    sel.addEventListener('change', function() {
        const parcelaId = this.value;
        const sectorSelect = this.form.querySelector('.select-sector');
        if(!sectorSelect) return;
        
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

        if(event && event.type === 'change') {
            sectorSelect.value = "";
        }
    });
    sel.dispatchEvent(new Event('change'));
});
</script>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>