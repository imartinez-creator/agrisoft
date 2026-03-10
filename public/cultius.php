<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

// --- LÒGICA PER ELIMINAR ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db()->prepare("DELETE FROM cultius WHERE id = ?")->execute([$id]);
    flash_set("Cultiu eliminat.", "ok");
    header("Location: cultius.php");
    exit;
}

// --- LÒGICA PER CREAR O EDITAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($name !== '') {
        if ($action === 'create_cultiu') {
            db()->prepare("INSERT INTO cultius (name) VALUES (?)")->execute([$name]);
            flash_set("Cultiu creat correctament.", "ok");
        } elseif ($action === 'edit_cultiu') {
            db()->prepare("UPDATE cultius SET name = ? WHERE id = ?")->execute([$name, $id]);
            flash_set("Cultiu actualitzat.", "ok");
        }
        header("Location: cultius.php");
        exit;
    } else {
        flash_set("El nom és obligatori.", "bad");
    }
}

// --- DETECTAR SI ESTEM EDITANT ---
$edit_item = null;
if (isset($_GET['id'])) {
    $st = db()->prepare("SELECT * FROM cultius WHERE id = ?");
    $st->execute([(int)$_GET['id']]);
    $edit_item = $st->fetch(PDO::FETCH_ASSOC);
}

// Llistar cultius
$cultius = db()->query("SELECT * FROM cultius ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Cultius · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <div class="card span6">
    <h2><?= $edit_item ? "Editar cultiu" : "Nou cultiu" ?></h2>
    
    <form method="post">
      <input type="hidden" name="action" value="<?= $edit_item ? 'edit_cultiu' : 'create_cultiu' ?>">
      <?php if ($edit_item): ?>
          <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
      <?php endif; ?>

      <label>Nom del cultiu</label>
      <input name="name" value="<?= $edit_item ? htmlspecialchars($edit_item['name']) : '' ?>" required autofocus>

      <button class="btn" type="submit"><?= $edit_item ? "Actualitzar" : "Crear" ?></button>
      
      <?php if ($edit_item): ?>
          <a href="cultius.php" class="btn" style="background:#eee; color:#333; margin-left:10px;">Cancel·lar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="card span6">
    <h2>Cultius</h2>

    <?php if (!$cultius): ?>
      <p class="small">Encara no hi ha cultius creats.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th style="text-align:right">Accions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cultius as $c): ?>
            <tr style="<?= ($edit_item && $edit_item['id'] == $c['id']) ? 'background: #f0f7ff;' : '' ?>">
              <td><?= (int)$c['id'] ?></td>
              <td><?= htmlspecialchars($c['name']) ?></td>
              <td style="text-align:right">
                <a href="cultius.php?id=<?= $c['id'] ?>" class="btn btn-small">
                  <span class="icon">✏️</span> Editar
                </a>
                <a href="cultius.php?delete=<?= $c['id'] ?>" 
                   class="btn btn-small btn-red" 
                   onclick="return confirm('Segur que vols eliminar aquest cultiu?')">
                  <span class="icon">🗑️</span>
                </a> 
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>