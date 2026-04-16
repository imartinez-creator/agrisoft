<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

// Eliminar treballador
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        db()->prepare("DELETE FROM treballadors WHERE id = ?")->execute([$id]);
        flash_set("Treballador eliminat.", "ok");
    } catch (Exception $e) {
        flash_set("No s'ha pogut eliminar (podria tenir dades vinculades).", "bad");
    }
    header("Location: personal.php");
    exit;
}

// Crear o Editar treballador
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $id = (int)($_POST['id'] ?? 0);
  $nom = trim($_POST['nom_complet'] ?? '');

  if ($nom === '') {
    flash_set("El nom és obligatori.", "bad");
  } else {
    if ($action === 'create') {
      $st = db()->prepare("
        INSERT INTO treballadors (nom_complet, telefon, rol_de_treball, cost_hora)
        VALUES (?, ?, ?, ?)
      ");
      $st->execute([
        $nom,
        trim($_POST['telefon'] ?? ''),
        trim($_POST['rol_de_treball'] ?? ''),
        $_POST['cost_hora'] !== '' ? $_POST['cost_hora'] : null
      ]);
      flash_set("Treballador creat correctament.", "ok");
    } elseif ($action === 'edit' && $id > 0) {
      $st = db()->prepare("
        UPDATE treballadors SET nom_complet=?, telefon=?, rol_de_treball=?, cost_hora=?
        WHERE id=?
      ");
      $st->execute([
        $nom,
        trim($_POST['telefon'] ?? ''),
        trim($_POST['rol_de_treball'] ?? ''),
        $_POST['cost_hora'] !== '' ? $_POST['cost_hora'] : null,
        $id
      ]);
      flash_set("Treballador actualitzat.", "ok");
    }
    header("Location: personal.php");
    exit;
  }
}

// Detectar edició
$edit_item = null;
if (isset($_GET['edit'])) {
    $st = db()->prepare("SELECT * FROM treballadors WHERE id = ?");
    $st->execute([(int)$_GET['edit']]);
    $edit_item = $st->fetch(PDO::FETCH_ASSOC);
}

// Llistar treballadors
$treballadors = db()->query("SELECT * FROM treballadors ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Personal · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <div class="card span6">
    <h2><?= $edit_item ? 'Editar treballador' : 'Nou treballador' ?></h2>

    <form method="post">
      <input type="hidden" name="action" value="<?= $edit_item ? 'edit' : 'create' ?>">
      <?php if ($edit_item): ?>
        <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
      <?php endif; ?>

      <label>Nom complet</label>
      <input name="nom_complet" required value="<?= $edit_item ? htmlspecialchars($edit_item['nom_complet']) : '' ?>">

      <label>Telèfon</label>
      <input name="telefon" value="<?= $edit_item ? htmlspecialchars($edit_item['telefon'] ?? '') : '' ?>">

      <label>Rol de treball</label>
      <input name="rol_de_treball" placeholder="Ex: Podador, Tractorista..." value="<?= $edit_item ? htmlspecialchars($edit_item['rol_de_treball'] ?? '') : '' ?>">

      <label>Cost per hora (€)</label>
      <input type="number" step="0.01" name="cost_hora" value="<?= $edit_item ? $edit_item['cost_hora'] : '' ?>">

      <button class="btn" type="submit"><?= $edit_item ? 'Actualitzar' : 'Desar' ?></button>
      <?php if ($edit_item): ?>
        <a href="personal.php" class="btn secondary" style="margin-left:8px">Cancel·lar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="card span6">
    <h2>Treballadors</h2>

    <?php if (!$treballadors): ?>
      <p class="small">Encara no hi ha treballadors registrats.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Telèfon</th>
             <th>Rol</th>
             <th>Cost/h</th>
             <th>Accions</th>
           </tr>
         </thead>
         <tbody>
           <?php foreach ($treballadors as $t): ?>
             <tr>
               <td><?= (int)$t['id'] ?></td>
               <td><?= htmlspecialchars($t['nom_complet']) ?></td>
               <td><?= htmlspecialchars($t['telefon'] ?? '') ?></td>
               <td><?= htmlspecialchars($t['rol_de_treball'] ?? '') ?></td>
               <td><?= htmlspecialchars($t['cost_hora'] ?? '0') ?> €/h</td>
               <td style="white-space:nowrap">
                 <a href="personal.php?edit=<?= $t['id'] ?>" class="btn btn-small">✏️</a>
                 <a href="personal.php?delete=<?= $t['id'] ?>" class="btn btn-small" onclick="return confirm('Segur?')">🗑️</a>
               </td>
             </tr>
           <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
