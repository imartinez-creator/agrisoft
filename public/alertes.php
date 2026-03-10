<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';

require_login();

// Alertes de productes amb stock baix
$stock_baix = db()->query("
  SELECT name, stock, stock_baix
  FROM fito_productes
  WHERE stock <= stock_baix
  ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

// Alertes de plans pendents amb data passada
$plans_retard = db()->query("
  SELECT pt.title, pt.planned_on,
         p.name AS parcela_name,
         s.nom_sector AS sector_name
  FROM plans_tractament pt
  LEFT JOIN parcela p ON p.id = pt.parcela_id
  LEFT JOIN sector_cultiu s ON s.id = pt.sector_id
  WHERE pt.status = 'pendent'
    AND pt.planned_on < CURDATE()
  ORDER BY pt.planned_on ASC
")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Alertes · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <div class="card span6">
    <h2>Stock baix</h2>

    <?php if (!$stock_baix): ?>
      <p class="small">No hi ha productes amb stock baix.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Producte</th>
            <th>Stock</th>
            <th>Mínim</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($stock_baix as $p): ?>
            <tr>
              <td><?= htmlspecialchars($p['name']) ?></td>
              <td><?= htmlspecialchars($p['stock']) ?></td>
              <td><?= htmlspecialchars($p['stock_baix']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card span6">
    <h2>Plans pendents fora de termini</h2>

    <?php if (!$plans_retard): ?>
      <p class="small">No hi ha plans fora de termini.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Data</th>
            <th>Títol</th>
            <th>Ubicació</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($plans_retard as $pt): ?>
            <tr>
              <td><?= htmlspecialchars($pt['planned_on']) ?></td>
              <td><?= htmlspecialchars($pt['title']) ?></td>
              <td>
                <?= htmlspecialchars($pt['parcela_name'] ?? '—') ?>
                <?php if (!empty($pt['sector_name'])): ?>
                  <div class="small">Sector: <?= htmlspecialchars($pt['sector_name']) ?></div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
