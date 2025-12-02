<?php
// Configura les teves dades
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "agrisoft_db";

// 1. Connexió
$conn = new mysqli($servername, $username, $password, $dbname);

// Comprovar errors
if ($conn->connect_error) {
    die("Error de connexió: " . $conn->connect_error);
}

// 2. Verificar que s'ha enviat el formulari
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recollim dades
    $nom = $_POST['nom'];
    $tipus = $_POST['tipus'];
    $matricula = $_POST['matricula'];
    $tipusCombustible = $_POST['tipusCombustible'];
    
    // Si el camp cavalls està buit, posem 0 per evitar errors
    $cavalls = !empty($_POST['cavalls']) ? $_POST['cavalls'] : 0;

    // 3. Sentència preparada (seguretat)
    $sql = "INSERT INTO MAQUINARIA (nom, tipus, matricula, tipusCombustible, cavalls) VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // "ssssi" significa: String, String, String, String, Integer
        // El 'i' final és perquè els cavalls són un número (INT)
        $stmt->bind_param("ssssi", $nom, $tipus, $matricula, $tipusCombustible, $cavalls);

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
        echo "Error en preparar la consulta: " . $conn->error;
    }
}

$conn->close();
?>