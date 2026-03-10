<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

// --- LÒGICA PER ELIMINAR ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    db()->prepare("DELETE FROM fito_productes WHERE id = ?")->execute([$id]);
    flash_set("Producte eliminat correctament.", "ok");
    header("Location: productes.php");
    exit;
}

// --- LÒGICA PER CREAR O EDITAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    $name = trim($_POST['name']);
    $substancia = trim($_POST['substancia_activa'] ?? '');
    $unitat = $_POST['unitat'] ?? 'l';
    $stock = (float)($_POST['stock'] ?? 0);
    $stock_baix = (float)($_POST['stock_baix'] ?? 5);
    $expiry = $_POST['expiry_date'] !== '' ? $_POST['expiry_date'] : null;

    if ($name !== '') {
        if ($action === 'create_producte') {
            $st = db()->prepare("
                INSERT INTO fito_productes (name, substancia_activa, unitat, stock, stock_baix, expiry_date)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $st->execute([$name, $substancia, $unitat, $stock, $stock_baix, $expiry]);
            flash_set("Producte creat correctament.", "ok");
        } elseif ($action === 'edit_producte') {
            $st = db()->prepare("
                UPDATE fito_productes 
                SET name = ?, substancia_activa = ?, unitat = ?, stock = ?, stock_baix = ?, expiry_date = ?
                WHERE id = ?
            ");
            $st->execute([$name, $substancia, $unitat, $stock, $stock_baix, $expiry, $id]);
            flash_set("Producte actualitzat correctament.", "ok");
        }
        header("Location: productes.php");
        exit;
    } else {
        flash_set("El nom és obligatori.", "bad");
    }
}

// --- DETECTAR SI ESTEM EDITANT ---
$edit_item = null;
if (isset($_GET['edit'])) {
    $st = db()->prepare("SELECT * FROM fito_productes WHERE id = ?");
    $st->execute([(int)$_GET['edit']]);
    $edit_item = $st->fetch(PDO::FETCH_ASSOC);
}

// Llistar productes
$productes = db()->query("SELECT * FROM fito_productes ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Productes fitosanitaris · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <div class="card span4">
    <h2><?= $edit_item ? "Editar producte" : "Nou producte" ?></h2>
    <form method="post">
      <input type="hidden" name="action" value="<?= $edit_item ? 'edit_producte' : 'create_producte' ?>">
      <?php if ($edit_item): ?>
          <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
      <?php endif; ?>

      <label>Nom</label>
      <input name="name" value="<?= $edit_item ? htmlspecialchars($edit_item['name']) : '' ?>" required autofocus>

      <label>Substància activa</label>
      <input name="substancia_activa" value="<?= $edit_item ? htmlspecialchars($edit_item['substancia_activa']) : '' ?>">

      <label>Unitat</label>
      <select name="unitat">
        <option value="l" <?= ($edit_item && $edit_item['unitat'] == 'l') ? 'selected' : '' ?>>Litres</option>
        <option value="kg" <?= ($edit_item && $edit_item['unitat'] == 'kg') ? 'selected' : '' ?>>Kg</option>
        <option value="u" <?= ($edit_item && $edit_item['unitat'] == 'u') ? 'selected' : '' ?>>Unitats</option>
      </select>

      <label>Stock</label>
      <input name="stock" type="number" step="0.01" value="<?= $edit_item ? (float)$edit_item['stock'] : '0' ?>">

      <label>Stock baix</label>
      <input name="stock_baix" type="number" step="0.01" value="<?= $edit_item ? (float)$edit_item['stock_baix'] : '5' ?>">

      <label>Data de caducitat</label>
      <input name="expiry_date" type="date" value="<?= $edit_item ? $edit_item['expiry_date'] : '' ?>">

      <div style="margin-top: 20px;">
          <button class="btn" type="submit"><?= $edit_item ? "Actualitzar" : "Crear" ?></button>
          <?php if ($edit_item): ?>
              <a href="productes.php" class="btn" style="background:#eee; color:#333;">Cancel·lar</a>
          <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="card span8">
    <h2>Productes</h2>

    <?php if (!$productes): ?>
      <p class="small">Encara no hi ha productes creats.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Stock</th>
            <th>Caduca</th>
            <th style="text-align:right">Accions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($productes as $p): ?>
            <tr style="<?= ($edit_item && $edit_item['id'] == $p['id']) ? 'background: #f0f7ff;' : '' ?>">
              <td><?= (int)$p['id'] ?></td>
              <td>
                <strong><?= htmlspecialchars($p['name']) ?></strong><br>
                <small style="color: #666;"><?= htmlspecialchars($p['substancia_activa']) ?></small>
              </td>
              <td><?= htmlspecialchars($p['stock']) ?> <?= htmlspecialchars($p['unitat']) ?></td>
              <td><?= $p['expiry_date'] ? date('d/m/Y', strtotime($p['expiry_date'])) : '-' ?></td>
              <td style="text-align:right; white-space: nowrap;">
                <a href="productes.php?edit=<?= $p['id'] ?>" class="btn btn-small">
                  <span class="icon">✏️</span>
                </a>
                <a href="productes.php?delete=<?= $p['id'] ?>" 
                   class="btn btn-small btn-red" 
                   onclick="return confirm('Segur que vols eliminar aquest producte?')">
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