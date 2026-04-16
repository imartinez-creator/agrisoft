<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

// Només administradors
require_role(['admin']);

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// Accions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // Crear usuari
  if ($action === 'create') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role  = $_POST['role'] ?? 'manager';
    $pass  = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $pass === '') {
      flash_set('Nom, correu i contrasenya són obligatoris.', 'bad');
      header('Location: usuaris.php');
      exit;
    }
    if (!in_array($role, ['admin','manager','treballador'], true)) {
      $role = 'manager';
    }

    $st = db()->prepare('SELECT id FROM usuaris WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    if ($st->fetch()) {
      flash_set('Aquest correu ja està registrat.', 'bad');
      header('Location: usuaris.php');
      exit;
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $st = db()->prepare('INSERT INTO usuaris (name,email,contrasenya_enciptada,role) VALUES (?,?,?,?)');
    $st->execute([$name,$email,$hash,$role]);
    flash_set('Usuari creat correctament.', 'ok');
    header('Location: usuaris.php');
    exit;
  }

  // Actualitzar usuari
  if ($action === 'update') {
    $id    = (int)($_POST['id'] ?? 0);
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role  = $_POST['role'] ?? 'manager';
    $pass  = $_POST['password'] ?? '';

    if ($id <= 0 || $name === '' || $email === '') {
      flash_set('Dades incompletes.', 'bad');
      header('Location: usuaris.php');
      exit;
    }
    if (!in_array($role, ['admin','manager','treballador'], true)) {
      $role = 'manager';
    }

    // Evitar que l'admin es tregui el seu propi rol per error
    if ($id === (int)($_SESSION['user']['id'] ?? 0) && $role !== 'admin') {
      flash_set('No pots baixar-te el rol a tu mateix. Crea un altre admin primer.', 'bad');
      header('Location: usuaris.php?edit=' . $id);
      exit;
    }

    // email únic
    $st = db()->prepare('SELECT id FROM usuaris WHERE email = ? AND id <> ? LIMIT 1');
    $st->execute([$email, $id]);
    if ($st->fetch()) {
      flash_set('Aquest correu ja està en ús.', 'bad');
      header('Location: usuaris.php?edit=' . $id);
      exit;
    }

    if ($pass !== '') {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $st = db()->prepare('UPDATE usuaris SET name=?, email=?, role=?, contrasenya_enciptada=? WHERE id=?');
      $st->execute([$name,$email,$role,$hash,$id]);
    } else {
      $st = db()->prepare('UPDATE usuaris SET name=?, email=?, role=? WHERE id=?');
      $st->execute([$name,$email,$role,$id]);
    }

    flash_set('Usuari actualitzat.', 'ok');
    header('Location: usuaris.php');
    exit;
  }

  // Eliminar usuari
  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      header('Location: usuaris.php');
      exit;
    }
    if ($id === (int)($_SESSION['user']['id'] ?? 0)) {
      flash_set('No pots eliminar el teu propi usuari.', 'bad');
      header('Location: usuaris.php');
      exit;
    }

    $st = db()->prepare('DELETE FROM usuaris WHERE id=?');
    $st->execute([$id]);
    flash_set('Usuari eliminat.', 'ok');
    header('Location: usuaris.php');
    exit;
  }
}

// Dades per editar
$edit_user = null;
if ($edit_id > 0) {
  $st = db()->prepare('SELECT id,name,email,role,creat FROM usuaris WHERE id=?');
  $st->execute([$edit_id]);
  $edit_user = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

$users = db()->query('SELECT id,name,email,role,creat FROM usuaris ORDER BY creat DESC')->fetchAll(PDO::FETCH_ASSOC);

$titol = 'Usuaris · AGRISOFT';
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="card">
  <h1>Usuaris</h1>
  <p style="margin-top:6px;color:#666">Gestió de comptes i rols (admin/manager/worker).</p>

  <div style="display:grid;grid-template-columns: 420px 1fr;gap:18px;align-items:start;">
    <div class="card" style="background:#fff;">
      <h2 style="margin-top:0;"><?= $edit_user ? 'Editar usuari' : 'Crear usuari' ?></h2>

      <form method="post">
        <input type="hidden" name="action" value="<?= $edit_user ? 'update' : 'create' ?>">
        <?php if ($edit_user): ?>
          <input type="hidden" name="id" value="<?= (int)$edit_user['id'] ?>">
        <?php endif; ?>

        <label>Nom</label>
        <input name="name" required style="width:100%" value="<?= htmlspecialchars($edit_user['name'] ?? '') ?>">

        <label>Correu</label>
        <input name="email" type="email" required style="width:100%" value="<?= htmlspecialchars($edit_user['email'] ?? '') ?>">

        <label>Rol</label>
        <select name="role" style="width:100%">
          <?php
            $r = $edit_user['role'] ?? 'manager';
            $roles = ['admin' => 'admin', 'manager' => 'manager', 'treballador' => 'treballador'];
            foreach ($roles as $k=>$lbl):
          ?>
            <option value="<?= $k ?>" <?= $r === $k ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>

        <label><?= $edit_user ? 'Nova contrasenya (opcional)' : 'Contrasenya' ?></label>
        <input name="password" type="password" <?= $edit_user ? '' : 'required' ?> style="width:100%" placeholder="<?= $edit_user ? 'Deixa en blanc per no canviar-la' : '' ?>">

        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
          <button type="submit" class="btn btn-login"><?= $edit_user ? 'Desar canvis' : 'Crear usuari' ?></button>
          <?php if ($edit_user): ?>
            <a class="btn secondary" href="usuaris.php">Cancel·lar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="card" style="background:#fff;">
      <h2 style="margin-top:0;">Llistat</h2>
      <div style="overflow:auto;">
        <table class="table" style="width:100%;min-width:720px;">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nom</th>
              <th>Correu</th>
              <th>Rol</th>
              <th>Creat</th>
              <th>Accions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td><?= (int)$u['id'] ?></td>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge"><?= htmlspecialchars($u['role']) ?></span></td>
                <td><?= htmlspecialchars($u['creat']) ?></td>
                <td style="white-space:nowrap;">
                  <a class="btn secondary" href="usuaris.php?edit=<?= (int)$u['id'] ?>">Editar</a>
                  <form method="post" style="display:inline" onsubmit="return confirm('Eliminar aquest usuari?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn" style="background:#b01919;color:#fff;">Eliminar</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
