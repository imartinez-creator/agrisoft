<?php
/* ===== Gestió de Cultius i Varietats ===== */
// Permet registrar i organitzar els diversos cultius i les seves varietats específiques

require_once __DIR__ . '/../app/config/db.php';       // Connexió a la base de dades
require_once __DIR__ . '/../app/middleware/auth.php';  // Control d'accés (autenticació)
require_once __DIR__ . '/../app/helpers/flash.php';    // Missatges flash (avisos a l'usuari)

// Comprova que l'usuari hagi iniciat sessió, sinó el redirigeix al login
require_login();


/* ===== Eliminar un cultiu ===== */
// Si rebem el paràmetre 'delete_cultiu' per GET, esborrem el cultiu de la BD
if (isset($_GET['delete_cultiu'])) {
    $id = (int)$_GET['delete_cultiu']; // Agafem l'ID del cultiu a eliminar
    db()->prepare("DELETE FROM cultius WHERE id = ?")->execute([$id]);
    flash_set("Cultiu eliminat.", "ok"); // Missatge de confirmació
    header("Location: cultius.php");     // Tornem a la mateixa pàgina
    exit;
}

/* ===== Eliminar una varietat ===== */
// Si rebem el paràmetre 'delete_var' per GET, esborrem la varietat de la BD
if (isset($_GET['delete_var'])) {
    $id = (int)$_GET['delete_var']; // Agafem l'ID de la varietat a eliminar
    db()->prepare("DELETE FROM varietats WHERE id = ?")->execute([$id]);
    flash_set("Varietat eliminada.", "ok"); // Missatge de confirmació
    header("Location: cultius.php");        // Tornem a la mateixa pàgina
    exit;
}


/* ===== Crear o Editar un cultiu (formulari POST) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'], ['create_cultiu', 'edit_cultiu'])) {
    $name = trim($_POST['name'] ?? '');   // Nom del cultiu (sense espais extra)
    $id = (int)($_POST['id'] ?? 0);       // ID del cultiu (0 si és nou)

    if ($name !== '') {
        if ($_POST['action'] === 'create_cultiu') {
            // Inserim un nou cultiu a la base de dades
            db()->prepare("INSERT INTO cultius (name) VALUES (?)")->execute([$name]);
            flash_set("Cultiu creat.", "ok");
        } else {
            // Actualitzem el nom del cultiu existent
            db()->prepare("UPDATE cultius SET name = ? WHERE id = ?")->execute([$name, $id]);
            flash_set("Cultiu actualitzat.", "ok");
        }
        header("Location: cultius.php");
        exit;
    } else {
        // Si el nom està buit, mostrem error
        flash_set("El nom és obligatori.", "bad");
    }
}


/* ===== Crear o Editar una varietat (formulari POST) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'], ['create_var', 'edit_var'])) {
    $name = trim($_POST['name'] ?? '');                    // Nom de la varietat
    $cultiu_id = (int)($_POST['cultiu_id'] ?? 0);          // A quin cultiu pertany
    $info = trim($_POST['informacio_agronomica'] ?? '');    // Dades agronòmiques
    $id = (int)($_POST['id'] ?? 0);                        // ID de la varietat (0 si és nova)

    if ($name !== '' && $cultiu_id > 0) {
        if ($_POST['action'] === 'create_var') {
            // Inserim una nova varietat a la base de dades
            db()->prepare("INSERT INTO varietats (cultiu_id, name, informacio_agronomica) VALUES (?, ?, ?)")->execute([$cultiu_id, $name, $info]);
            flash_set("Varietat creada.", "ok");
        } else {
            // Actualitzem la varietat existent
            db()->prepare("UPDATE varietats SET cultiu_id=?, name=?, informacio_agronomica=? WHERE id=?")->execute([$cultiu_id, $name, $info, $id]);
            flash_set("Varietat actualitzada.", "ok");
        }
        header("Location: cultius.php");
        exit;
    } else {
        // Si falta el nom o el cultiu, mostrem error
        flash_set("Nom i cultiu són obligatoris.", "bad");
    }
}


/* ===== Carregar dades per editar un cultiu ===== */
// Si rebem 'edit_cultiu' per GET, busquem les seves dades per omplir el formulari
$edit_cultiu = null;
if (isset($_GET['edit_cultiu'])) {
    $st = db()->prepare("SELECT * FROM cultius WHERE id = ?");
    $st->execute([(int)$_GET['edit_cultiu']]);
    $edit_cultiu = $st->fetch(PDO::FETCH_ASSOC);
}

/* ===== Carregar dades per editar una varietat ===== */
// Si rebem 'edit_var' per GET, busquem les seves dades per omplir el formulari
$edit_var = null;
if (isset($_GET['edit_var'])) {
    $st = db()->prepare("SELECT * FROM varietats WHERE id = ?");
    $st->execute([(int)$_GET['edit_var']]);
    $edit_var = $st->fetch(PDO::FETCH_ASSOC);
}


