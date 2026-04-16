<?php
/* ===== Gestió de tractaments fitosanitaris i fertilització ===== */
// Mòdul complet: CRUD, filtres avançats, càlculs automàtics i exportació PDF

require_once __DIR__ . '/../app/config/db.php';       // Connexió a la base de dades
require_once __DIR__ . '/../app/middleware/auth.php';  // Control d'accés
require_once __DIR__ . '/../app/helpers/flash.php';    // Missatges flash

// Comprova que l'usuari hagi iniciat sessió
require_login();

/* ===== Eliminar un tractament ===== */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db()->prepare("DELETE FROM tractaments WHERE id = ?")->execute([$id]);
    flash_set("Tractament eliminat correctament.", "ok");
    header("Location: tractaments.php");
    exit;
}

/* ===== Crear o Editar un tractament (formulari POST) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    // Recollim i netegem totes les dades del formulari
    $parcela_id   = !empty($_POST['parcela_id'])  ? (int)$_POST['parcela_id']  : null;
    $sector_id    = !empty($_POST['sector_id'])   ? (int)$_POST['sector_id']   : null;
    $fila_id      = !empty($_POST['fila_id'])     ? (int)$_POST['fila_id']     : null;
    $producte_id  = (int)$_POST['producte_id'];
    $aplicat      = $_POST['aplicat'];
    $hora         = !empty($_POST['hora']) ? $_POST['hora'] : null;
    $dosis_ha     = (float)$_POST['dosis_hectarea'];
    $unitat       = trim($_POST['unitat'] ?? '');
    $dosis_tot    = (float)$_POST['dosis_total'];
    $volum_caldo  = !empty($_POST['volum_caldo']) ? (float)$_POST['volum_caldo'] : null;
    $metode       = trim($_POST['metode'] ?? '');
    $operari_id   = !empty($_POST['operari_id'])  ? (int)$_POST['operari_id']  : null;
    $temperatura  = $_POST['temperatura'] !== '' ? (float)$_POST['temperatura'] : null;
    $humitat      = $_POST['humitat'] !== ''      ? (float)$_POST['humitat']    : null;
    $vent         = trim($_POST['vent'] ?? '');
    $notes        = trim($_POST['notes'] ?? '');

    if ($action === 'create_tractament') {
        $st = db()->prepare("
            INSERT INTO tractaments
              (parcela_id, sector_id, fila_id, producte_id, aplicat, hora,
               dosis_hectarea, unitat, dosis_total, volum_caldo, metode,
               operari_id, temperatura, humitat, vent, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $st->execute([
            $parcela_id, $sector_id, $fila_id, $producte_id, $aplicat, $hora,
            $dosis_ha, $unitat, $dosis_tot, $volum_caldo, $metode,
            $operari_id, $temperatura, $humitat, $vent, $notes,
            $_SESSION['user']['id']
        ]);

        flash_set("Tractament registrat correctament.", "ok");
        header("Location: tractaments.php");
        exit;

    } elseif ($action === 'edit_tractament') {
        $st = db()->prepare("
            UPDATE tractaments
            SET parcela_id = ?, sector_id = ?, fila_id = ?, producte_id = ?,
                aplicat = ?, hora = ?, dosis_hectarea = ?, unitat = ?,
                dosis_total = ?, volum_caldo = ?, metode = ?,
                operari_id = ?, temperatura = ?, humitat = ?, vent = ?, notes = ?
            WHERE id = ?
        ");
        $st->execute([
            $parcela_id, $sector_id, $fila_id, $producte_id,
            $aplicat, $hora, $dosis_ha, $unitat,
            $dosis_tot, $volum_caldo, $metode,
            $operari_id, $temperatura, $humitat, $vent, $notes,
            $id
        ]);

        flash_set("Tractament actualitzat correctament.", "ok");
        header("Location: tractaments.php");
        exit;
    }
}

/* ===== Detectar si s'està editant un tractament ===== */
$edit_item = null;
if (isset($_GET['id'])) {
    $st = db()->prepare("SELECT * FROM tractaments WHERE id = ?");
    $st->execute([(int)$_GET['id']]);
    $edit_item = $st->fetch(PDO::FETCH_ASSOC);
}

