<?php
/* ===== Càrrega de fitxers necessaris ===== */
require_once __DIR__ . '/../app/config/db.php';       // Connexió a la base de dades
require_once __DIR__ . '/../app/middleware/auth.php';  // Control d'accés
require_once __DIR__ . '/../app/helpers/flash.php';    // Missatges flash

// Comprova que l'usuari hagi iniciat sessió
require_login();


/* ===== Eliminar una anàlisi ===== */
// Si rebem el paràmetre 'delete' per GET, esborrem l'anàlisi de la BD
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db()->prepare("DELETE FROM analisis WHERE id = ?")->execute([$id]);
    flash_set("Anàlisi eliminat.", "ok");
    header("Location: analisi.php");
    exit;
}


/* ===== Crear o Editar una anàlisi (formulari POST) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'], ['create_analisi', 'update_analisi'])) {
    // Recollim les dades del formulari
    $analitzat      = $_POST['analitzat'] ?? date('Y-m-d');       // Data de l'anàlisi
    $parcela_id     = ($_POST['parcela_id'] ?? '') !== '' ? (int)$_POST['parcela_id'] : null;  // Parcel·la (opcional)
    $sector_id      = ($_POST['sector_id'] ?? '') !== '' ? (int)$_POST['sector_id'] : null;    // Sector (opcional)
    $tipus_analisi  = $_POST['tipus_analisi'] ?? 'sol';           // Tipus: sòl o fulla
    $resum          = trim($_POST['resum'] ?? '');                // Resum dels resultats
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'create_analisi') {
        // Inserim una nova anàlisi a la BD
        $st = db()->prepare("
            INSERT INTO analisis (analitzat, parcela_id, sector_id, tipus_anàlisi, resum, creat)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $st->execute([$analitzat, $parcela_id, $sector_id, $tipus_analisi, $resum, $_SESSION['user']['id']]);
        flash_set("Anàlisi registrat.", "ok");
    } elseif ($action === 'update_analisi') {
        // Actualitzem una anàlisi existent
        $st = db()->prepare("
            UPDATE analisis SET analitzat=?, parcela_id=?, sector_id=?, tipus_anàlisi=?, resum=?
            WHERE id=?
        ");
        $st->execute([$analitzat, $parcela_id, $sector_id, $tipus_analisi, $resum, $id]);
        flash_set("Anàlisi actualitzat.", "ok");
    }
    header("Location: analisi.php");
    exit;
}


/* ===== Carregar dades per editar una anàlisi ===== */
$edit_item = null;
if (isset($_GET['edit'])) {
    $st = db()->prepare("SELECT * FROM analisis WHERE id = ?");
    $st->execute([(int)$_GET['edit']]);
    $edit_item = $st->fetch(PDO::FETCH_ASSOC);
}


/* ===== Obtenir llistes per als selectors ===== */
$parceles = db()->query("SELECT id, name FROM parcela ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$sectors  = db()->query("SELECT id, nom AS name, parcela_id FROM sectors ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);


