<?php
// Configuració de la base de dades
$servername = "localhost";
$username = "root"; // Canvia-ho pel teu usuari
$password = ""; // Canvia-ho per la teva contrasenya
$dbname = "agrisoft_db"; // Canvia-ho pel nom de la teva BD

// Crear connexió
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la connexió
if ($conn->connect_error) {
    die("Connexió fallida: " . $conn->connect_error);
}

// Comprovar que les dades arriben per POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recollir dades del formulari
    $idClient = $_POST['idClient'];
    $nom = $_POST['nom'];
    $tipus = $_POST['tipus'];
    $contacte = $_POST['contacte'];
    $direccio = $_POST['direccio'];
    $requisits = $_POST['requisits'];

    // PREPARAR LA SENTÈNCIA (Prepared Statement)
    // Això és crucial per evitar Injecció SQL. Els '?' són marcadors de posició.
    $sql = "INSERT INTO CLIENT (idClient, nom, tipus, contacte, direccio, requisits) VALUES (?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // Vincular paràmetres
        // "isssss" significa: 
        // i = integer (per idClient)
        // s = string (pels altres 5 camps: nom, tipus, contacte, direccio, requisits)
        $stmt->bind_param("isssss", $idClient, $nom, $tipus, $contacte, $direccio, $requisits);

        // Executar la consulta
        if ($stmt->execute()) {
            // Codi JavaScript per mostrar avís i redirigir
            echo "<script>
                    alert('Client guardat correctament!');
                    window.location.href = 'altaclient.html';
                  </script>";
        } else {
            echo "Error al guardar: " . $stmt->error;
        }

        // Tancar la sentència
        $stmt->close();
    } else {
        echo "Error en la preparació de la consulta: " . $conn->error;
    }
} else {
    echo "Si us plau, envia el formulari primer.";
}

// Tancar la connexió
$conn->close();
?>