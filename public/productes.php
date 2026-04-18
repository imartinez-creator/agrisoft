<?php
/* ===== Càrrega de fitxers necessaris ===== */
require_once __DIR__ . '/../app/config/db.php';       // Connexió a la base de dades
require_once __DIR__ . '/../app/middleware/auth.php';  // Control d'accés
require_once __DIR__ . '/../app/helpers/flash.php';    // Missatges flash

// Comprova que l'usuari hagi iniciat sessió
require_login();


/* ===== Eliminar un producte ===== */
if (isset($_GET['delete'])) {
    if (!can_manage()) die("Error: No tens permisos per eliminar productes.");
    $id = (int)$_GET['delete'];
    db()->prepare("DELETE FROM fito_productes WHERE id = ?")->execute([$id]);
    flash_set("Producte eliminat correctament.", "ok");
    header("Location: productes.php");
    exit;
}

/* ===== Gastar/Consumir Estoc (Treballadors inclosos) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'consume_producte') {
    $id = (int)$_POST['id'];
    $amount = (float)$_POST['amount'];
    if ($amount > 0) {
        $st = db()->prepare("UPDATE fito_productes SET stock = GREATEST(0, stock - ?) WHERE id = ?");
        $st->execute([$amount, $id]);
        flash_set("S'han restat $amount del producte correctament.", "ok");
    } else {
        flash_set("La quantitat a restar a de ser superior a 0.", "bad");
    }
    header("Location: productes.php");
    exit;
}

/* ===== Crear o Editar un producte fitosanitari (formulari POST) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(($_POST['action'] ?? ''), ['create_producte', 'edit_producte'])) {
    if (!can_manage()) die("Error: No tens permisos per modificar productes.");
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    // Recollim les dades del formulari
    $name = trim($_POST['name']);                              // Nom del producte (obligatori)
    $substancia = trim($_POST['substancia_activa'] ?? '');     // Substància activa
    $unitat = $_POST['unitat'] ?? 'l';                         // Unitat de mesura (l, kg, u)
    $stock = (float)($_POST['stock'] ?? 0);                    // Stock actual
    $stock_baix = (float)($_POST['stock_baix'] ?? 5);          // Llindar de stock baix
    $expiry = $_POST['expiry_date'] !== '' ? $_POST['expiry_date'] : null;  // Data de caducitat

    if ($name !== '') {
        if ($action === 'create_producte') {
            // Inserim un nou producte
            $st = db()->prepare("
                INSERT INTO fito_productes (name, substancia_activa, unitat, stock, stock_baix, expiry_date)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $st->execute([$name, $substancia, $unitat, $stock, $stock_baix, $expiry]);
            flash_set("Producte creat correctament.", "ok");
        } elseif ($action === 'edit_producte') {
            // Actualitzem un producte existent
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


/* ===== Carregar dades per editar un producte ===== */
$edit_item = null;
if (isset($_GET['edit'])) {
    $st = db()->prepare("SELECT * FROM fito_productes WHERE id = ?");
    $st->execute([(int)$_GET['edit']]);
    $edit_item = $st->fetch(PDO::FETCH_ASSOC);
}


/* ===== Obtenir tots els productes ===== */
$productes = db()->query("SELECT * FROM fito_productes ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

/* ===== Títol de la pàgina i capçalera HTML ===== */
$titol = "Productes fitosanitaris · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <!-- ===== Formulari per crear o editar un producte ===== -->
  <?php if (can_manage()): ?>
  <div class="card span4">
    <h2><?= $edit_item ? "Editar producte" : "Nou producte" ?></h2>
    <form method="post">
      <input type="hidden" name="action" value="<?= $edit_item ? 'edit_producte' : 'create_producte' ?>">
      <?php if ($edit_item): ?>
          <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
      <?php endif; ?>

      <!-- Nom del producte -->
      <label>Nom</label>
      <input name="name" value="<?= $edit_item ? htmlspecialchars($edit_item['name']) : '' ?>" required autofocus>

      <!-- Substància activa -->
      <label>Substància activa</label>
      <input name="substancia_activa" value="<?= $edit_item ? htmlspecialchars($edit_item['substancia_activa']) : '' ?>">

      <!-- Unitat de mesura -->
      <label>Unitat</label>
      <select name="unitat">
        <option value="l" <?= ($edit_item && $edit_item['unitat'] == 'l') ? 'selected' : '' ?>>Litres</option>
        <option value="kg" <?= ($edit_item && $edit_item['unitat'] == 'kg') ? 'selected' : '' ?>>Kg</option>
        <option value="u" <?= ($edit_item && $edit_item['unitat'] == 'u') ? 'selected' : '' ?>>Unitats</option>
      </select>

      <!-- Stock actual -->
      <label>Stock</label>
      <input name="stock" type="number" step="0.01" value="<?= $edit_item ? (float)$edit_item['stock'] : '0' ?>">

      <!-- Llindar d'alerta de stock baix -->
      <label>Stock baix</label>
      <input name="stock_baix" type="number" step="0.01" value="<?= $edit_item ? (float)$edit_item['stock_baix'] : '5' ?>">

      <!-- Data de caducitat -->
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
  <?php endif; ?>

  <!-- ===== Taula amb tots els productes ===== -->
  <div class="card <?= can_manage() ? 'span8' : 'span12' ?>">
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
            <th style="text-align:right">Consumició / Accions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($productes as $p): ?>
            <!-- Ressaltem la fila si s'està editant -->
            <tr style="<?= ($edit_item && $edit_item['id'] == $p['id']) ? 'background: #f0f7ff;' : '' ?>">
              <td><?= (int)$p['id'] ?></td>
              <td>
                <strong><?= htmlspecialchars($p['name']) ?></strong><br>
                <!-- Substància activa en petit -->
                <small style="color: #666;"><?= htmlspecialchars($p['substancia_activa']) ?></small>
              </td>
              <td><?= htmlspecialchars($p['stock']) ?> <?= htmlspecialchars($p['unitat']) ?></td>
              <td><?= $p['expiry_date'] ? date('d/m/Y', strtotime($p['expiry_date'])) : '-' ?></td>
              <td style="text-align:right; white-space: nowrap;">
                
                <!-- Formulari Gastar Producte (Access per a tots) -->
                <form method="post" style="display:inline; margin-right: 15px;">
                    <input type="hidden" name="action" value="consume_producte">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <input type="number" step="0.01" name="amount" required style="width: 70px; padding: 4px; font-size:12px; margin:0;" placeholder="Quant.">
                    <button class="btn btn-small" type="submit" style="padding: 4px 8px; font-size:12px; margin-left:2px; vertical-align: top;">Gastar</button>
                </form>

                <?php if (can_manage()): ?>
                <!-- Botó editar -->
                <a href="productes.php?edit=<?= $p['id'] ?>" class="btn btn-small">
                  <span class="icon">✏️</span>
                </a>
                <!-- Botó eliminar -->
                <a href="productes.php?delete=<?= $p['id'] ?>" 
                   class="btn btn-small btn-red" 
                   onclick="return confirm('Segur que vols eliminar aquest producte?')">
                  <span class="icon">🗑️</span>
                </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>