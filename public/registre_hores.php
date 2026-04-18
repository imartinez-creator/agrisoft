<?php
/* ===== Càrrega de fitxers necessaris ===== */
require_once __DIR__ . '/../app/config/db.php';       // Connexió a la base de dades
require_once __DIR__ . '/../app/middleware/auth.php';  // Control d'accés

// Comprova que l'usuari hagi iniciat sessió
require_login();

// Data d'avui (per filtrar els torns del dia)
$avui = date('Y-m-d');

/* ===== Funció: Obtenir l'últim torn actiu d'avui ===== */
// Busca si un treballador té un torn 'treballant' o 'pausat' avui
function get_active_shift_today(int $idTreballador, string $avui) {
  $st = db()->prepare("
    SELECT *
    FROM registre_hores
    WHERE idTreballador = ?
      AND data = ?
      AND estat IN ('treballant','pausat')
    ORDER BY id_registre DESC
    LIMIT 1
  ");
  $st->execute([$idTreballador, $avui]);
  return $st->fetch(PDO::FETCH_ASSOC);
}

/* ===== Processar accions del formulari (iniciar/pausar/reprendre/acabar) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $idTreballador = (int)($_POST['idTreballador'] ?? 0);  // ID del treballador
  $acc = $_POST['acc'] ?? '';                              // Acció a fer

  if ($idTreballador > 0) {

    // ===== SEGURETAT (RBAC BACKEND) =====
    // Un treballador no pot enviar un POST modificant el idTreballador extern
    $my_role = $_SESSION['user']['role'] ?? '';
    $my_user_id = $_SESSION['user']['id'] ?? 0;
    
    if ($my_role === 'treballador') {
        $st_rbac = db()->prepare("SELECT id FROM treballadors WHERE id = ? AND user_id = ?");
        $st_rbac->execute([$idTreballador, $my_user_id]);
        if (!$st_rbac->fetch()) {
            die("Error de Seguretat Crític: No tens permisos per alterar els registres d'hores d'altres companys.");
        }
    }

    // INICIAR: crear un nou torn si no n'hi ha cap actiu
    if ($acc === 'inici') {
      $actiu = get_active_shift_today($idTreballador, $avui);
      if (!$actiu) {
        db()->prepare("
          INSERT INTO registre_hores (idTreballador, data, hora_inici, estat, pauses)
          VALUES (?, ?, NOW(), 'treballant', 0)
        ")->execute([$idTreballador, $avui]);
      }
    }

    // PAUSAR: canviar l'estat de 'treballant' a 'pausat'
    if ($acc === 'pausa') {
      $actiu = get_active_shift_today($idTreballador, $avui);
      if ($actiu && $actiu['estat'] === 'treballant') {
        db()->prepare("
          UPDATE registre_hores
          SET estat='pausat'
          WHERE id_registre=?
        ")->execute([(int)$actiu['id_registre']]);
      }
    }

    // REPRENDRE: tornar de pausa a treballant (incrementem el comptador de pauses)
    if ($acc === 'repren') {
      $actiu = get_active_shift_today($idTreballador, $avui);
      if ($actiu && $actiu['estat'] === 'pausat') {
        db()->prepare("
          UPDATE registre_hores
          SET estat='treballant',
              pauses = pauses + 1
          WHERE id_registre=?
        ")->execute([(int)$actiu['id_registre']]);
      }
    }

    // ACABAR: marcar el torn com a finalitzat i registrar l'hora de fi
    if ($acc === 'final') {
      $actiu = get_active_shift_today($idTreballador, $avui);
      if ($actiu) {
        db()->prepare("
          UPDATE registre_hores
          SET hora_fi = NOW(),
              estat='finalitzat'
          WHERE id_registre=?
        ")->execute([(int)$actiu['id_registre']]);
      }
    }
  }

  header("Location: registre_hores.php");
  exit;
}

/* ===== Obtenir la llista de treballadors amb el seu torn d'avui ===== */
// Filtre inicial per Rol RBAC (Frontend restriction)
$my_role = $_SESSION['user']['role'] ?? 'treballador';
$my_user_id = $_SESSION['user']['id'] ?? 0;

$where_clause = "";
$params = [];

// Els administradors o mànagers ho veuran tot intacte. Els treballadors només a si mateixos.
if ($my_role === 'treballador') {
    $where_clause = "WHERE t.user_id = ?";
    $params[] = $my_user_id;
}

$sql_rows = "
  SELECT
    t.id,
    t.nom_complet,
    r.id_registre,
    r.estat,
    r.hora_inici,
    r.hora_fi,
    r.pauses,
    DATE_FORMAT(r.hora_inici, '%H:%i') AS hora_inici_hm,
    DATE_FORMAT(r.hora_fi, '%H:%i') AS hora_fi_hm
  FROM treballadors t
  LEFT JOIN registre_hores r
    ON r.id_registre = (
      SELECT r2.id_registre
      FROM registre_hores r2
      WHERE r2.idTreballador = t.id
        AND r2.data = CURDATE()
      ORDER BY r2.id_registre DESC
      LIMIT 1
    )
  $where_clause
  ORDER BY t.nom_complet
";

$st_rows = db()->prepare($sql_rows);
$st_rows->execute($params);
$rows = $st_rows->fetchAll(PDO::FETCH_ASSOC);

/* ===== Títol de la pàgina i capçalera HTML ===== */
$titol = "Registre d'hores · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';

/* ===== Funció: Formatar segons en format HH:MM:SS ===== */
function fmt_hms(int $sec): string {
  if ($sec < 0) $sec = 0;
  $h = intdiv($sec, 3600);
  $m = intdiv($sec % 3600, 60);
  $s = $sec % 60;
  return sprintf("%02d:%02d:%02d", $h, $m, $s);
}

/* ===== Funció: Calcular segons treballats ===== */
// Si finalitzat: diferència entre fi i inici
// Si treballant: diferència entre ara i inici (comptador en viu)
// Si pausat: queda fix al carregar la pàgina
function worked_seconds(?string $hora_inici, ?string $hora_fi, string $estat): int {
  if (!$hora_inici) return 0;

  $start = strtotime($hora_inici);

  if ($estat === 'finalitzat' && $hora_fi) {
    $end = strtotime($hora_fi);
  } else {
    $end = time(); // Temps actual
  }

  return (int)max(0, $end - $start);
}
?>

<!-- ===== Taula del registre d'hores ===== -->
<div class="card">
  <h2>Registre d'hores (<?= htmlspecialchars($avui) ?>)</h2>

  <table class="table">
    <thead>
      <tr>
        <th>Treballador</th>
        <th>Inici</th>
        <th>Fi</th>
        <th>Temps</th>
        <th>Pauses</th>
        <th>Estat</th>
        <th>Accions</th>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($rows as $r): ?>
        <?php
          $estat = $r['estat'] ?? '—';
          $te_actiu = in_array($estat, ['treballant','pausat'], true);

          // Calculem els segons treballats per mostrar el temps
          $initSec = worked_seconds(
            $r['hora_inici'] ?? null,
            $r['hora_fi'] ?? null,
            $estat
          );

          // Timestamps per al comptador JavaScript en viu
          $startTs = !empty($r['hora_inici']) ? strtotime($r['hora_inici']) : null;
          $endTs   = !empty($r['hora_fi']) ? strtotime($r['hora_fi']) : null;
        ?>
        <!-- Cada fila porta data-attributes pel comptador JS -->
        <tr
          data-start-ts="<?= $startTs ?? '' ?>"
          data-end-ts="<?= $endTs ?? '' ?>"
          data-estat="<?= htmlspecialchars($estat) ?>"
        >
          <td><?= htmlspecialchars($r['nom_complet']) ?></td>
          <td><?= htmlspecialchars($r['hora_inici_hm'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['hora_fi_hm'] ?? '') ?></td>

          <!-- Comptador de temps (s'actualitza en viu si 'treballant') -->
          <td>
            <span class="work-timer"><?= htmlspecialchars(fmt_hms($initSec)) ?></span>
          </td>

          <td><?= (int)($r['pauses'] ?? 0) ?></td>
          <td><?= htmlspecialchars($estat) ?></td>

          <!-- Botons d'acció segons l'estat actual -->
          <td>
            <form method="post" style="display:flex;gap:6px;flex-wrap:wrap;">
              <input type="hidden" name="idTreballador" value="<?= (int)$r['id'] ?>">

              <?php if (!$te_actiu): ?>
                <!-- Si no té torn actiu, pot iniciar -->
                <button name="acc" value="inici" class="btn">Iniciar</button>

              <?php elseif ($estat === 'treballant'): ?>
                <!-- Si està treballant, pot pausar o acabar -->
                <button name="acc" value="pausa" class="btn secondary">Pausar</button>
                <button name="acc" value="final" class="btn">Acabar</button>

              <?php elseif ($estat === 'pausat'): ?>
                <!-- Si està pausat, pot reprendre o acabar -->
                <button name="acc" value="repren" class="btn">Reprendre</button>
                <button name="acc" value="final" class="btn secondary">Acabar</button>
              <?php endif; ?>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <p class="small" style="margin-top:10px;opacity:.85">
    El camp <b>Temps</b> és un comptador en viu quan l'estat és <b>treballant</b>. En <b>pausat</b> queda fix.
  </p>
</div>

<!-- JavaScript: comptador en viu per als treballadors actius -->
<script>
(function () {
  // Formata segons a HH:MM:SS
  function fmt(sec) {
    sec = Math.max(0, Math.floor(sec));
    const h = String(Math.floor(sec / 3600)).padStart(2, '0');
    const m = String(Math.floor((sec % 3600) / 60)).padStart(2, '0');
    const s = String(sec % 60).padStart(2, '0');
    return `${h}:${m}:${s}`;
  }

  // Calcula els segons treballats fins ara
  function computeWorked(row) {
    const startTs = parseInt(row.dataset.startTs || '0', 10);
    const endTs = parseInt(row.dataset.endTs || '0', 10);
    if (!startTs) return 0;

    const now = Math.floor(Date.now() / 1000);
    const end = endTs ? endTs : now;
    return Math.max(0, end - startTs);
  }

  // Actualitza el comptador cada segon
  function tick() {
    document.querySelectorAll('tr[data-start-ts]').forEach(row => {
      const timer = row.querySelector('.work-timer');
      if (!timer) return;

      const estat = row.dataset.estat || '';
      // Només actualitzem en viu si està treballant
      if (estat === 'treballant') {
        timer.textContent = fmt(computeWorked(row));
      }
    });
  }

  tick();
  setInterval(tick, 1000); // Cada segon
})();
</script>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
