<?php
/* ===== Configuració general de l'aplicació AGRISOFT ===== */

// Dades de connexió a la base de dades MySQL
define('DB_HOST','127.0.0.1');  // Servidor de la BD (localhost)
define('DB_NAME','agrisoft');    // Nom de la base de dades
define('DB_USER','root');        // Usuari de la BD
define('DB_PASS','');            // Contrasenya de la BD (buida per defecte)

// Nom de l'aplicació i URL base
define('APP_NAME','AGRISOFT');
define('BASE_URL','/agrisoft/public');

// Configuració d'alertes per correu electrònic
define('ALERT_EMAIL_ENABLED', false);  // Activar/desactivar enviament de correus d'alerta
define('ALERT_EMAIL_TO','');           // Adreça de correu on enviar les alertes

// Iniciem la sessió PHP si encara no està activa
if (session_status()===PHP_SESSION_NONE) session_start();
