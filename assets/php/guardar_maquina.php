<?php
<<<<<<< HEAD
=======
// Configura les teves dades
>>>>>>> 1d4b5734e9f172a6b2fbd01a080841babb2c8535
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "agrisoft_db";

<<<<<<< HEAD
$conn = new mysqli($servername, $username, $password, $dbname);

=======
// 1. Connexió
$conn = new mysqli($servername, $username, $password, $dbname);

// Comprovar errors
>>>>>>> 1d4b5734e9f172a6b2fbd01a080841babb2c8535
if ($conn->connect_error) {
    die("Error de connexió: " . $conn->connect_error);
}

<<<<<<< HEAD
=======
// 2. Verificar que s'ha enviat el formulari
>>>>>>> 1d4b5734e9f172a6b2fbd01a080841babb2c8535
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recollim dades
    $nom = $_POST['nom'];
    $tipus = $_POST['tipus'];
    $matricula = $_POST['matricula'];
    $tipusCombustible = $_POST['tipusCombustible'];
    
<<<<<<< HEAD
    $cavalls = !empty($_POST['cavalls']) ? $_POST['cavalls'] : 0;

=======
    // Si el camp cavalls està buit, posem 0 per evitar errors
    $cavalls = !empty($_POST['cavalls']) ? $_POST['cavalls'] : 0;

    // 3. Sentència preparada (seguretat)
>>>>>>> 1d4b5734e9f172a6b2fbd01a080841babb2c8535
    $sql = "INSERT INTO MAQUINARIA (nom, tipus, matricula, tipusCombustible, cavalls) VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
<<<<<<< HEAD
        $stmt->bind_param("ssssi", $nom, $tipus, $matricula, $tipusCombustible, $cavalls);

        if ($stmt->execute()) {
=======
        // "ssssi" significa: String, String, String, String, Integer
        // El 'i' final és perquè els cavalls són un número (INT)
        $stmt->bind_param("ssssi", $nom, $tipus, $matricula, $tipusCombustible, $cavalls);

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
        echo "Error en preparar la consulta: " . $conn->error;
    }
}

$conn->close();
?>