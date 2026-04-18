<?php
/**
 * AGRISOFT Installer - Professional Version
 * Aquest fitxer s'ha d'executar automàticament per regenerar la base de dades.
 */

// 1. Carregar configuració
require_once __DIR__ . '/../app/config/config.php';

// Configuració d'errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

$steps = [];
$error = false;

// Funció per executar el procés d'instal·lació
function run_install(&$steps) {
    try {
        $dsn_no_db = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
        $pdo = new PDO($dsn_no_db, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Pas 1: Eliminar si existeix
        $steps[] = ['icon' => '🔄', 'txt' => "Eliminant la base de dades '" . DB_NAME . "' si existeix..."];
        $pdo->exec("DROP DATABASE IF EXISTS `" . DB_NAME . "`");

        // Pas 2: Crear de nou
        $steps[] = ['icon' => '🆕', 'txt' => "Creant la base de dades '" . DB_NAME . "'..."];
        $pdo->exec("CREATE DATABASE `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE `" . DB_NAME . "`");

        // Pas 3: Executar schema.sql
        $schema_path = __DIR__ . '/schema.sql';
        $steps[] = ['icon' => '📦', 'txt' => "Executant schema.sql (estructura de taules)..."];
        if (!file_exists($schema_path)) throw new Exception("No s'ha trobat schema.sql");
        $pdo->exec(file_get_contents($schema_path));
        $steps[] = ['icon' => '✅', 'txt' => "Estructura creada correctament.", 'success' => true];

        // Pas 4: Executar insert_web_completa.sql
        $data_path = __DIR__ . '/insert_web_completa.sql';
        $steps[] = ['icon' => '📝', 'txt' => "Executant insert_web_completa.sql (dades de demo)..."];
        if (!file_exists($data_path)) throw new Exception("No s'ha trobat insert_web_completa.sql");
        $pdo->exec(file_get_contents($data_path));
        $steps[] = ['icon' => '✅', 'txt' => "Dades inserides correctament.", 'success' => true];

        return true;
    } catch (Exception $e) {
        $steps[] = ['icon' => '❌', 'txt' => "ERROR: " . $e->getMessage(), 'error' => true];
        return false;
    }
}

// Executem automàticament
$success = run_install($steps);
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGRISOFT Installer</title>
    <style>
        :root {
            --bg: #2d333b;
            --card-bg: #22272e;
            --border: #444c56;
            --text-main: #adbac7;
            --text-bright: #cdd9e5;
            --green: #2ecc71;
            --accent: #444c56;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
            background: #1c2128;
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background-image: radial-gradient(circle at 50% 50%, #2d333b 0%, #1c2128 100%);
        }

        .container {
            width: 100%;
            max-width: 600px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            text-align: center;
        }

        h1 {
            font-size: 32px;
            margin: 0;
            font-weight: 800;
        }

        h1 span.green { color: var(--green); }
        h1 span.white { color: #fff; }

        .subtitle {
            margin: 15px 0 30px;
            color: var(--text-main);
            font-size: 16px;
        }

        .steps-box {
            text-align: left;
            background: #1c2128;
            border-radius: 6px;
            border: 1px solid var(--border);
            margin-bottom: 30px;
        }

        .step-row {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 13px;
        }

        .step-row:last-child { border-bottom: none; }
        .step-icon { margin-right: 12px; font-size: 16px; }
        .success-text { color: var(--green); }
        .error-text { color: #f85149; }

        .success-panel {
            background: rgba(46, 204, 113, 0.05);
            border: 1px solid rgba(46, 204, 113, 0.2);
            border-radius: 12px;
            padding: 30px;
            margin-top: 25px;
        }

        .success-header {
            color: var(--green);
            font-size: 26px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .success-info {
            font-size: 15px;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .btn-entry {
            display: block;
            width: 100%;
            background: #2ecc71;
            color: #1c2128;
            text-decoration: none;
            padding: 18px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 18px;
            text-align: center;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        .btn-entry:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }

        .btn-entry:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><span class="green">AGRISOFT</span> <span class="white">Installer</span></h1>
        <div class="subtitle">Configuració de la base de dades i dades per defecte.</div>

        <div class="steps-box">
            <?php foreach ($steps as $s): ?>
                <div class="step-row <?= isset($s['success']) ? 'success-text' : (isset($s['error']) ? 'error-text' : '') ?>">
                    <span class="step-icon"><?= $s['icon'] ?></span>
                    <span><?= htmlspecialchars($s['txt']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($success): ?>
            <div class="success-panel">
                <div class="success-header">
                    <span>✅</span>
                    <span>Instal·lació Completada!</span>
                </div>
                <div class="success-info">La base de dades <strong><?= DB_NAME ?></strong> s'ha recreat correctament.</div>

                <a href="../public/login.php" class="btn-entry">Entrar a l'Aplicació</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
