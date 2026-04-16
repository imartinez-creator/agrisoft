<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';

require_login();

// Eliminar anàlisi
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db()->prepare("DELETE FROM analisis WHERE id = ?")->execute([$id]);
    flash_set("Anàlisi eliminat.", "ok");
    header("Location: analisi.php");
    exit;
}

// Crear o Editar anàlisi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'], ['create', 'edit'])) {
    $analitzat      = $_POST['analitzat'] ?? date('Y-m-d');
    $parcela_id     = ($_POST['parcela_id'] ?? '') !== '' ? (int)$_POST['parcela_id'] : null;
    $sector_id      = ($_POST['sector_id'] ?? '') !== '' ? (int)$_POST['sector_id'] : null;
    $tipus_analisi  = $_POST['tipus_analisi'] ?? 'sol';
    $resum          = trim($_POST['resum'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'create_analisi') {
        $st = db()->prepare("
            INSERT INTO analisis (analitzat, parcela_id, sector_id, tipus_anàlisi, resum, creat)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $st->execute([$analitzat, $parcela_id, $sector_id, $tipus_analisi, $resum, $_SESSION['user']['id']]);
        flash_set("Anàlisi registrat.", "ok");
    } elseif ($action === 'update_analisi') {
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

// Detectar edició
$edit_item = null;
if (isset($_GET['edit'])) {
    $st = db()->prepare("SELECT * FROM analisis WHERE id = ?");
    $st->execute([(int)$_GET['edit']]);
    $edit_item = $st->fetch(PDO::FETCH_ASSOC);
}

// Dades per selects
$parceles = db()->query("SELECT id, name FROM parcela ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$sectors  = db()->query("SELECT id, nom_sector AS name, parcela_id FROM sector_cultiu ORDER BY nom_sector")->fetchAll(PDO::FETCH_ASSOC);

// Llista d'anàlisis
$analisis_records = db()->query("
  SELECT a.*, p.name AS parcela_name, s.nom_sector AS sector_name
  FROM analisis a
  LEFT JOIN parcela p ON p.id = a.parcela_id
  LEFT JOIN sector_cultiu s ON s.id = a.sector_id
  ORDER BY a.analitzat DESC, a.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$stats = [
  'parceles'      => (int)db()->query("SELECT COUNT(*) FROM parcela")->fetchColumn(),
  'sectors'       => (int)db()->query("SELECT COUNT(*) FROM sector_cultiu")->fetchColumn(),
  'cultius'       => (int)db()->query("SELECT COUNT(*) FROM cultius")->fetchColumn(),
  'tractaments'   => (int)db()->query("SELECT COUNT(*) FROM tractaments")->fetchColumn(),
  'treballadors'  => (int)db()->query("SELECT COUNT(*) FROM treballadors")->fetchColumn(),
];

$titol = "Anàlisi · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>


  <div class="tabs-nav">
    <div class="tab-link active" onclick="switchTab('gestio')">🧪 Gestió d'Anàlisis</div>
    <div class="tab-link" onclick="switchTab('kpis')">📊 KPIs de Campanya</div>
  </div>

  <div id="gestio" class="tab-content active">
    <div class="grid">
      <div class="card span4">
        <h2><?= $edit_item ? 'Editar anàlisi' : 'Nou anàlisi' ?></h2>
        <p class="small">Registra resultats de laboratori de sòl o fulla.</p>

        <form method="post">
          <input type="hidden" name="action" value="<?= $edit_item ? 'update_analisi' : 'create_analisi' ?>">
          <?php if ($edit_item): ?>
            <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
          <?php endif; ?>

          <label>Data de l'anàlisi</label>
          <input type="date" name="analitzat" value="<?= $edit_item ? $edit_item['analitzat'] : date('Y-m-d') ?>" required>

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

          <label>Tipus d'anàlisi</label>
          <select name="tipus_analisi">
            <option value="sol" <?= ($edit_item && $edit_item['tipus_anàlisi']=='sol') ? 'selected' : '' ?>>Sòl</option>
            <option value="fulla" <?= ($edit_item && $edit_item['tipus_anàlisi']=='fulla') ? 'selected' : '' ?>>Fulla</option>
          </select>

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
                    <a href="analisi.php?edit=<?= $ar['id'] ?>" class="btn btn-small">✏️</a>
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

<script>
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
    
    document.getElementById(tabId).classList.add('active');
    event.currentTarget.classList.add('active');
}

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

    if(event && event.type === 'change') {
        sectorSelect.value = "";
    }
});
document.getElementById('select_parcela').dispatchEvent(new Event('change'));
</script>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
