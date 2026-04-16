<?php
/* ===== Càrrega de fitxers necessaris ===== */
require_once __DIR__ . '/../app/config/db.php';     // Connexió a la base de dades
require_once __DIR__ . '/../app/helpers/flash.php';  // Missatges flash (avisos a l'usuari)

/* ===== Iniciar sessió PHP ===== */
// Si encara no hi ha sessió activa, en creem una
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/* ===== Si ja ha iniciat sessió, redirigim a l'inici ===== */
// Si l'usuari ja està autenticat, no cal que vegi el login
if (!empty($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}

/* ===== Processar el formulari de login (POST) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Recollim les dades del formulari
  $email = trim($_POST['email'] ?? '');   // Correu electrònic
  $pass  = $_POST['password'] ?? '';       // Contrasenya

  // Busquem l'usuari a la base de dades pel seu correu
  $st = db()->prepare("
    SELECT id, name, email, contrasenya_enciptada, role
    FROM usuaris
    WHERE email = ?
    LIMIT 1
  ");
  $st->execute([$email]);
  $u = $st->fetch(PDO::FETCH_ASSOC);

  // Comprovem que l'usuari existeixi i la contrasenya sigui correcta
  if ($u && password_verify($pass, $u['contrasenya_enciptada'])) {
    // Guardem les dades de l'usuari a la sessió
    $_SESSION['user'] = [
      'id'    => $u['id'],
      'name'  => $u['name'],
      'email' => $u['email'],
      'role'  => $u['role']
    ];

    // Missatge de benvinguda i redirecció a l'inici
    flash_set("Benvingut/da, {$u['name']}!", "ok");
    header('Location: index.php');
    exit;
  }

  // Si les credencials són incorrectes, mostrem error
  flash_set("Credencials incorrectes.", "bad");
}

/* ===== Títol de la pàgina i capçalera HTML ===== */
$titol = "Inici de sessió · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<!-- ===== Formulari de login centrat a la pantalla ===== -->
<div style="display: flex; align-items: center; justify-content: center; min-height: calc(100vh - 100px); padding: 20px;">
  <div class="card" style="width: 100%; max-width: 450px;">
    <h1 style="text-align: center;">Inicia sessió</h1>

    <form method="post">
      <!-- Camp del correu electrònic -->
      <label>Correu</label>
      <input name="email" type="email" required style="width: 100%;" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

      <!-- Camp de la contrasenya -->
      <label>Contrasenya</label>
      <input name="password" type="password" required style="width: 100%;">

      <!-- Botó per enviar el formulari -->
      <div class="login-actions">
        <button type="submit" class="btn btn-login">Entrar</button>
      </div>
    </form>

  </div>
</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
