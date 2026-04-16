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
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">
      <span style="font-size:24px;">📦</span>
      <h2 style="margin:0">Stock baix</h2>
    </div>

    <?php if (!$stock_baix): ?>
      <div class="status-empty">
        <p>No hi ha productes amb stock baix. Tot correcte.</p>
      </div>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Producte</th>
            <th>Stock actual</th>
            <th>Mínim</th>
            <th style="text-align:right">Estat</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($stock_baix as $p): ?>
            <tr>
              <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
              <td><?= htmlspecialchars($p['stock']) ?></td>
              <td><?= htmlspecialchars($p['stock_baix']) ?></td>
              <td style="text-align:right">
                <span class="badge" style="background:#fee2e2; color:#991b1b;">Crític</span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card span6">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">
      <span style="font-size:24px;">⚠️</span>
      <h2 style="margin:0">Plans fora de termini</h2>
    </div>

    <?php if (!$plans_retard): ?>
      <div class="status-empty">
        <p>No hi ha plans pendents amb data passada. Bona feina!</p>
      </div>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Data límit</th>
            <th>Títol del pla</th>
            <th>Ubicació</th>
            <th style="text-align:right">Accions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($plans_retard as $pt): ?>
            <tr>
              <td><span style="color:#991b1b; font-weight:600;"><?= date('d/m/Y', strtotime($pt['planned_on'])) ?></span></td>
              <td><strong><?= htmlspecialchars($pt['title']) ?></strong></td>
              <td>
                <small><?= htmlspecialchars($pt['parcela_name'] ?? '—') ?></small>
                <?php if (!empty($pt['sector_name'])): ?>
                  <div class="small text-muted">Sec: <?= htmlspecialchars($pt['sector_name']) ?></div>
                <?php endif; ?>
              </td>
              <td style="text-align:right">
                <a href="plagues.php" class="btn btn-small">Gestionar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