/* ===== Obtenir llistes completes per mostrar a la pàgina ===== */
// Tots els cultius ordenats per nom
$cultius = db()->query("SELECT * FROM cultius ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Totes les varietats amb el nom del cultiu al qual pertanyen
$varietats = db()->query("
    SELECT v.*, c.name AS cultiu_name 
    FROM varietats v 
    JOIN cultius c ON c.id = v.cultiu_id 
    ORDER BY c.name, v.name
")->fetchAll(PDO::FETCH_ASSOC);

/* ===== Títol de la pàgina i capçalera HTML ===== */
$titol = "Cultius · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>


  <!-- Pestanyes per canviar entre Cultius i Varietats -->
  <div class="tabs-nav">
    <div class="tab-link <?= (!$edit_var) ? 'active' : '' ?>" onclick="switchTab('tab_cultius')">🌿 Cultius</div>
    <div class="tab-link <?= ($edit_var) ? 'active' : '' ?>" onclick="switchTab('tab_varietats')">🧬 Varietats</div>
  </div>

  <!-- ===== PESTANYA CULTIUS ===== -->
  <div id="tab_cultius" class="tab-content <?= (!$edit_var) ? 'active' : '' ?>">
    <div class="grid">

      <!-- Formulari per crear o editar un cultiu -->
      <div class="card span4">
        <h2><?= $edit_cultiu ? "Editar cultiu" : "Nou cultiu" ?></h2>
        <form method="post">
          <!-- Camp ocult per indicar si creem o editem -->
          <input type="hidden" name="action" value="<?= $edit_cultiu ? 'edit_cultiu' : 'create_cultiu' ?>">
          <?php if ($edit_cultiu): ?>
              <!-- Si editem, enviem l'ID del cultiu -->
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

      <!-- Taula amb la llista de tots els cultius -->
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
                <!-- Ressaltem la fila si s'està editant aquest cultiu -->
                <tr style="<?= ($edit_cultiu && $edit_cultiu['id'] == $c['id']) ? 'background: rgba(var(--primary-color-rgb), 0.1);' : '' ?>">
                  <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                  <td style="text-align:right">
                    <!-- Botó per editar -->
                    <a href="cultius.php?edit_cultiu=<?= $c['id'] ?>" class="btn btn-small">✏️</a>
                    <!-- Botó per eliminar (amb confirmació) -->
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

  <!-- ===== PESTANYA VARIETATS ===== -->
  <div id="tab_varietats" class="tab-content <?= ($edit_var) ? 'active' : '' ?>">
    <div class="grid">

      <!-- Formulari per crear o editar una varietat -->
      <div class="card span4">
        <h2><?= $edit_var ? "Editar varietat" : "Nova varietat" ?></h2>
        <form method="post">
          <!-- Camp ocult per indicar si creem o editem -->
          <input type="hidden" name="action" value="<?= $edit_var ? 'edit_var' : 'create_var' ?>">
          <?php if ($edit_var): ?>
              <!-- Si editem, enviem l'ID de la varietat -->
              <input type="hidden" name="id" value="<?= $edit_var['id'] ?>">
          <?php endif; ?>

          <!-- Selector de cultiu (a quin cultiu pertany la varietat) -->
          <label>Cultiu</label>
          <select name="cultiu_id" required>
            <option value="">—</option>
            <?php foreach ($cultius as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($edit_var && $edit_var['cultiu_id']==$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>

          <!-- Nom de la varietat -->
          <label>Nom varietat</label>
          <input name="name" value="<?= $edit_var ? htmlspecialchars($edit_var['name']) : '' ?>" required>

          <!-- Informació agronòmica (opcional) -->
          <label>Informació agronòmica</label>
          <textarea name="informacio_agronomica" rows="3"><?= $edit_var ? htmlspecialchars($edit_var['informacio_agronomica']) : '' ?></textarea>

          <button class="btn" type="submit"><?= $edit_var ? "Desar" : "Crear" ?></button>
          <?php if ($edit_var): ?>
              <a href="cultius.php" class="btn secondary">Cancel·lar</a>
          <?php endif; ?>
        </form>
      </div>

      <!-- Taula amb la llista de totes les varietats -->
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
                <!-- Ressaltem la fila si s'està editant aquesta varietat -->
                <tr style="<?= ($edit_var && $edit_var['id'] == $v['id']) ? 'background: rgba(var(--primary-color-rgb), 0.1);' : '' ?>">
                  <td>
                    <strong><?= htmlspecialchars($v['name']) ?></strong>
                    <?php if ($v['informacio_agronomica']): ?>
                        <!-- Mostrem la info agronòmica si n'hi ha -->
                        <div class="small text-muted"><?= htmlspecialchars($v['informacio_agronomica']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($v['cultiu_name']) ?></td>
                  <td style="text-align:right">
                    <!-- Botó per editar -->
                    <a href="cultius.php?edit_var=<?= $v['id'] ?>" class="btn btn-small">✏️</a>
                    <!-- Botó per eliminar (amb confirmació) -->
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

<!-- JavaScript per canviar entre pestanyes -->
<script>
function switchTab(tabId) {
    // Desactivem totes les pestanyes i continguts
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
    
    // Activem la pestanya i el contingut seleccionat
    document.getElementById(tabId).classList.add('active');
    event.currentTarget.classList.add('active');
}
</script>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>