/* ===== Obtenir totes les anàlisis amb noms de parcel·la i sector ===== */
$analisis_records = db()->query("
  SELECT a.*, p.name AS parcela_name, s.nom AS sector_name
  FROM analisis a
  LEFT JOIN parcela p ON p.id = a.parcela_id
  LEFT JOIN sectors s ON s.id = a.sector_id
  ORDER BY a.analitzat DESC, a.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* ===== Estadístiques generals per a la pestanya de KPIs ===== */
$stats = [
  'parceles'      => (int)db()->query("SELECT COUNT(*) FROM parcela")->fetchColumn(),
  'sectors'       => (int)db()->query("SELECT COUNT(*) FROM sectors")->fetchColumn(),
  'cultius'       => (int)db()->query("SELECT COUNT(*) FROM cultius")->fetchColumn(),
  'tractaments'   => (int)db()->query("SELECT COUNT(*) FROM tractaments")->fetchColumn(),
  'treballadors'  => (int)db()->query("SELECT COUNT(*) FROM treballadors")->fetchColumn(),
];

/* ===== Títol de la pàgina i capçalera HTML ===== */
$titol = "Anàlisi · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>


  <!-- Pestanyes: Gestió d'Anàlisis / KPIs -->
  <div class="tabs-nav">
    <div class="tab-link active" onclick="switchTab('gestio')">🧪 Gestió d'Anàlisis</div>
    <div class="tab-link" onclick="switchTab('kpis')">📊 KPIs de Campanya</div>
  </div>

  <!-- ===== PESTANYA: GESTIÓ D'ANÀLISIS ===== -->
  <div id="gestio" class="tab-content active">
    <div class="grid">

      <!-- Formulari per crear o editar una anàlisi -->
      <div class="card span4">
        <h2><?= $edit_item ? 'Editar anàlisi' : 'Nou anàlisi' ?></h2>
        <p class="small">Registra resultats de laboratori de sòl o fulla.</p>

        <form method="post">
          <input type="hidden" name="action" value="<?= $edit_item ? 'update_analisi' : 'create_analisi' ?>">
          <?php if ($edit_item): ?>
            <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
          <?php endif; ?>

          <!-- Data de l'anàlisi -->
          <label>Data de l'anàlisi</label>
          <input type="date" name="analitzat" value="<?= $edit_item ? $edit_item['analitzat'] : date('Y-m-d') ?>" required>

          <!-- Selector de parcel·la -->
          <label>Parcel·la</label>
          <select name="parcela_id" id="select_parcela">
            <option value="">—</option>
            <?php foreach ($parceles as $p): ?>
              <option value="<?= $p['id'] ?>" <?= ($edit_item && $edit_item['parcela_id'] == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
          </select>

          <!-- Selector de sector (es filtra per parcel·la amb JS) -->
          <label>Sector</label>
          <select name="sector_id" id="select_sector">
            <option value="">—</option>
            <?php foreach ($sectors as $s): ?>
              <option value="<?= $s['id'] ?>" data-parcela="<?= $s['parcela_id'] ?>" <?= ($edit_item && $edit_item['sector_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>

          <!-- Tipus d'anàlisi: sòl o fulla -->
          <label>Tipus d'anàlisi</label>
          <select name="tipus_analisi">
            <option value="sol" <?= ($edit_item && $edit_item['tipus_anàlisi']=='sol') ? 'selected' : '' ?>>Sòl</option>
            <option value="fulla" <?= ($edit_item && $edit_item['tipus_anàlisi']=='fulla') ? 'selected' : '' ?>>Fulla</option>
          </select>

          <!-- Resum dels resultats -->
          <label>Resum de resultats</label>
          <textarea name="resum" rows="5" placeholder="Ex: NPK, matèria orgànica, deficiències detectades..."><?= $edit_item ? htmlspecialchars($edit_item['resum'] ?? '') : '' ?></textarea>

          <div style="margin-top:15px;">
            <button class="btn" type="submit"><?= $edit_item ? 'Actualitzar' : 'Desar' ?></button>
            <?php if ($edit_item): ?>
              <a href="analisi.php" class="btn secondary">Cancel·lar</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- Taula amb l'historial d'anàlisis -->
      <div class="card span8">
        <h2>Historial d'anàlisis</h2>
        <?php if (!$analisis_records): ?>
          <p class="small">No hi ha anàlisis de laboratòria registrats.</p>
        <?php else: ?>
          <table class="table">
            <thead>
              <tr>
                <th>Data</th>
                <th>Tipus</th>
                <th>Ubicació</th>
                <th style="text-align:right">Accions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($analisis_records as $ar): ?>
                <tr>
                  <td>
                    <strong><?= date('d/m/Y', strtotime($ar['analitzat'])) ?></strong>
                    <!-- Resum truncat en una línia -->
                    <div class="small text-muted" style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($ar['resum'] ?? '') ?></div>
                  </td>
                  <td><span class="badge"><?= ucfirst($ar['tipus_anàlisi']) ?></span></td>
                  <td>
                    <small><?= htmlspecialchars($ar['parcela_name'] ?? '—') ?></small>
                    <?php if (!empty($ar['sector_name'])): ?>
                       <div class="small">Sec: <?= htmlspecialchars($ar['sector_name']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td style="text-align:right">
                    <!-- Botó editar -->
                    <a href="analisi.php?edit=<?= $ar['id'] ?>" class="btn btn-small">✏️</a>
                    <!-- Botó eliminar -->
                    <a href="analisi.php?delete=<?= $ar['id'] ?>" class="btn btn-small" onclick="return confirm('Eliminar?')">🗑️</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ===== PESTANYA: KPIs DE CAMPANYA ===== -->
  <div id="kpis" class="tab-content">
    <div class="card">
      <h2>KPIs de Campanya</h2>
      <p class="small">Resum d'indicadors clau per a la campanya actual.</p>
      <div class="grid">
        <?php foreach ($stats as $k => $v): ?>
          <div class="card span2">
            <div class="kpi">
              <div>
                <div class="small"><?= htmlspecialchars(ucfirst($k)) ?></div>
                <div class="n"><?= (int)$v ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

</div>

<!-- JavaScript per canviar pestanyes i filtrar sectors per parcel·la -->
<script>
// Canvi de pestanyes
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
    
    document.getElementById(tabId).classList.add('active');
    event.currentTarget.classList.add('active');
}

// Filtrar sectors quan canviem de parcel·la
document.getElementById('select_parcela').addEventListener('change', function() {
    const parcelaId = this.value;
    const sectorSelect = document.getElementById('select_sector');
    const sectors = sectorSelect.querySelectorAll('option');

    sectors.forEach(opt => {
        if (opt.value === "") {
            opt.style.display = "block";
            return;
        }
        // Mostrem només els sectors de la parcel·la seleccionada
        if (parcelaId === "" || opt.getAttribute('data-parcela') === parcelaId) {
            opt.style.display = "block";
        } else {
            opt.style.display = "none";
        }
    });

    // Reiniciem la selecció de sector
    if(event && event.type === 'change') {
        sectorSelect.value = "";
    }
});
// Executem el filtre al carregar la pàgina
document.getElementById('select_parcela').dispatchEvent(new Event('change'));
</script>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
