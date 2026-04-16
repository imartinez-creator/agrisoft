<?php
/* ===== API: Informació d'un producte fitosanitari ===== */
// Retorna dades JSON d'un producte donat el seu ID
// Usat pel formulari de tractaments per carregar info del producte seleccionat

require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';

// Comprova que l'usuari hagi iniciat sessió
require_login();

// Establim la capçalera JSON
header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['error' => 'ID invàlid']);
    exit;
}

$st = db()->prepare("SELECT id, name, substancia_activa, unitat, stock, dosi_maxima, tipus FROM fito_productes WHERE id = ?");
$st->execute([$id]);
$producte = $st->fetch(PDO::FETCH_ASSOC);

if (!$producte) {
    echo json_encode(['error' => 'Producte no trobat']);
    exit;
}

// Retornem les dades del producte
echo json_encode($producte);
