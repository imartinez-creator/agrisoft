<?php
/* ===== Detall de Parcel·la ===== */

require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();
$can_manage = can_manage();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    flash_set("ID de parcel·la no vàlid.", "err");
    header('Location: parcelles.php');
    exit;
}

// Obtenir dades de la parcel·la
$st = db()->prepare("SELECT * FROM parcela WHERE id = ?");
$st->execute([$id]);
$parcela = $st->fetch(PDO::FETCH_ASSOC);

if (!$parcela) {
    flash_set("La parcel·la no existeix.", "err");
    header('Location: parcelles.php');
    exit;
}

// Obtenir els sectors associats
$st_sec = db()->prepare("
    SELECT s.*, c.name AS cultiu_nom
    FROM sectors s
    LEFT JOIN cultius c ON c.id = s.cultiu_id
    WHERE s.parcela_id = ?
    ORDER BY s.nom ASC
");
$st_sec->execute([$id]);
$sectors = $st_sec->fetchAll(PDO::FETCH_ASSOC);

$titol = htmlspecialchars($parcela['name']) . " - Detall · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">
    <div class="span12" style="margin-bottom: 20px;">
        <a href="parcelles.php" class="btn secondary">⬅ Tornar a Parcel·les</a>
    </div>

    <!-- Dades bàsiques -->
    <div class="card span4">
        <h2>Detall de la Parcel·la</h2>
        <ul style="list-style: none; padding: 0; line-height: 1.8;">
            <li><strong>Nom:</strong> <?= htmlspecialchars($parcela['name']) ?></li>
            <li><strong>Superfície:</strong> <?= htmlspecialchars($parcela['area_ha']) ?> ha</li>
            <li><strong>Infraestructures:</strong> <?= htmlspecialchars($parcela['infraestructures'] ?? '-') ?></li>
            <li><strong>GPS:</strong> <?= htmlspecialchars($parcela['gps_lat'] ?? '-') ?>, <?= htmlspecialchars($parcela['gps_lng'] ?? '-') ?></li>
        </ul>
        <?php if (!empty($parcela['notes'])): ?>
            <div style="margin-top: 15px; padding: 10px; background: rgba(0,0,0,0.03); border-radius: 8px;">
                <strong>Observacions:</strong><br>
                <?= nl2br(htmlspecialchars($parcela['notes'])) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sectors associats -->
    <div class="card span8">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h2 style="margin:0;">Sectors a la Parcel·la</h2>
            <?php if ($can_manage): ?>
                <a href="sectors.php" class="btn btn-small">Gestió de Sectors</a>
            <?php endif; ?>
        </div>

        <?php if (empty($sectors)): ?>
            <div style="text-align:center; padding:30px; background:rgba(0,0,0,0.02); border-radius:12px;">
                <span style="font-size:36px; display:block; margin-bottom:10px;">🌾</span>
                <p>Aquesta parcel·la encara no té cap sector creat.</p>
                <?php if ($can_manage): ?>
                    <a href="sectors.php" class="btn btn-small">Crear primer sector</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom del Sector</th>
                        <th>Cultiu i Varietat</th>
                        <th>Superfície (ha)</th>
                        <th>Plantació / Arbres</th>
                        <th>Estat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sectors as $s): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($s['nom']) ?></strong>
                            </td>
                            <td>
                                <span style="color:#16a34a; font-weight:600;">
                                    <?= htmlspecialchars($s['cultiu_nom'] ?? 'Sense cultiu') ?>
                                </span>
                                <?php if (!empty($s['varietat'])): ?>
                                    <br><span class="small" style="color:#6b7280;"><?= htmlspecialchars($s['varietat']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($s['superficie'] ?? '0') ?> ha</td>
                            <td class="small">
                                <?php if (!empty($s['data_plantacio'])): ?>
                                    📅 <?= htmlspecialchars($s['data_plantacio']) ?><br>
                                <?php endif; ?>
                                <?php if (!empty($s['num_arbres'])): ?>
                                    🌳 <?= htmlspecialchars($s['num_arbres']) ?> arbres
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= htmlspecialchars($s['estat_actual'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
