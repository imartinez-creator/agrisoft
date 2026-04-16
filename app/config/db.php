<?php
/* ===== Connexió a la base de dades ===== */

// Carreguem la configuració (on estan definides DB_HOST, DB_NAME, etc.)
require_once __DIR__ . '/config.php';

// Funció que retorna la connexió PDO a la base de dades
// Utilitza el patró "singleton": només crea la connexió un cop, i la reutilitza
function db(): PDO {
  static $pdo=null; // Variable estàtica: es manté entre crides
  if($pdo) return $pdo; // Si ja existeix la connexió, la retornem directament

  // Construïm el DSN (Data Source Name) per connectar a MySQL amb charset UTF-8
  $dsn='mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';

  // Creem la connexió PDO amb les opcions:
  // - ERRMODE_EXCEPTION: si hi ha error, llança una excepció
  // - FETCH_ASSOC: els resultats es retornen com a arrays associatius
  $pdo=new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
  ]);
  return $pdo;
}
