<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';

require_login();

// Totals globals (usant collites)
$totals = db()->query("
  SELECT 
    SUM(kg) AS total_produccio,
    COUNT(*) AS total_collites,
    (SELECT name FROM parcela p JOIN collites co ON p.id = co.parcela_id GROUP BY p.id ORDER BY SUM(co.kg) DESC LIMIT 1) AS millor_parcela
  FROM collites
")->fetch(PDO::FETCH_ASSOC);

// Resum per any
$per_any = db()->query("
  SELECT
    COALESCE(any_campanya, YEAR(recollit)) AS any_campanya,
    SUM(kg) AS total_kg,
    COUNT(*) AS num_collites
  FROM collites
  GROUP BY COALESCE(any_campanya, YEAR(recollit))
  ORDER BY any_campanya DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Resum per cultiu
$per_cultiu = db()->query("
  SELECT
    COALESCE(c.name, '—') AS cultiu,
    SUM(co.kg) AS total_kg,
    COUNT(*) AS num_collites
  FROM collites co
  LEFT JOIN varietats v ON v.id = co.varietat_id
  LEFT JOIN cultius c ON c.id = v.cultiu_id
  GROUP BY COALESCE(c.id, 0), COALESCE(c.name, '—')
  ORDER BY total_kg DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Resum per parcel·la
$per_parcela = db()->query("
  SELECT
    COALESCE(p.name, '—') AS parcela,
    SUM(co.kg) AS total_kg,
    COUNT(*) AS num_collites
  FROM collites co
  LEFT JOIN parcela p ON p.id = co.parcela_id
  GROUP BY COALESCE(p.id, 0), COALESCE(p.name, '—')
  ORDER BY total_kg DESC
")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Informes · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <!-- KPIs de Producció -->
  <div class="card span4">
    <div class="kpi">
      <div>
        <div class="small">Producció Total</div>
        <div class="n"><?= number_format((float)($totals['total_produccio'] ?? 0), 0, ',', '.') ?> <small>kg</small></div>
      </div>
      <div style="font-size:32px">🚜</div>
    </div>
  </div>

  <div class="card span4">
    <div class="kpi">
      <div>
        <div class="small">Total Collites</div>
        <div class="n"><?= (int)($totals['total_collites'] ?? 0) ?></div>
      </div>
      <div style="font-size:32px">📦</div>
    </div>
  </div>

  <div class="card span4">
    <div class="kpi">
      <div>
        <div class="small">Millor Parcel·la</div>
        <div class="n" style="font-size:1.6rem"><?= htmlspecialchars($totals['millor_parcela'] ?? '—') ?></div>
      </div>
      <div style="font-size:32px">🏆</div>
    </div>
  </div>

  <div class="card span12" style="margin-top:20px">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">
      <span style="font-size:24px;">📈</span>
      <h2 style="margin:0">Producció per any</h2>
    </div>
    <?php if (!$per_any): ?>
      <div class="status-empty">
        <p>Encara no hi ha dades de collites per generar informes.</p>
      </div>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Any de campanya</th>
            <th>Número de collites</th>
            <th style="text-align:right">Total produït (kg)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($per_any as $r): ?>
            <tr>
              <td><strong><?= htmlspecialchars($r['any_campanya']) ?></strong></td>
              <td><?= (int)$r['num_collites'] ?> records</td>
              <td style="text-align:right; font-weight:600; font-size:1.1rem;"><?= number_format((float)$r['total_kg'], 2, ',', '.') ?> kg</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card span6">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">
      <span style="font-size:24px;">🌿</span>
      <h2 style="margin:0">Producció per cultiu</h2>
    </div>
    <?php if (!$per_cultiu): ?>
      <p class="small text-muted">Dades insuficients.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Cultiu</th>
            <th style="text-align:right">Total (kg)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($per_cultiu as $r): ?>
            <tr>
              <td><strong><?= htmlspecialchars($r['cultiu']) ?></strong></td>
              <td style="text-align:right"><?= number_format((float)$r['total_kg'], 2, ',', '.') ?> kg</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card span6">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">
      <span style="font-size:24px;">📍</span>
      <h2 style="margin:0">Producció per parcel·la</h2>
    </div>
    <?php if (!$per_parcela): ?>
      <p class="small text-muted">Dades insuficients.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Parcel·la</th>
            <th style="text-align:right">Total (kg)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($per_parcela as $r): ?>
            <tr>
              <td><strong><?= htmlspecialchars($r['parcela'] ?? '—') ?></strong></td>
              <td style="text-align:right"><?= number_format((float)$r['total_kg'], 2, ',', '.') ?> kg</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
