<?php
/* 
 * Fitxer: usuaris.php (Treballadors i Accessos)
 * Descripció: Gestió unificada d'usuaris del sistema i dades laborals.
 */

require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

// Només administradors
require_role(['admin']);

$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

/* ===== Processar (POST) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  $name       = trim($_POST['name'] ?? '');
  $email      = trim($_POST['email'] ?? '');
  $role       = $_POST['role'] ?? 'manager';
  $pass       = $_POST['password'] ?? '';
  $telefon    = trim($_POST['telefon'] ?? '');
  $rol_feina  = trim($_POST['rol_de_treball'] ?? '');
  $cost_hora  = $_POST['cost_hora'] !== '' ? (float)$_POST['cost_hora'] : null;

  if ($action === 'create') {
    if ($name === '' || $email === '' || $pass === '') {
      flash_set('Nom, correu i contrasenya són obligatoris.', 'bad');
      header('Location: usuaris.php'); exit;
    }
    if (!in_array($role, ['admin','manager','treballador'])) $role = 'manager';

    $st = db()->prepare('SELECT id FROM usuaris WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    if ($st->fetch()) {
      flash_set('Correu ja registrat.', 'bad');
      header('Location: usuaris.php'); exit;
    }

    // Insert to usuaris
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $st = db()->prepare('INSERT INTO usuaris (name,email,contrasenya_enciptada,role) VALUES (?,?,?,?)');
    $st->execute([$name,$email,$hash,$role]);
    $new_user_id = db()->lastInsertId();

    // Insert to treballadors
    $st_treballador = db()->prepare('INSERT INTO treballadors (nom_complet, telefon, rol_de_treball, cost_hora, user_id) VALUES (?, ?, ?, ?, ?)');
    $st_treballador->execute([$name, $telefon, $rol_feina, $cost_hora, $new_user_id]);

    flash_set('Treballador creat correctament.', 'ok');
    header('Location: usuaris.php'); exit;
  }

  if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0 || $name === '' || $email === '') {
      flash_set('Dades incompletes.', 'bad');
      header('Location: usuaris.php'); exit;
    }
    if (!in_array($role, ['admin','manager','treballador'])) $role = 'manager';

    if ($id === (int)($_SESSION['user']['id'] ?? 0) && $role !== 'admin') {
      flash_set('No pots baixar-te el rol a tu mateix.', 'bad');
      header('Location: usuaris.php?edit=' . $id . '#form-box'); exit;
    }

    $st = db()->prepare('SELECT id FROM usuaris WHERE email = ? AND id <> ? LIMIT 1');
    $st->execute([$email, $id]);
    if ($st->fetch()) {
      flash_set('Aquest correu ja està en ús.', 'bad');
      header('Location: usuaris.php?edit=' . $id . '#form-box'); exit;
    }

    // Update usuaris
    if ($pass !== '') {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $st = db()->prepare('UPDATE usuaris SET name=?, email=?, role=?, contrasenya_enciptada=? WHERE id=?');
      $st->execute([$name,$email,$role,$hash,$id]);
    } else {
      $st = db()->prepare('UPDATE usuaris SET name=?, email=?, role=? WHERE id=?');
      $st->execute([$name,$email,$role,$id]);
    }

    // Update treballadors
    $chk = db()->prepare("SELECT id FROM treballadors WHERE user_id = ?");
    $chk->execute([$id]);
    if ($chk->fetch()) {
        $st_upd = db()->prepare("UPDATE treballadors SET nom_complet=?, telefon=?, rol_de_treball=?, cost_hora=? WHERE user_id=?");
        $st_upd->execute([$name, $telefon, $rol_feina, $cost_hora, $id]);
    } else {
        $st_ins = db()->prepare("INSERT INTO treballadors (nom_complet, telefon, rol_de_treball, cost_hora, user_id) VALUES (?, ?, ?, ?, ?)");
        $st_ins->execute([$name, $telefon, $rol_feina, $cost_hora, $id]);
    }

    flash_set('Treballador actualitzat.', 'ok');
    header('Location: usuaris.php'); exit;
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id === (int)($_SESSION['user']['id'] ?? 0)) {
        flash_set('No pots eliminar el teu compte actiu.', 'bad');
        header('Location: usuaris.php'); exit;
    }
    
    if ($id > 0) {
        try {
            db()->prepare('DELETE FROM treballadors WHERE user_id=?')->execute([$id]);
            db()->prepare('DELETE FROM usuaris WHERE id=?')->execute([$id]);
            flash_set('Usuari eliminat.', 'ok');
        } catch (Exception $e) {
            flash_set("No s'ha pogut eliminar l'usuari perquè té hores o tasques vinculades al sistema.", "bad");
        }
    }
    header('Location: usuaris.php'); exit;
  }
}

// Dades per editar
$edit_user = null;
if ($edit_id > 0) {
  $st = db()->prepare('
    SELECT u.id, u.name, u.email, u.role, u.creat, t.telefon, t.rol_de_treball, t.cost_hora 
    FROM usuaris u 
    LEFT JOIN treballadors t ON u.id = t.user_id 
    WHERE u.id=?
  ');
  $st->execute([$edit_id]);
  $edit_user = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

$users = db()->query('
  SELECT u.id, u.name, u.email, u.role, u.creat, t.telefon, t.rol_de_treball, t.cost_hora 
  FROM usuaris u 
  LEFT JOIN treballadors t ON u.id = t.user_id 
  ORDER BY u.creat DESC
')->fetchAll(PDO::FETCH_ASSOC);

/* ===== Títol ===== */
$titol = "Treballadors · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="card">
  <h1>Treballadors i Accessos</h1>
  <p style="margin-top:6px;color:#666">Gestió integral del personal: credencials i fitxa laboral.</p>

  <div style="display:grid;grid-template-columns: 420px 1fr;gap:18px;align-items:start;">
    <div class="card" style="background:#fff;" id="form-box">
      <h2 style="margin-top:0;"><?= $edit_user ? 'Editar persona' : 'Nou treballador' ?></h2>

      <form method="post">
        <input type="hidden" name="action" value="<?= $edit_user ? 'update' : 'create' ?>">
        <?php if ($edit_user): ?>
          <input type="hidden" name="id" value="<?= (int)$edit_user['id'] ?>">
        <?php endif; ?>
        
        <div style="background:#f4f7fa; padding:10px; border-radius:6px; margin-bottom:15px">
            <h3 style="margin:0 0 10px 0; font-size:14px; border-bottom:1px solid #ddd; padding-bottom:5px">Dades d'Accés</h3>
            <label>Nom</label>
            <input name="name" required style="width:100%" value="<?= htmlspecialchars($edit_user['name'] ?? '') ?>">

            <label>Correu electrònic</label>
            <input name="email" type="email" required style="width:100%" value="<?= htmlspecialchars($edit_user['email'] ?? '') ?>">

            <label>Rol al Sistema</label>
            <select name="role" style="width:100%">
              <?php
                $r = $edit_user['role'] ?? 'treballador';
                $roles = ['admin' => 'Admin (Tot)', 'manager' => 'Manager (Gestió)', 'treballador' => 'Treballador (Base)'];
                foreach ($roles as $k=>$lbl):
              ?>
                <option value="<?= $k ?>" <?= $r === $k ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>

            <label><?= $edit_user ? 'Nova contrasenya (Opcional)' : 'Contrasenya' ?></label>
            <input name="password" type="password" <?= $edit_user ? '' : 'required' ?> style="width:100%" placeholder="<?= $edit_user ? 'Si en poses una, la canviaràs' : '' ?>">
        </div>

        <div style="background:#fefefe; border: 1px solid #eee; padding:10px; border-radius:6px; margin-bottom:10px">
            <h3 style="margin:0 0 10px 0; font-size:14px; border-bottom:1px solid #ddd; padding-bottom:5px">Fitxa Laboral</h3>
            
            <label>Telèfon</label>
            <input name="telefon" style="width:100%" value="<?= htmlspecialchars($edit_user['telefon'] ?? '') ?>" placeholder="Opcional">

            <label>Ocupació (Rol de feina)</label>
            <input name="rol_de_treball" style="width:100%" value="<?= htmlspecialchars($edit_user['rol_de_treball'] ?? '') ?>" placeholder="Ex: Tractorista, Podador...">

            <label>Cost / Hora (€)</label>
            <input name="cost_hora" type="number" step="0.01" style="width:100%" value="<?= htmlspecialchars($edit_user['cost_hora'] ?? '') ?>">
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
          <button type="submit" class="btn btn-login"><?= $edit_user ? 'Desar canvis' : 'Donar d\'alta' ?></button>
          <?php if ($edit_user): ?>
            <a class="btn secondary" href="usuaris.php">Cancel·lar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="card" style="background:#fff;">
      <h2 style="margin-top:0;">Llistat de la Plantilla</h2>
      <div style="overflow:auto;">
        <table class="table" style="width:100%;min-width:720px;">
          <thead>
            <tr>
              <th>Nom</th>
              <th>C/T</th>
              <th>Permisos</th>
              <th>Rol / €/h</th>
              <th style="min-width: 140px;">Accions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr style="<?= ($edit_id == $u['id']) ? 'background:#f0f7ff' : '' ?>">
                <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                <td>
                    <?= htmlspecialchars($u['email']) ?><br>
                    <small style="color:#666"><?= htmlspecialchars($u['telefon'] ?? '-') ?></small>
                </td>
                <td><span class="badge border-<?= $u['role']=='admin'?'red':($u['role']=='manager'?'yellow':'green') ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                <td>
                    <?= htmlspecialchars($u['rol_de_treball'] ?? '-') ?><br>
                    <small style="color:#666"><?= isset($u['cost_hora']) ? $u['cost_hora'].' €/h' : '-' ?></small>
                </td>
                <td style="white-space:nowrap;">
                  <a class="btn secondary btn-small" href="usuaris.php?edit=<?= (int)$u['id'] ?>#form-box">Editar</a>
                  <form method="post" style="display:inline" onsubmit="return confirm('ATENCIÓ: Esborrarà l\'usuari i el seu registre de personal. Segur?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn btn-red btn-small">Eliminar</button>
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
