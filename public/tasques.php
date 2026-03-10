<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

// Crear registre de tasca (treball)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
  $st = db()->prepare("
    INSERT INTO resgistres_treball
      (id_treballador, parcela_id, sector_id, work_date, hours, task)
    VALUES (?, ?, ?, ?, ?, ?)
  ");

  $st->execute([
    $_POST['id_treballador'],
    $_POST['parcela_id'] !== '' ? $_POST['parcela_id'] : null,
    $_POST['sector_id'] !== '' ? $_POST['sector_id'] : null,
    $_POST['work_date'],
    (float)$_POST['hours'],
    trim($_POST['task'] ?? '')
  ]);

  flash_set("Tasca registrada correctament.", "ok");
  header("Location: tasques.php");
  exit;
}

// Dades per als selects
$treballadors = db()->query("SELECT id, nom_complet FROM treballadors ORDER BY nom_complet")->fetchAll(PDO::FETCH_ASSOC);
$parceles = db()->query("SELECT id, name FROM parcela ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$sectors  = db()->query("SELECT id, nom_sector AS name, parcela_id FROM sector_cultiu ORDER BY nom_sector")->fetchAll(PDO::FETCH_ASSOC);

// Llistar tasques
$tasques = db()->query("
  SELECT rt.*,
         t.nom_complet,
         p.name AS parcela_name,
         s.nom_sector AS sector_name
  FROM resgistres_treball rt
  JOIN treballadors t ON t.id = rt.id_treballador
  LEFT JOIN parcela p ON p.id = rt.parcela_id
  LEFT JOIN sector_cultiu s ON s.id = rt.sector_id
  ORDER BY rt.work_date DESC, rt.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Tasques · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <div class="card span6">
    <h2>Nova tasca</h2>

    <form method="post">
      <input type="hidden" name="action" value="create">

      <label>Treballador</label>
      <select name="id_treballador" required>
        <?php foreach ($treballadors as $t): ?>
          <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nom_complet']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Parcel·la</label>
      <select name="parcela_id" id="select_parcela">
        <option value="">—</option>
        <?php foreach ($parceles as $p): ?>
          <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Sector</label>
      <select name="sector_id" id="select_sector">
        <option value="">—</option>
        <?php foreach ($sectors as $s): ?>
          <option value="<?= $s['id'] ?>" data-parcela="<?= $s['parcela_id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Data</label>
      <input type="date" name="work_date" required>

      <label>Hores</label>
      <input type="number" step="0.25" name="hours" required>

      <label>Tasca</label>
      <input name="task" placeholder="Ex: Poda, collita, tractament...">

      <button class="btn" type="submit">Desar</button>
    </form>
  </div>

  <div class="card span6">
    <h2>Tasques registrades</h2>

    <?php if (!$tasques): ?>
      <p class="small">Encara no hi ha tasques registrades.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Data</th>
            <th>Treballador</th>
            <th>Parcel·la</th>
            <th>Hores</th>
            <th>Tasca</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tasques as $t): ?>
            <tr>
              <td><?= htmlspecialchars($t['work_date']) ?></td>
              <td><?= htmlspecialchars($t['nom_complet']) ?></td>
              <td>
                <?= htmlspecialchars($t['parcela_name'] ?? '') ?>
                <?php if (!empty($t['sector_name'])): ?>
                  <div class="small text-muted">Sec: <?= htmlspecialchars($t['sector_name']) ?></div>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($t['hours']) ?></td>
              <td><?= htmlspecialchars($t['task']) ?></td>
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

    sectorSelect.value = ""; 
});
</script>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
