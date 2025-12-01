<?php
header('Content-Type: application/json');
session_start();
require 'db.php';

$data = json_decode(file_get_contents("php://input"));

if(isset($data->email) && isset($data->password)) {
    
    $email = $data->email;
    $password = $data->password;

    $stmt = $conn->prepare("SELECT id, nom, password FROM usuaris WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()){
        if(password_verify($password, $row['password'])){
            
            $_SESSION['user_id'] = $row['id'];
            
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $row['id'],
                    'nom' => $row['nom'],
                    'email' => $email
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Contrasenya incorrecta.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuari no trobat.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Falten dades.']);
}
$conn->close();
?>