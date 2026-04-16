<?php
/* ===== Càrrega de fitxers necessaris ===== */
require_once __DIR__ . '/../app/config/db.php';       // Connexió a la base de dades
require_once __DIR__ . '/../app/middleware/auth.php';  // Control d'accés (autenticació)

// Comprova que l'usuari hagi iniciat sessió
require_login();


/* ===== Consulta 1: Productes amb stock baix ===== */
// Busquem tots els productes on l'stock actual és igual o inferior al mínim definit
$stock_baix = db()->query("
  SELECT name, stock, stock_baix
  FROM fito_productes
  WHERE stock <= stock_baix
  ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);


/* ===== Consulta 2: Plans de tractament fora de termini ===== */
// Busquem plans que estan pendents i la seva data prevista ja ha passat
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

/* ===== Títol de la pàgina i capçalera HTML ===== */
$titol = "Alertes · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <!-- ===== Targeta: Stock baix ===== -->
  <div class="card span6">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">
      <span style="font-size:24px;">📦</span>
      <h2 style="margin:0">Stock baix</h2>
    </div>

    <?php if (!$stock_baix): ?>
      <!-- Si no hi ha productes amb stock baix, tot va bé -->
      <div class="status-empty">
        <p>No hi ha productes amb stock baix. Tot correcte.</p>
      </div>
    <?php else: ?>
      <!-- Taula amb els productes que tenen stock crític -->
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
                <!-- Etiqueta vermella per indicar estat crític -->
                <span class="badge" style="background:#fee2e2; color:#991b1b;">Crític</span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- ===== Targeta: Plans fora de termini ===== -->
  <div class="card span6">
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">
      <span style="font-size:24px;">⚠️</span>
      <h2 style="margin:0">Plans fora de termini</h2>
    </div>

    <?php if (!$plans_retard): ?>
      <!-- Si no hi ha plans endarrerits, tot va bé -->
      <div class="status-empty">
        <p>No hi ha plans pendents amb data passada. Bona feina!</p>
      </div>
    <?php else: ?>
      <!-- Taula amb els plans que ja haurien d'estar fets -->
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
              <!-- Data en vermell per destacar que està fora de termini -->
              <td><span style="color:#991b1b; font-weight:600;"><?= date('d/m/Y', strtotime($pt['planned_on'])) ?></span></td>
              <td><strong><?= htmlspecialchars($pt['title']) ?></strong></td>
              <td>
                <!-- Nom de la parcel·la -->
                <small><?= htmlspecialchars($pt['parcela_name'] ?? '—') ?></small>
                <?php if (!empty($pt['sector_name'])): ?>
                  <!-- Nom del sector (si n'hi ha) -->
                  <div class="small text-muted">Sec: <?= htmlspecialchars($pt['sector_name']) ?></div>
                <?php endif; ?>
              </td>
              <td style="text-align:right">
                <!-- Botó per anar a gestionar el pla -->
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
