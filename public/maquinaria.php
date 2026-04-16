<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Eliminar màquina
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db()->prepare("DELETE FROM maquinaria WHERE idMaquina = ?")->execute([$id]);
    flash_set("Maquinària eliminada.", "ok");
    header("Location: maquinaria.php");
    exit;
}

// Crear o Editar màquina
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $id = (int)($_POST['id'] ?? 0);

  $nom = trim($_POST['nom'] ?? '');

  if ($nom === '') {
    flash_set("El nom és obligatori.", "bad");
  } else {
    if ($action === 'create') {
      $st = db()->prepare("
        INSERT INTO maquinaria (nom, tipus, matricula, tipusCombustible, cavalls)
        VALUES (?, ?, ?, ?, ?)
      ");

      $st->execute([
        $nom,
        trim($_POST['tipus'] ?? '') ?: null,
        trim($_POST['matricula'] ?? '') ?: null,
        trim($_POST['tipusCombustible'] ?? '') ?: null,
        ($_POST['cavalls'] ?? '') !== '' ? (int)$_POST['cavalls'] : null
      ]);
      flash_set("Maquinària creada correctament.", "ok");
    } elseif ($action === 'edit' && $id > 0) {
      $st = db()->prepare("
        UPDATE maquinaria SET nom=?, tipus=?, matricula=?, tipusCombustible=?, cavalls=?
        WHERE idMaquina=?
      ");
      $st->execute([
        $nom,
        trim($_POST['tipus'] ?? '') ?: null,
        trim($_POST['matricula'] ?? '') ?: null,
        trim($_POST['tipusCombustible'] ?? '') ?: null,
        ($_POST['cavalls'] ?? '') !== '' ? (int)$_POST['cavalls'] : null,
        $id
      ]);
      flash_set("Maquinària actualitzada.", "ok");
    }

    header("Location: maquinaria.php");
    exit;
  }
}

// Detectar edició
$edit_item = null;
if (isset($_GET['edit'])) {
    $st = db()->prepare("SELECT * FROM maquinaria WHERE idMaquina = ?");
    $st->execute([(int)$_GET['edit']]);
    $edit_item = $st->fetch(PDO::FETCH_ASSOC);
}

// Llistar maquinària
$maquinaria = db()->query("
  SELECT * FROM maquinaria
  ORDER BY idMaquina DESC
")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Maquinària · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <div class="card span6">
    <h2><?= $edit_item ? 'Editar màquina' : 'Nova màquina' ?></h2>

    <form method="post">
      <input type="hidden" name="action" value="<?= $edit_item ? 'edit' : 'create' ?>">
      <?php if ($edit_item): ?>
        <input type="hidden" name="id" value="<?= (int)$edit_item['idMaquina'] ?>">
      <?php endif; ?>

      <label>Nom *</label>
      <input name="nom" required placeholder="Ex: Tractor John Deere" value="<?= $edit_item ? h($edit_item['nom']) : '' ?>">

      <label>Tipus</label>
      <input name="tipus" placeholder="Ex: Tractor, Segadora, Polvoritzador" value="<?= $edit_item ? h($edit_item['tipus'] ?? '') : '' ?>">

      <label>Matrícula</label>
      <input name="matricula" value="<?= $edit_item ? h($edit_item['matricula'] ?? '') : '' ?>">

      <label>Tipus de combustible</label>
      <input name="tipusCombustible" placeholder="Ex: Dièsel, Gasolina, Elèctric" value="<?= $edit_item ? h($edit_item['tipusCombustible'] ?? '') : '' ?>">

      <label>Cavalls (CV)</label>
      <input type="number" name="cavalls" min="0" value="<?= $edit_item ? (int)$edit_item['cavalls'] : '' ?>">

      <button class="btn" type="submit"><?= $edit_item ? 'Actualitzar' : 'Desar' ?></button>
      <?php if ($edit_item): ?>
        <a href="maquinaria.php" class="btn secondary" style="margin-left:8px">Cancel·lar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="card span6">
    <h2>Maquinària</h2>

    <?php if (!$maquinaria): ?>
      <p class="small">Encara no hi ha maquinària registrada.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Tipus</th>
            <th>Matrícula</th>
             <th>Combustible</th>
             <th>CV</th>
             <th>Accions</th>
           </tr>
         </thead>
         <tbody>
           <?php foreach ($maquinaria as $m): ?>
             <tr>
               <td><?= (int)$m['idMaquina'] ?></td>
               <td><?= h($m['nom']) ?></td>
               <td><?= h($m['tipus'] ?? '') ?></td>
               <td><?= h($m['matricula'] ?? '') ?></td>
               <td><?= h($m['tipusCombustible'] ?? '') ?></td>
               <td><?= h($m['cavalls'] ?? '') ?></td>
               <td style="white-space:nowrap">
                 <a href="maquinaria.php?edit=<?= $m['idMaquina'] ?>" class="btn btn-small">✏️</a>
                 <a href="maquinaria.php?delete=<?= $m['idMaquina'] ?>" class="btn btn-small" onclick="return confirm('Segur?')">🗑️</a>
               </td>
             </tr>
           <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
