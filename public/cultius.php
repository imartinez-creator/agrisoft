<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

// Eliminar cultiu o varietat
if (isset($_GET['delete_cultiu'])) {
    $id = (int)$_GET['delete_cultiu'];
    db()->prepare("DELETE FROM cultius WHERE id = ?")->execute([$id]);
    flash_set("Cultiu eliminat.", "ok");
    header("Location: cultius.php");
    exit;
}
if (isset($_GET['delete_var'])) {
    $id = (int)$_GET['delete_var'];
    db()->prepare("DELETE FROM varietats WHERE id = ?")->execute([$id]);
    flash_set("Varietat eliminada.", "ok");
    header("Location: cultius.php");
    exit;
}

// Crear o Editar cultiu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'], ['create_cultiu', 'edit_cultiu'])) {
    $name = trim($_POST['name'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($name !== '') {
        if ($_POST['action'] === 'create_cultiu') {
            db()->prepare("INSERT INTO cultius (name) VALUES (?)")->execute([$name]);
            flash_set("Cultiu creat.", "ok");
        } else {
            db()->prepare("UPDATE cultius SET name = ? WHERE id = ?")->execute([$name, $id]);
            flash_set("Cultiu actualitzat.", "ok");
        }
        header("Location: cultius.php");
        exit;
    } else {
        flash_set("El nom és obligatori.", "bad");
    }
}

// Crear o Editar varietat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'], ['create_var', 'edit_var'])) {
    $name = trim($_POST['name'] ?? '');
    $cultiu_id = (int)($_POST['cultiu_id'] ?? 0);
    $info = trim($_POST['informacio_agronomica'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($name !== '' && $cultiu_id > 0) {
        if ($_POST['action'] === 'create_var') {
            db()->prepare("INSERT INTO varietats (cultiu_id, name, informacio_agronomica) VALUES (?, ?, ?)")->execute([$cultiu_id, $name, $info]);
            flash_set("Varietat creada.", "ok");
        } else {
            db()->prepare("UPDATE varietats SET cultiu_id=?, name=?, informacio_agronomica=? WHERE id=?")->execute([$cultiu_id, $name, $info, $id]);
            flash_set("Varietat actualitzada.", "ok");
        }
        header("Location: cultius.php");
        exit;
    } else {
        flash_set("Nom i cultiu són obligatoris.", "bad");
    }
}

// Detectar edició
$edit_cultiu = null;
if (isset($_GET['edit_cultiu'])) {
    $st = db()->prepare("SELECT * FROM cultius WHERE id = ?");
    $st->execute([(int)$_GET['edit_cultiu']]);
    $edit_cultiu = $st->fetch(PDO::FETCH_ASSOC);
}
$edit_var = null;
if (isset($_GET['edit_var'])) {
    $st = db()->prepare("SELECT * FROM varietats WHERE id = ?");
    $st->execute([(int)$_GET['edit_var']]);
    $edit_var = $st->fetch(PDO::FETCH_ASSOC);
}

// Llistar cultius i varietats
$cultius = db()->query("SELECT * FROM cultius ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$varietats = db()->query("
    SELECT v.*, c.name AS cultiu_name 
    FROM varietats v 
    JOIN cultius c ON c.id = v.cultiu_id 
    ORDER BY c.name, v.name
")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Cultius · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>


  <div class="tabs-nav">
    <div class="tab-link <?= (!$edit_var) ? 'active' : '' ?>" onclick="switchTab('tab_cultius')">🌿 Cultius</div>
    <div class="tab-link <?= ($edit_var) ? 'active' : '' ?>" onclick="switchTab('tab_varietats')">🧬 Varietats</div>
  </div>

  <div id="tab_cultius" class="tab-content <?= (!$edit_var) ? 'active' : '' ?>">
    <div class="grid">
      <div class="card span4">
        <h2><?= $edit_cultiu ? "Editar cultiu" : "Nou cultiu" ?></h2>
        <form method="post">
          <input type="hidden" name="action" value="<?= $edit_cultiu ? 'edit_cultiu' : 'create_cultiu' ?>">
          <?php if ($edit_cultiu): ?>
              <input type="hidden" name="id" value="<?= $edit_cultiu['id'] ?>">
          <?php endif; ?>
          <label>Nom del cultiu</label>
          <input name="name" value="<?= $edit_cultiu ? htmlspecialchars($edit_cultiu['name']) : '' ?>" required>
          <button class="btn" type="submit"><?= $edit_cultiu ? "Actualitzar" : "Crear" ?></button>
          <?php if ($edit_cultiu): ?>
              <a href="cultius.php" class="btn secondary">Cancel·lar</a>
          <?php endif; ?>
        </form>
      </div>

      <div class="card span8">
        <h2>Llista de Cultius</h2>
        <?php if (!$cultius): ?>
          <p class="small">No hi ha cultius.</p>
        <?php else: ?>
          <table class="table">
            <thead>
              <tr>
                <th>Nom</th>
                <th style="text-align:right">Accions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cultius as $c): ?>
                <tr style="<?= ($edit_cultiu && $edit_cultiu['id'] == $c['id']) ? 'background: rgba(var(--primary-color-rgb), 0.1);' : '' ?>">
                  <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                  <td style="text-align:right">
                    <a href="cultius.php?edit_cultiu=<?= $c['id'] ?>" class="btn btn-small">✏️</a>
                    <a href="cultius.php?delete_cultiu=<?= $c['id'] ?>" class="btn btn-small" onclick="return confirm('Segur?')">🗑️</a> 
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div id="tab_varietats" class="tab-content <?= ($edit_var) ? 'active' : '' ?>">
    <div class="grid">
      <div class="card span4">
        <h2><?= $edit_var ? "Editar varietat" : "Nova varietat" ?></h2>
        <form method="post">
          <input type="hidden" name="action" value="<?= $edit_var ? 'edit_var' : 'create_var' ?>">
          <?php if ($edit_var): ?>
              <input type="hidden" name="id" value="<?= $edit_var['id'] ?>">
          <?php endif; ?>

          <label>Cultiu</label>
          <select name="cultiu_id" required>
            <option value="">—</option>
            <?php foreach ($cultius as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($edit_var && $edit_var['cultiu_id']==$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>

          <label>Nom varietat</label>
          <input name="name" value="<?= $edit_var ? htmlspecialchars($edit_var['name']) : '' ?>" required>

          <label>Informació agronòmica</label>
          <textarea name="informacio_agronomica" rows="3"><?= $edit_var ? htmlspecialchars($edit_var['informacio_agronomica']) : '' ?></textarea>

          <button class="btn" type="submit"><?= $edit_var ? "Desar" : "Crear" ?></button>
          <?php if ($edit_var): ?>
              <a href="cultius.php" class="btn secondary">Cancel·lar</a>
          <?php endif; ?>
        </form>
      </div>

      <div class="card span8">
        <h2>Llista de Varietats</h2>
        <?php if (!$varietats): ?>
          <p class="small">No hi ha varietats.</p>
        <?php else: ?>
          <table class="table">
            <thead>
              <tr>
                <th>Varietat</th>
                <th>Cultiu</th>
                <th style="text-align:right">Accions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($varietats as $v): ?>
                <tr style="<?= ($edit_var && $edit_var['id'] == $v['id']) ? 'background: rgba(var(--primary-color-rgb), 0.1);' : '' ?>">
                  <td>
                    <strong><?= htmlspecialchars($v['name']) ?></strong>
                    <?php if ($v['informacio_agronomica']): ?>
                        <div class="small text-muted"><?= htmlspecialchars($v['informacio_agronomica']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($v['cultiu_name']) ?></td>
                  <td style="text-align:right">
                    <a href="cultius.php?edit_var=<?= $v['id'] ?>" class="btn btn-small">✏️</a>
                    <a href="cultius.php?delete_var=<?= $v['id'] ?>" class="btn btn-small" onclick="return confirm('Segur?')">🗑️</a> 
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
</script>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>