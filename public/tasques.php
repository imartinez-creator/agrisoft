<?php
/* ===== Gestió de Tasques Assignades ===== */
require_once __DIR__ . '/../app/config/db.php';       // Connexió a la base de dades
require_once __DIR__ . '/../app/middleware/auth.php';  // Control d'accés
require_once __DIR__ . '/../app/helpers/flash.php';    // Missatges flash

// Comprova que l'usuari hagi iniciat sessió
require_login();

// ============================================
// ACCIONS: TAULA 'tasques' (Assignacions)
// ============================================

// Eliminar tasca assignada
if (isset($_GET['delete_task'])) {
    if (!can_manage()) die("Error: Sense permisos per eliminar tasques.");
    $id = (int)$_GET['delete_task'];
    db()->prepare("DELETE FROM tasques WHERE id = ?")->execute([$id]);
    flash_set("Tasca eliminada.", "ok");
    header("Location: tasques.php");
    exit;
}

// Canviar estat tasca assignada (fet)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_status_task') {
    // Els treballadors sí que poden canviar l'estat a fet/pendent
    $id = (int)$_POST['id'];
    $status = isset($_POST['status_fet']) ? 'fet' : 'pendent';
    db()->prepare("UPDATE tasques SET status = ? WHERE id = ?")->execute([$status, $id]);
    flash_set("Estat de la tasca actualitzat.", "ok");
    
    // Per tornar a la mateixa pestanya segons el nou estat:
    if($status === 'fet') {
         header("Location: tasques.php#realitzades");
    } else {
         header("Location: tasques.php#pendents");
    }
    exit;
}

