<?php
// Dades de connexió (per defecte a XAMPP)
$host = "localhost";
$user = "root";
$pass = ""; 
$db   = "agrisoft_db";

$conn = new mysqli($host, $user, $pass, $db);

<<<<<<< HEAD
=======
// Si falla la connexió, retornem un error JSON perquè el JS ho entengui
>>>>>>> 1d4b5734e9f172a6b2fbd01a080841babb2c8535
if ($conn->connect_error) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Error de connexió a la Base de Dades']));
}
?>