/* ===== Dades per als selects del formulari ===== */
$parceles    = db()->query("SELECT id, name, area_ha FROM parcela ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$all_sectors = db()->query("SELECT sc.id, sc.nom AS name, sc.parcela_id, sc.superficie, c.name AS cultiu_nom
                            FROM sectors sc
                            LEFT JOIN cultius c ON c.id = sc.cultiu_id
                            ORDER BY sc.nom")->fetchAll(PDO::FETCH_ASSOC);
$files       = db()->query("SELECT id, codi_fila, sector_id FROM files_arbres ORDER BY codi_fila")->fetchAll(PDO::FETCH_ASSOC);
$productes   = db()->query("SELECT id, name, substancia_activa, unitat, dosi_maxima, tipus FROM fito_productes ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$treballadors = db()->query("SELECT id, nom_complet FROM treballadors ORDER BY nom_complet")->fetchAll(PDO::FETCH_ASSOC);

/* ===== Filtres de la llista ===== */
$filtre_parcela  = $_GET['f_parcela']  ?? '';
$filtre_sector   = $_GET['f_sector']   ?? '';
$filtre_producte = $_GET['f_producte'] ?? '';
$filtre_data_des = $_GET['f_data_des'] ?? '';
$filtre_data_fins = $_GET['f_data_fins'] ?? '';

// Construïm la consulta amb filtres dinàmics
$sql = "SELECT t.*,
          p.name AS parcela_name,
          p.area_ha AS parcela_area,
          s.nom AS sector_name,
          s.superficie AS sector_area,
          cu.name AS cultiu_nom,
          f.codi_fila AS fila_name,
          fp.name AS producte_name,
          fp.substancia_activa,
          fp.unitat AS producte_unitat,
          tr.nom_complet AS operari_name
        FROM tractaments t
        LEFT JOIN parcela p ON p.id = t.parcela_id
        LEFT JOIN sectors s ON s.id = t.sector_id
        LEFT JOIN cultius cu ON cu.id = s.cultiu_id
        LEFT JOIN files_arbres f ON f.id = t.fila_id
        LEFT JOIN fito_productes fp ON fp.id = t.producte_id
        LEFT JOIN treballadors tr ON tr.id = t.operari_id
        WHERE 1=1";

$params = [];

if ($filtre_parcela !== '') {
    $sql .= " AND t.parcela_id = ?";
    $params[] = (int)$filtre_parcela;
}
if ($filtre_sector !== '') {
    $sql .= " AND t.sector_id = ?";
    $params[] = (int)$filtre_sector;
}
if ($filtre_producte !== '') {
    $sql .= " AND t.producte_id = ?";
    $params[] = (int)$filtre_producte;
}
if ($filtre_data_des !== '') {
    $sql .= " AND t.aplicat >= ?";
    $params[] = $filtre_data_des;
}
if ($filtre_data_fins !== '') {
    $sql .= " AND t.aplicat <= ?";
    $params[] = $filtre_data_fins;
}

$sql .= " ORDER BY t.aplicat DESC, t.hora DESC, t.id DESC";

$st = db()->prepare($sql);
$st->execute($params);
$tractaments = $st->fetchAll(PDO::FETCH_ASSOC);

/* ===== Mapa de superfícies per parcel·les (per JS) ===== */
$parcela_areas = [];
foreach ($parceles as $p) {
    $parcela_areas[$p['id']] = (float)($p['area_ha'] ?? 0);
}

/* ===== Títol de la pàgina i capçalera HTML ===== */
$titol = "Tractaments · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<!-- ===== Formulari de tractaments ===== -->
<div class="grid">

  <!-- ========== COLUMNA ESQUERRA: FORMULARI ========== -->
  <div class="card span6">
    <h2><?= $edit_item ? "Editar tractament" : "Nou tractament" ?></h2>
    <p class="small" style="margin-bottom:14px">Registra l'aplicació de productes fitosanitaris o fertilitzants.</p>

    <form method="post" id="formTractament">
      <input type="hidden" name="action" value="<?= $edit_item ? 'edit_tractament' : 'create_tractament' ?>">
      <?php if ($edit_item): ?>
          <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
      <?php endif; ?>

      <!-- 1. Data i hora -->
      <div class="grid" style="gap:12px">
        <div class="span3">
          <label>Data aplicació *</label>
          <input type="date" name="aplicat" required id="field_data"
                 value="<?= $edit_item ? htmlspecialchars($edit_item['aplicat']) : date('Y-m-d') ?>">
        </div>
        <div class="span3">
          <label>Hora</label>
          <input type="time" name="hora" id="field_hora"
                 value="<?= $edit_item ? htmlspecialchars($edit_item['hora'] ?? '') : date('H:i') ?>">
        </div>
      </div>

      <!-- 2. Ubicació: Parcel·la, Sector, Fila -->
      <label>Parcel·la</label>
      <select name="parcela_id" id="select_parcela">
        <option value="">— Selecciona la parcel·la —</option>
        <?php foreach ($parceles as $p): ?>
          <option value="<?= $p['id'] ?>"
                  data-area="<?= (float)($p['area_ha'] ?? 0) ?>"
                  <?= (($edit_item && $edit_item['parcela_id'] == $p['id']) || ($_GET['parcela_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['name']) ?> (<?= (float)($p['area_ha'] ?? 0) ?> ha)
          </option>
        <?php endforeach; ?>
      </select>

      <label>Sector</label>
      <select name="sector_id" id="select_sector">
        <option value="" data-area="0">— Selecciona sector —</option>
        <?php foreach ($all_sectors as $s): ?>
          <option value="<?= $s['id'] ?>"
                  data-parcela="<?= $s['parcela_id'] ?>"
                  data-area="<?= (float)($s['superficie'] ?? 0) ?>"
                  data-cultiu="<?= htmlspecialchars($s['cultiu_nom'] ?? '') ?>"
                  <?= (($edit_item && $edit_item['sector_id'] == $s['id']) || ($_GET['sector_id'] ?? '') == $s['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['name']) ?> (<?= (float)($s['superficie'] ?? 0) ?> ha)
          </option>
        <?php endforeach; ?>
      </select>

      <label>Fila (Opcional)</label>
      <select name="fila_id" id="select_fila">
        <option value="">— Totes / Cap —</option>
        <?php foreach ($files as $f): ?>
          <option value="<?= $f['id'] ?>" data-sector="<?= $f['sector_id'] ?>"
                  <?= ($edit_item && $edit_item['fila_id'] == $f['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($f['codi_fila']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- 3. Producte i informació -->
      <label>Producte *</label>
      <select name="producte_id" id="select_producte" required>
        <option value="">— Selecciona un producte —</option>
        <?php foreach ($productes as $p): ?>
          <option value="<?= $p['id'] ?>"
                  data-unitat="<?= htmlspecialchars($p['unitat']) ?>"
                  data-substancia="<?= htmlspecialchars($p['substancia_activa'] ?? '') ?>"
                  data-dosi-max="<?= (float)($p['dosi_maxima'] ?? 0) ?>"
                  data-tipus="<?= htmlspecialchars($p['tipus'] ?? 'fitosanitari') ?>"
                  <?= (($edit_item && $edit_item['producte_id'] == $p['id']) || ($_GET['producte_id'] ?? '') == $p['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- Info del producte (es mostra dinàmicament) -->
      <div id="producte_info" style="display:none; padding:10px; border-radius:12px; background:rgba(22,163,74,0.06); border:1px solid rgba(22,163,74,0.15); margin:8px 0; font-size:0.9rem;">
        <div><strong>Substància activa:</strong> <span id="info_substancia">—</span></div>
        <div><strong>Tipus:</strong> <span id="info_tipus">—</span></div>
        <div id="info_dosi_max_row"><strong>Dosi màxima:</strong> <span id="info_dosi_max">—</span></div>
      </div>

      <!-- Avís si s'excedeix la dosi màxima -->
      <div id="aviso_dosi" style="display:none; padding:10px; border-radius:12px; background:rgba(220,38,38,0.08); border:1px solid rgba(220,38,38,0.25); color:#991b1b; margin:8px 0; font-size:0.9rem;">
        <strong>Atenció:</strong> La dosi introduïda supera la dosi màxima recomanada per aquest producte.
      </div>

      <!-- 4. Dosis i càlcul automàtic -->
      <div class="grid" style="gap:12px">
        <div class="span2">
          <label>Dosi / ha *</label>
          <input type="number" step="0.01" name="dosis_hectarea" id="field_dosi_ha" required
                 value="<?= $edit_item ? htmlspecialchars($edit_item['dosis_hectarea']) : ($_GET['dosi_ha'] ?? '') ?>">
        </div>
        <div class="span2">
          <label>Unitat</label>
          <input type="text" name="unitat" id="field_unitat" placeholder="l/ha, kg/ha..."
                 value="<?= $edit_item ? htmlspecialchars($edit_item['unitat'] ?? '') : '' ?>">
        </div>
        <div class="span2">
          <label>Dosi total (calc.)</label>
          <input type="number" step="0.01" name="dosis_total" id="field_dosi_total" required
                 value="<?= $edit_item ? htmlspecialchars($edit_item['dosis_total']) : ($_GET['dosi_tot'] ?? '') ?>">
        </div>
      </div>

      <!-- Resultat del càlcul automàtic -->
      <div id="calcul_resultat" style="display:none; padding:8px 12px; border-radius:10px; background:rgba(22,163,74,0.08); border:1px solid rgba(22,163,74,0.18); margin:8px 0; font-size:0.88rem; color:#065f46;">
        <span id="calcul_text"></span>
      </div>

      <label>Volum de caldo (litres)</label>
      <input type="number" step="0.01" name="volum_caldo"
             placeholder="Litres de caldo aplicats"
             value="<?= $edit_item ? htmlspecialchars($edit_item['volum_caldo'] ?? '') : '' ?>">

      <!-- 5. Mètode d'aplicació -->
      <label>Mètode d'aplicació</label>
      <select name="metode" id="select_metode">
        <option value="">— Selecciona mètode —</option>
        <?php
          $metodes = ['Polvorització','Nebulització','Fertirrigació','Granulat','Aplicació manual','Tractament aeri','Injecció al sòl','Altres'];
          foreach ($metodes as $m):
            $sel = ($edit_item && ($edit_item['metode'] ?? '') === $m) ? 'selected' : '';
        ?>
          <option value="<?= htmlspecialchars($m) ?>" <?= $sel ?>><?= htmlspecialchars($m) ?></option>
        <?php endforeach; ?>
      </select>

      <!-- 6. Operari -->
      <label>Operari</label>
      <select name="operari_id" id="select_operari">
        <option value="">— Selecciona operari —</option>
        <?php foreach ($treballadors as $tr): ?>
          <option value="<?= $tr['id'] ?>"
                  <?= ($edit_item && ($edit_item['operari_id'] ?? '') == $tr['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($tr['nom_complet']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- 7. Condicions ambientals -->
      <div style="margin-top:10px; padding:10px; border-radius:12px; background:rgba(0,0,0,0.02); border:1px solid rgba(0,0,0,0.06);">
        <div style="display:flex; align-items:center; gap:6px; margin-bottom:8px;">
          <strong style="font-size:0.9rem; color:var(--muted);">Condicions ambientals</strong>
        </div>
        <div class="grid" style="gap:12px">
          <div class="span2">
            <label style="margin-top:0">Temperatura (°C)</label>
            <input type="number" step="0.1" name="temperatura" placeholder="Ex: 22.5"
                   value="<?= $edit_item ? htmlspecialchars($edit_item['temperatura'] ?? '') : '' ?>">
          </div>
          <div class="span2">
            <label style="margin-top:0">Humitat (%)</label>
            <input type="number" step="0.1" name="humitat" placeholder="Ex: 65"
                   value="<?= $edit_item ? htmlspecialchars($edit_item['humitat'] ?? '') : '' ?>">
          </div>
          <div class="span2">
            <label style="margin-top:0">Vent</label>
            <input type="text" name="vent" placeholder="Ex: Suau, NE"
                   value="<?= $edit_item ? htmlspecialchars($edit_item['vent'] ?? '') : '' ?>">
          </div>
        </div>
      </div>

      <!-- 8. Temps i Observacions -->
      <label>Temps d'aplicació</label>
      <input name="temps" placeholder="Ex: 2h 30min" value="<?= $edit_item ? htmlspecialchars($edit_item['temps'] ?? '') : '' ?>">

      <label>Observacions</label>
      <textarea name="notes" rows="3" placeholder="Anotacions addicionals sobre el tractament..."><?= $edit_item ? htmlspecialchars($edit_item['notes'] ?? '') : '' ?></textarea>

      <div style="display:flex; gap:10px; align-items:center; margin-top:14px;">
        <button class="btn" type="submit"><?= $edit_item ? "Actualitzar tractament" : "Desar tractament" ?></button>
        <?php if ($edit_item): ?>
            <a href="tractaments.php" class="btn secondary">Cancel·lar</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- ========== COLUMNA DRETA: LLISTA + FILTRES ========== -->
  <div class="card span6">

    <!-- Filtres -->
    <h2>Tractaments</h2>

    <form method="get" style="padding:10px; border-radius:12px; background:rgba(0,0,0,0.02); border:1px solid rgba(0,0,0,0.06); margin-bottom:14px;">
      <div style="display:flex; align-items:center; gap:6px; margin-bottom:8px;">
        <strong style="font-size:0.85rem; color:var(--muted);">Filtrar tractaments</strong>
      </div>
      <div class="grid" style="gap:10px">
        <div class="span3">
          <label style="margin:0 0 4px 0; font-size:0.82rem;">Parcel·la</label>
          <select name="f_parcela" style="font-size:0.85rem; padding:7px;">
            <option value="">Totes</option>
            <?php foreach ($parceles as $p): ?>
              <option value="<?= $p['id'] ?>" <?= $filtre_parcela == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="span3">
          <label style="margin:0 0 4px 0; font-size:0.82rem;">Producte</label>
          <select name="f_producte" style="font-size:0.85rem; padding:7px;">
            <option value="">Tots</option>
            <?php foreach ($productes as $p): ?>
              <option value="<?= $p['id'] ?>" <?= $filtre_producte == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="span2">
          <label style="margin:0 0 4px 0; font-size:0.82rem;">Des de</label>
          <input type="date" name="f_data_des" value="<?= htmlspecialchars($filtre_data_des) ?>" style="font-size:0.85rem; padding:7px;">
        </div>
        <div class="span2">
          <label style="margin:0 0 4px 0; font-size:0.82rem;">Fins a</label>
          <input type="date" name="f_data_fins" value="<?= htmlspecialchars($filtre_data_fins) ?>" style="font-size:0.85rem; padding:7px;">
        </div>
        <div class="span2" style="display:flex; align-items:flex-end; gap:6px;">
          <button class="btn btn-small" type="submit" style="padding:7px 14px;">Filtrar</button>
          <a href="tractaments.php" class="btn btn-small secondary" style="padding:7px 10px;">✕</a>
        </div>
      </div>
    </form>

    <!-- Botó exportar PDF -->
    <div style="display:flex; justify-content:flex-end; margin-bottom:10px;">
      <button type="button" id="btn_exportar_pdf" class="btn btn-small" style="background:rgba(37,99,235,0.10); color:#1e40af; border-color:rgba(37,99,235,0.25);"
              onclick="exportarQuadernPDF()">
        Exportar quadern PDF
      </button>
    </div>

    <!-- Taula de tractaments -->
    <?php if (!$tractaments): ?>
      <div style="text-align:center; padding:30px;">
        <p class="small">Encara no hi ha tractaments registrats<?= ($filtre_parcela || $filtre_producte || $filtre_data_des || $filtre_data_fins) ? ' amb els filtres seleccionats' : '' ?>.</p>
      </div>
    <?php else: ?>
      <div style="overflow-x:auto;">
        <table class="table" id="taula_tractaments">
          <thead>
            <tr>
              <th>Data</th>
              <th>Producte</th>
              <th>Ubicació</th>
              <th>Dosi</th>
              <th>Operari</th>
              <th>Mètode</th>
              <th style="text-align:right">Accions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tractaments as $t): ?>
              <tr style="<?= ($edit_item && $edit_item['id'] == $t['id']) ? 'background: #f0f7ff;' : '' ?>"
                  data-data="<?= htmlspecialchars($t['aplicat']) ?>"
                  data-parcela="<?= htmlspecialchars($t['parcela_name'] ?? '-') ?>"
                  data-sector="<?= htmlspecialchars($t['sector_name'] ?? '-') ?>"
                  data-cultiu="<?= htmlspecialchars($t['cultiu_nom'] ?? '-') ?>"
                  data-producte="<?= htmlspecialchars($t['producte_name'] ?? '-') ?>"
                  data-dosi="<?= htmlspecialchars($t['dosis_hectarea'] ?? '') ?> <?= htmlspecialchars($t['unitat'] ?? $t['producte_unitat'] ?? '') ?>/ha"
                  data-operari="<?= htmlspecialchars($t['operari_name'] ?? '-') ?>"
                  data-metode="<?= htmlspecialchars($t['metode'] ?? '-') ?>"
                  data-observacions="<?= htmlspecialchars($t['notes'] ?? '-') ?>">
                <td style="white-space:nowrap">
                  <strong><?= htmlspecialchars($t['aplicat']) ?></strong>
                  <?php if (!empty($t['hora'])): ?>
                    <br><span class="small"><?= htmlspecialchars(substr($t['hora'], 0, 5)) ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <strong><?= htmlspecialchars($t['producte_name'] ?? '-') ?></strong>
                  <?php if (!empty($t['substancia_activa'])): ?>
                    <br><span class="small" style="color:#6b7280;"><?= htmlspecialchars($t['substancia_activa']) ?></span>
                  <?php endif; ?>
                </td>
                <td class="small">
                  <?= htmlspecialchars($t['parcela_name'] ?? '-') ?>
                  <?php if (!empty($t['sector_name'])): ?>
                    <br><span style="color:#6b7280;">↳ <?= htmlspecialchars($t['sector_name']) ?></span>
                  <?php endif; ?>
                  <?php if (!empty($t['cultiu_nom'])): ?>
                    <br><span style="color:#16a34a; font-weight:600;"><?= htmlspecialchars($t['cultiu_nom']) ?></span>
                  <?php endif; ?>
                </td>
                <td style="white-space:nowrap">
                  <?= htmlspecialchars($t['dosis_hectarea']) ?> <?= htmlspecialchars($t['unitat'] ?? '') ?>/ha
                  <br><span class="small">Total: <?= htmlspecialchars($t['dosis_total']) ?></span>
                </td>
                <td class="small"><?= htmlspecialchars($t['operari_name'] ?? '-') ?></td>
                <td class="small"><?= htmlspecialchars($t['metode'] ?? '-') ?></td>
                <td style="text-align:right; white-space:nowrap">
                  <a href="tractaments.php?id=<?= $t['id'] ?>" class="btn btn-small" title="Editar">
                    Editar
                  </a>
                  <a href="tractaments.php?delete=<?= $t['id'] ?>"
                     class="btn btn-small btn-red"
                     onclick="return confirm('Segur que vols eliminar aquest tractament?')"
                     title="Eliminar">
                    Eliminar
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="text-align:right; margin-top:8px;">
        <span class="small" style="color:#6b7280;">Total: <strong><?= count($tractaments) ?></strong> tractaments</span>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- ===== jsPDF per exportació de quadern ===== -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.2/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>

<script>
/* ===== Filtre dinàmic de sectors segons parcel·la ===== */
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
    // Reiniciem el sector si no estem editant
    if (!document.querySelector('input[name="action"][value="edit_tractament"]')) {
        sectorSelect.value = "";
    }
    calcularDosiTotal();
});

/* ===== Filtre dinàmic de files segons sector ===== */
document.getElementById('select_sector').addEventListener('change', function() {
    const sectorId = this.value;
    const filaSelect = document.getElementById('select_fila');
    filaSelect.querySelectorAll('option').forEach(opt => {
        if (opt.value === "") { opt.style.display = "block"; return; }
        opt.style.display = (sectorId === "" || opt.getAttribute('data-sector') === sectorId) ? "block" : "none";
    });
    if (!document.querySelector('input[name="action"][value="edit_tractament"]')) {
        filaSelect.value = "";
    }
    calcularDosiTotal();
});

/* ===== Informació del producte seleccionat ===== */
document.getElementById('select_producte').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const infoBox = document.getElementById('producte_info');
    const unitatField = document.getElementById('field_unitat');

    if (this.value === '') {
        infoBox.style.display = 'none';
        return;
    }

    const substancia = opt.getAttribute('data-substancia') || '—';
    const tipus = opt.getAttribute('data-tipus') || 'fitosanitari';
    const dosiMax = parseFloat(opt.getAttribute('data-dosi-max')) || 0;
    const unitat = opt.getAttribute('data-unitat') || '';

    document.getElementById('info_substancia').textContent = substancia || '—';
    document.getElementById('info_tipus').textContent = tipus.charAt(0).toUpperCase() + tipus.slice(1);

    if (dosiMax > 0) {
        document.getElementById('info_dosi_max').textContent = dosiMax + ' ' + unitat + '/ha';
        document.getElementById('info_dosi_max_row').style.display = 'block';
    } else {
        document.getElementById('info_dosi_max_row').style.display = 'none';
    }

    infoBox.style.display = 'block';

    // Auto-omplir la unitat si està buida
    if (unitatField.value === '' && unitat) {
        unitatField.value = unitat;
    }

    validarDosiMaxima();
});

/* ===== Càlcul automàtic de dosi total ===== */
function calcularDosiTotal() {
    const dosiHa = parseFloat(document.getElementById('field_dosi_ha').value) || 0;
    const sectorSelect = document.getElementById('select_sector');
    const parcelaSelect = document.getElementById('select_parcela');
    const resultatDiv = document.getElementById('calcul_resultat');

    // Obtenim la superfície: prioritat sector > parcel·la
    let superficie = 0;
    let fontSuperficie = '';

    if (sectorSelect.value !== '') {
        const opt = sectorSelect.options[sectorSelect.selectedIndex];
        superficie = parseFloat(opt.getAttribute('data-area')) || 0;
        fontSuperficie = 'sector';
    } else if (parcelaSelect.value !== '') {
        const opt = parcelaSelect.options[parcelaSelect.selectedIndex];
        superficie = parseFloat(opt.getAttribute('data-area')) || 0;
        fontSuperficie = 'parcel·la';
    }

    if (dosiHa > 0 && superficie > 0) {
        const total = (dosiHa * superficie).toFixed(2);
        document.getElementById('field_dosi_total').value = total;
        document.getElementById('calcul_text').textContent =
            dosiHa + ' × ' + superficie + ' ha (' + fontSuperficie + ') = ' + total + ' (quantitat total)';
        resultatDiv.style.display = 'block';
    } else {
        resultatDiv.style.display = 'none';
    }

    validarDosiMaxima();
}

/* ===== Validació de dosi màxima ===== */
function validarDosiMaxima() {
    const dosiHa = parseFloat(document.getElementById('field_dosi_ha').value) || 0;
    const producteSelect = document.getElementById('select_producte');
    const avisDiv = document.getElementById('aviso_dosi');

    if (producteSelect.value === '') {
        avisDiv.style.display = 'none';
        return;
    }

    const opt = producteSelect.options[producteSelect.selectedIndex];
    const dosiMax = parseFloat(opt.getAttribute('data-dosi-max')) || 0;

    if (dosiMax > 0 && dosiHa > dosiMax) {
        avisDiv.style.display = 'block';
    } else {
        avisDiv.style.display = 'none';
    }
}

// Escoltem canvis per recalcular automàticament
document.getElementById('field_dosi_ha').addEventListener('input', calcularDosiTotal);
document.getElementById('select_sector').addEventListener('change', calcularDosiTotal);
document.getElementById('select_parcela').addEventListener('change', calcularDosiTotal);

// Disparar l'event de producte al carregar si hi ha un producte seleccionat (mode edició)
window.addEventListener('DOMContentLoaded', function() {
    const prodSelect = document.getElementById('select_producte');
    if (prodSelect.value !== '') {
        prodSelect.dispatchEvent(new Event('change'));
    }
    // Calcular dosi total si ja tenim dades
    calcularDosiTotal();
});

/* ===== Exportar quadern d'explotació en PDF ===== */
function exportarQuadernPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

    // Recollim les dades de la taula visible
    const files = document.querySelectorAll('#taula_tractaments tbody tr');
    if (files.length === 0) {
        alert('No hi ha tractaments per exportar. Aplica filtres o afegeix tractaments.');
        return;
    }

    const dades = [];
    files.forEach(tr => {
        dades.push([
            tr.getAttribute('data-data')         || '',
            tr.getAttribute('data-parcela')      || '',
            tr.getAttribute('data-sector')       || '',
            tr.getAttribute('data-cultiu')       || '',
            tr.getAttribute('data-producte')     || '',
            tr.getAttribute('data-dosi')         || '',
            tr.getAttribute('data-operari')      || '',
            tr.getAttribute('data-metode')       || '',
            tr.getAttribute('data-observacions') || ''
        ]);
    });

    // Capçalera del document
    doc.setFontSize(18);
    doc.setFont(undefined, 'bold');
    doc.text('QUADERN D\'EXPLOTACIÓ - AGRISOFT', 14, 18);

    doc.setFontSize(10);
    doc.setFont(undefined, 'normal');

    // Rang de dates dels filtres
    const dataDesEl = document.querySelector('input[name="f_data_des"]');
    const dataFinsEl = document.querySelector('input[name="f_data_fins"]');
    let subtitol = 'Registre de tractaments fitosanitaris i fertilització';
    if (dataDesEl && dataDesEl.value && dataFinsEl && dataFinsEl.value) {
        subtitol += '  |  Període: ' + dataDesEl.value + ' a ' + dataFinsEl.value;
    } else if (dataDesEl && dataDesEl.value) {
        subtitol += '  |  Des de: ' + dataDesEl.value;
    } else if (dataFinsEl && dataFinsEl.value) {
        subtitol += '  |  Fins: ' + dataFinsEl.value;
    }
    doc.text(subtitol, 14, 25);

    const avui = new Date().toLocaleDateString('ca-ES', { year:'numeric', month:'2-digit', day:'2-digit' });
    doc.text('Generat: ' + avui + '  |  Total tractaments: ' + dades.length, 14, 31);

    // Línia separadora
    doc.setDrawColor(22, 163, 74);
    doc.setLineWidth(0.5);
    doc.line(14, 34, 283, 34);

    // Taula amb autoTable
    doc.autoTable({
        startY: 38,
        head: [['Data', 'Parcel·la', 'Sector', 'Cultiu', 'Producte', 'Dosi', 'Operari', 'Mètode', 'Observacions']],
        body: dades,
        styles: {
            fontSize: 8,
            cellPadding: 3,
            lineColor: [200, 200, 200],
            lineWidth: 0.1
        },
        headStyles: {
            fillColor: [22, 163, 74],
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            fontSize: 8.5
        },
        alternateRowStyles: {
            fillColor: [245, 247, 251]
        },
        columnStyles: {
            0: { cellWidth: 22 },
            8: { cellWidth: 45 }
        },
        margin: { left: 14, right: 14 },
        didDrawPage: function(data) {
            // Peu de pàgina
            const pageHeight = doc.internal.pageSize.height;
            doc.setFontSize(7);
            doc.setTextColor(150);
            doc.text('AGRISOFT — Quadern d\'explotació digital', 14, pageHeight - 8);
            doc.text('Pàgina ' + doc.getCurrentPageInfo().pageNumber, 270, pageHeight - 8);
        }
    });

    // Descarregar
    const nomFitxer = 'quadern_explotacio_' + new Date().toISOString().slice(0,10) + '.pdf';
    doc.save(nomFitxer);
}
</script>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
