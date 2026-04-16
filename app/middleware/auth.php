<?php
/* ===== Middleware d'autenticació i permisos ===== */
// Funcions per controlar l'accés dels usuaris a les pàgines

// Obliga a estar autenticat. Si no ho està, redirigeix al login
function require_login(): void {
  if (empty($_SESSION['user'])) { header('Location: login.php'); exit; }
}

// Retorna el rol de l'usuari actual (ex: 'admin', 'manager', 'treballador')
function current_role(): string {
  return $_SESSION['user']['role'] ?? '';
}

// Comprova si l'usuari té algun dels rols indicats
// Exemple: has_role(['admin', 'manager']) → true si és admin o manager
function has_role(array $roles): bool {
  $r = current_role();
  return $r !== '' && in_array($r, $roles, true);
}

// Obliga a tenir un dels rols indicats. Si no el té, mostra error 403 (accés denegat)
function require_role(array $roles): void {
  require_login();
  if (!has_role($roles)) {
    http_response_code(403);
    echo "Accés denegat.";
    exit;
  }
}

// Retorna true si l'usuari pot gestionar (crear/editar/eliminar)
// Només els rols 'admin' i 'manager' poden gestionar
function can_manage(): bool {
  return has_role(['admin','manager']);
}
