<?php
header('Content-Type: application/json');
require 'db.php';

// Llegim el JSON que envia el teu script.js
$data = json_decode(file_get_contents("php://input"));

if(isset($data->nombre) && isset($data->email) && isset($data->password)) {
    
    $nom = $data->nombre; 
    $email = $data->email;
    $password = password_hash($data->password, PASSWORD_DEFAULT); // Encriptem la contrasenya

    // 1. Comprovar si l'email ja existeix
    $check = $conn->prepare("SELECT id FROM USUARIS WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if($result->num_rows > 0){
        echo json_encode(['success' => false, 'message' => 'Aquest correu ja està registrat.']);
    } else {
        // 2. Insertar nou usuari
        $stmt = $conn->prepare("INSERT INTO USUARIS (nom, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nom, $email, $password);
        
        if($stmt->execute()){
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar a la base de dades.']);
        }
        $stmt->close();
    }
    $check->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Dades incompletes.']);
}
$conn->close();
?>