// Crear o Editar tasca assignada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(($_POST['action'] ?? ''), ['create_task', 'edit_task'])) {
    if (!can_manage()) die("Error: Sense permisos per crear o editar assignacions.");
    $action = $_POST['action'];
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $assigned_to = !empty($_POST['assigned_to_id_treballador']) ? $_POST['assigned_to_id_treballador'] : null;
    $parcela_id = !empty($_POST['parcela_id']) ? $_POST['parcela_id'] : null;
    $sector_id = !empty($_POST['sector_id']) ? $_POST['sector_id'] : null;
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

    if ($action === 'create_task') {
        $st = db()->prepare("
            INSERT INTO tasques (title, description, assigned_to_id_treballador, parcela_id, sector_id, due_date, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pendent')
        ");
        $st->execute([$title, $description, $assigned_to, $parcela_id, $sector_id, $due_date]);
        flash_set("Tasca assignada correctament.", "ok");
    } elseif ($action === 'edit_task') {
        $st = db()->prepare("
            UPDATE tasques SET title=?, description=?, assigned_to_id_treballador=?, parcela_id=?, sector_id=?, due_date=?
            WHERE id=?
        ");
        $st->execute([$title, $description, $assigned_to, $parcela_id, $sector_id, $due_date, $id]);
        flash_set("Tasca assignada actualitzada.", "ok");
    }
    header("Location: tasques.php");
    exit;
}

// ============================================
// LECTURA DE DADES
// ============================================

// Detectar edició de tasques
$edit_task = null;
if (isset($_GET['edit_task'])) {
    $st = db()->prepare("SELECT * FROM tasques WHERE id = ?");
    $st->execute([(int)$_GET['edit_task']]);
    $edit_task = $st->fetch(PDO::FETCH_ASSOC);
}

// Seleccions globals
$treballadors = db()->query("SELECT id, nom_complet, user_id FROM treballadors ORDER BY nom_complet")->fetchAll(PDO::FETCH_ASSOC);
$parceles = db()->query("SELECT id, name FROM parcela ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$sectors  = db()->query("SELECT id, nom AS name, parcela_id FROM sectors ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

$my_role = $_SESSION['user']['role'] ?? '';
$my_user_id = $_SESSION['user']['id'] ?? 0;

$where_treballador_tasques = "";
$params_tasques = [];

if (!can_manage()) {
    $workerObj = db()->prepare("SELECT id FROM treballadors WHERE user_id = ? LIMIT 1");
    $workerObj->execute([$my_user_id]);
    $wId = $workerObj->fetchColumn();

    $where_treballador_tasques = "WHERE tq.assigned_to_id_treballador = ?";
    $params_tasques[] = $wId ?: -1;
}

// Llistar Tasques
$st_tq = db()->prepare("
  SELECT tq.*,
         t.nom_complet AS treballador_nom,
         p.name AS parcela_name,
         s.nom AS sector_name
  FROM tasques tq
  LEFT JOIN treballadors t ON t.id = tq.assigned_to_id_treballador
  LEFT JOIN parcela p ON p.id = tq.parcela_id
  LEFT JOIN sectors s ON s.id = tq.sector_id
  $where_treballador_tasques
  ORDER BY tq.due_date ASC, tq.id DESC
");
$st_tq->execute($params_tasques);
$tasques_list = $st_tq->fetchAll(PDO::FETCH_ASSOC);

$tasques_pendents = [];
$tasques_fetes = [];

foreach($tasques_list as $t) {
    if($t['status'] === 'fet') {
        $tasques_fetes[] = $t;
    } else {
        $tasques_pendents[] = $t;
    }
}

$titol = "Tasques Assignades · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';

// Funció auxiliàr per resoldre les taules descarregant codi repetit
function render_task_table($tasks, $is_fet_table) {
    if (!$tasks) {
        $msg = $is_fet_table ? "No hi ha cap tasca completada." : "No hi ha cap tasca pendent.";
        echo '<p class="small">' . $msg . '</p>';
        return;
    }
    
    echo '<table class="table">';
    echo '<thead><tr>';
    echo '<th style="width: 50px; text-align: center;">Fet</th>';
    echo '<th>Data límit</th>';
    echo '<th>Títol / Descripció</th>';
    echo '<th>Treballador</th>';
    echo '<th>Ubicació</th>';
    if (can_manage()) {
        echo '<th style="text-align:right">Accions</th>';
    }
    echo '</tr></thead><tbody>';
    
    foreach ($tasks as $t) {
        $opacity = ($t['status'] === 'fet') ? 'opacity:0.6;' : '';
        echo '<tr style="' . $opacity . '">';
        
        // Casella
        echo '<td style="text-align: center;">';
        echo '<form method="post" style="margin: 0;">';
        echo '<input type="hidden" name="action" value="set_status_task">';
        echo '<input type="hidden" name="id" value="' . $t['id'] . '">';
        $checked = $t['status'] === 'fet' ? 'checked' : '';
        echo '<input type="checkbox" name="status_fet" onchange="this.form.submit()" ' . $checked . ' style="transform: scale(1.5); cursor: pointer;">';
        echo '</form>';
        echo '</td>';
        
        // Data
        echo '<td>';
        if ($t['due_date']) {
            echo '<strong>' . date('d/m/Y', strtotime($t['due_date'])) . '</strong>';
            if ($t['status'] !== 'fet' && $t['due_date'] < date('Y-m-d')) {
                echo '<br><span style="color:red;font-size:11px;">⚠️ Vençuda</span>';
            }
        } else {
            echo '-';
        }
        echo '</td>';
        
        // Titols
        echo '<td>';
        echo '<strong>' . htmlspecialchars($t['title']) . '</strong>';
        if (!empty($t['description'])) {
            echo '<div class="small text-muted" style="max-width:200px; white-space:normal;">' . htmlspecialchars($t['description']) . '</div>';
        }
        echo '</td>';
        
        // Treballador
        echo '<td class="small">' . htmlspecialchars($t['treballador_nom'] ?? 'Sense assignar') . '</td>';
        
        // Ubicacio
        echo '<td>';
        echo '<span class="small">' . htmlspecialchars($t['parcela_name'] ?? '—') . '</span>';
        if (!empty($t['sector_name'])) {
            echo '<div class="small text-muted">Sec: ' . htmlspecialchars($t['sector_name']) . '</div>';
        }
        echo '</td>';
        
        // Accions manager
        if (can_manage()) {
            echo '<td style="text-align:right; white-space:nowrap;">';
            echo '<a href="tasques.php?edit_task=' . $t['id'] . '" class="btn btn-small">✏️</a> ';
            echo '<a href="tasques.php?delete_task=' . $t['id'] . '" class="btn btn-small" onclick="return confirm(\'Segur?\')">🗑️</a>';
            echo '</td>';
        }
        echo '</tr>';
    }
    
    echo '</tbody></table>';
}
?>

<!-- Pestanyes de Tasques -->
<div class="tabs-nav">
  <div class="tab-link active" onclick="switchTab('pendents')">📄 Tasques Pendents</div>
  <div class="tab-link" onclick="switchTab('realitzades')">✅ Tasques Realitzades</div>
</div>

<!-- CONTAINER DE PESTANYES -->
<!-- PESTANYA 1: PENDENTS -->
<div id="pendents" class="tab-content active">
  <div class="grid">
    <?php if (can_manage()): ?>
    <div class="card span4">
      <h2><?= $edit_task ? 'Editar tasca' : 'Assignar tasca' ?></h2>
      <form method="post">
        <input type="hidden" name="action" value="<?= $edit_task ? 'edit_task' : 'create_task' ?>">
        <?php if ($edit_task): ?>
          <input type="hidden" name="id" value="<?= (int)$edit_task['id'] ?>">
        <?php endif; ?>

        <label>Títol de la tasca</label>
        <input name="title" required value="<?= $edit_task ? htmlspecialchars($edit_task['title']) : '' ?>">

        <label>Més detalls / Descripció</label>
        <textarea name="description" rows="3"><?= $edit_task ? htmlspecialchars($edit_task['description']) : '' ?></textarea>

        <label>Treballador assignat</label>
        <select name="assigned_to_id_treballador">
          <option value="">(Sense assignar)</option>
          <?php foreach ($treballadors as $t): ?>
            <option value="<?= $t['id'] ?>" <?= ($edit_task && $edit_task['assigned_to_id_treballador'] == $t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['nom_complet']) ?></option>
          <?php endforeach; ?>
        </select>

        <label>Límit (Data)</label>
        <input type="date" name="due_date" value="<?= $edit_task ? $edit_task['due_date'] : '' ?>">

        <label>Parcel·la</label>
        <select name="parcela_id" class="select_parcela_t">
          <option value="">—</option>
          <?php foreach ($parceles as $p): ?>
            <option value="<?= $p['id'] ?>" <?= ($edit_task && $edit_task['parcela_id'] == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
          <?php endforeach; ?>
        </select>

        <label>Sector</label>
        <select name="sector_id" class="select_sector_t">
          <option value="">—</option>
          <?php foreach ($sectors as $s): ?>
            <option value="<?= $s['id'] ?>" data-parcela="<?= $s['parcela_id'] ?>" <?= ($edit_task && $edit_task['sector_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>

        <div style="margin-top:15px;">
            <button class="btn" type="submit"><?= $edit_task ? 'Guardar tasca' : 'Assignar tasca' ?></button>
            <?php if ($edit_task): ?>
              <a href="tasques.php" class="btn secondary">Cancel·lar</a>
            <?php endif; ?>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="card <?= can_manage() ? 'span8' : 'span12' ?>">
      <h2>Tasques Pendents</h2>
      <?php render_task_table($tasques_pendents, false); ?>
    </div>
  </div>
</div>

<!-- PESTANYA 2: REALITZADES -->
<div id="realitzades" class="tab-content">
  <div class="grid">
    <div class="card span12">
      <h2>Tasques Realitzades</h2>
      <?php render_task_table($tasques_fetes, true); ?>
    </div>
  </div>
</div>

<script>
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
    
    document.getElementById(tabId).classList.add('active');
    event.currentTarget.classList.add('active');
    
    window.location.hash = tabId;
}

function setupSectorFilter(parcelaClass, sectorClass) {
    document.querySelectorAll('.' + parcelaClass).forEach(sel => {
        sel.addEventListener('change', function() {
            const parcelaId = this.value;
            const sectorSelect = this.form.querySelector('.' + sectorClass);
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
}
setupSectorFilter('select_parcela_t', 'select_sector_t');

// Activar pestanya segons el hash de la URL
if (window.location.hash === '#realitzades') {
    document.querySelectorAll('.tab-link')[1].click();
}
</script>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
