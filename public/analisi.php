<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';

require_login();

// Dades bàsiques per analítica
$parceles = db()->query("SELECT id, name, area_ha FROM parcela ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

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

  <div class="card span12">
    <h2>Parcel·les</h2>

    <?php if (!$parceles): ?>
      <p class="small">No hi ha parcel·les registrades.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Àrea (ha)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($parceles as $p): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td><?= htmlspecialchars($p['name']) ?></td>
              <td><?= htmlspecialchars($p['area_ha']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
