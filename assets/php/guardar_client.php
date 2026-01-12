<?php
<<<<<<< HEAD
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "agrisoft_db";

$conn = new mysqli($servername, $username, $password, $dbname);

=======
// Dades de connexió (canvia-ho per les teves)
$servername = "localhost";
$username = "root"; // El teu usuari
$password = "";     // La teva contrasenya
$dbname = "agrisoft_db"; // El nom de la teva base de dades

// 1. Crear connexió
$conn = new mysqli($servername, $username, $password, $dbname);

// 2. Comprovar connexió
>>>>>>> 1d4b5734e9f172a6b2fbd01a080841babb2c8535
if ($conn->connect_error) {
    die("Connexió fallida: " . $conn->connect_error);
}

<<<<<<< HEAD
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nom = $_POST['nom'];
    $tipus = $_POST['tipus'];
    $contacte = $_POST['contacte'];
    $direccio = $_POST['direccio'];
    $requisits = $_POST['requisits'];

=======
// 3. Verificar que les dades venen del mètode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recollim les dades del formulari usant els 'name'
    $nom = $_POST['nom'];
    $tipus = $_POST['tipus'];
    $contacte = $_POST['contacte']; // Si està buit, serà string buida
    $direccio = $_POST['direccio'];
    $requisits = $_POST['requisits'];

    // 4. Preparar la sentència SQL (INSERT)
    // Els signes d'interrogació (?) són marcadors de seguretat
>>>>>>> 1d4b5734e9f172a6b2fbd01a080841babb2c8535
    $sql = "INSERT INTO CLIENT (nom, tipus, contacte, direccio, requisits) VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
<<<<<<< HEAD
        $stmt->bind_param("sssss", $nom, $tipus, $contacte, $direccio, $requisits);

        if ($stmt->execute()) {
=======
        // 5. Vincular paràmetres
        // "sssss" significa que passem 5 Strings
        $stmt->bind_param("sssss", $nom, $tipus, $contacte, $direccio, $requisits);

        // 6. Executar
        // Executar la consulta
        if ($stmt->execute()) {
            // Codi JavaScript per mostrar avís i redirigir
>>>>>>> 1d4b5734e9f172a6b2fbd01a080841babb2c8535
            echo "<script>
                    alert('Client guardat correctament!');
                    window.location.href = '../../index.html';
                  </script>";
        } else {
            echo "Error al guardar: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error a la preparació de la consulta: " . $conn->error;
    }
}

$conn->close();
?>