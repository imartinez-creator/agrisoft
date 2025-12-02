<?php
// Dades de connexió (canvia-ho per les teves)
$servername = "localhost";
$username = "root"; // El teu usuari
$password = "";     // La teva contrasenya
$dbname = "agrisoft_db"; // El nom de la teva base de dades

// 1. Crear connexió
$conn = new mysqli($servername, $username, $password, $dbname);

// 2. Comprovar connexió
if ($conn->connect_error) {
    die("Connexió fallida: " . $conn->connect_error);
}

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
    $sql = "INSERT INTO CLIENT (nom, tipus, contacte, direccio, requisits) VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // 5. Vincular paràmetres
        // "sssss" significa que passem 5 Strings
        $stmt->bind_param("sssss", $nom, $tipus, $contacte, $direccio, $requisits);

        // 6. Executar
        // Executar la consulta
        if ($stmt->execute()) {
            // Codi JavaScript per mostrar avís i redirigir
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