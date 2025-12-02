<?php
// Configura les teves dades de connexió
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "agrisoft_db"; // Canvia pel nom real de la teva base de dades

// 1. Crear connexió
$conn = new mysqli($servername, $username, $password, $dbname);

// Comprovar connexió
if ($conn->connect_error) {
    die("Connexió fallida: " . $conn->connect_error);
}

// 2. Verificar que rebem dades per POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recollim les dades del formulari (atributs 'name')
    $nom = $_POST['nom'];
    $tipus = $_POST['tipus'];

    // 3. Preparem la sentència SQL
    // NO posem l'Id a l'INSERT, es genera sol.
    $sql = "INSERT INTO ESPECIE (Nom, tipus) VALUES (?, ?)";
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // "ss" significa que passem 2 Strings (text)
        $stmt->bind_param("ss", $nom, $tipus);

        // 4. Executar i comprovar
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
        echo "Error preparant la consulta: " . $conn->error;
    }
}

$conn->close();
?>
