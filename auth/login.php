<?php
session_start(); // Iniciar manejo de sesiones de PHP
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

$file = 'users.json';
if (!file_exists($file)) {
    echo json_encode(['success' => false, 'message' => 'No hay usuarios registrados']);
    exit;
}

$users = json_decode(file_get_contents($file), true);
$userFound = null;

// 1. Buscar usuario
foreach ($users as $user) {
    if ($user['email'] === $email) {
        $userFound = $user;
        break;
    }
}

// 2. Verificar contraseña
if ($userFound && password_verify($password, $userFound['password'])) {
    // ¡Éxito! Guardamos datos en la sesión del servidor
    $_SESSION['user_id'] = $userFound['id'];
    $_SESSION['user_name'] = $userFound['nombre'];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Login correcto',
        'user' => ['nombre' => $userFound['nombre'], 'email' => $userFound['email']]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
}
?>