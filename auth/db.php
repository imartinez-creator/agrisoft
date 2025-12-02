<?php
// Dades de connexió (per defecte a XAMPP)
$host = "localhost";
$user = "root";
$pass = ""; 
$db   = "agrisoft_db";

$conn = new mysqli($host, $user, $pass, $db);

// Si falla la connexió, retornem un error JSON perquè el JS ho entengui
if ($conn->connect_error) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Error de connexió a la Base de Dades']));
}
?>