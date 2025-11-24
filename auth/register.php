<?php
header('Content-Type: application/json');

// 1. Recibir datos del frontend (JSON)
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$nombre = $input['nombre'] ?? '';

if (empty($email) || empty($password) || empty($nombre)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit;
}

// 2. Cargar usuarios existentes
$file = 'users.json';
if (!file_exists($file)) {
    file_put_contents($file, '[]');
}
$users = json_decode(file_get_contents($file), true);

// 3. Comprobar si ya existe el usuario
foreach ($users as $user) {
    if ($user['email'] === $email) {
        echo json_encode(['success' => false, 'message' => 'El usuario ya existe']);
        exit;
    }
}

// 4. Crear nuevo usuario (CON HASHING DE CONTRASEÑA)
// Usamos PASSWORD_DEFAULT que actualmente usa algoritmos seguros como Bcrypt
$newUser = [
    'id' => uniqid(),
    'nombre' => $nombre,
    'email' => $email,
    'password' => password_hash($password, PASSWORD_DEFAULT), 
    'created_at' => date('Y-m-d H:i:s')
];

// 5. Guardar
$users[] = $newUser;
if (file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true, 'message' => 'Usuario registrado correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al escribir en el archivo']);
}
?>