<?php
/* ===== Capçalera HTML comuna a totes les pàgines ===== */
// Aquest fitxer genera el <head>, la barra lateral i la barra superior

// Carreguem la configuració i les funcions de missatges flash
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../helpers/flash.php';

// Detectem quina pàgina estem mostrant (per marcar-la activa al menú)
$current = basename($_SERVER['PHP_SELF'] ?? '');
$title = $title ?? APP_NAME; // Si no s'ha definit $title, usem el nom de l'app

// Retorna la classe CSS 'active' si la pàgina actual coincideix amb l'enllaç del menú
function nav_active(string $file, string $current): string {
  return $file === $current ? ' active' : '';
}
?>
<!doctype html>
<html lang="ca">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($title) ?></title>

  <!-- Fulls d'estil principals -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<!-- Contenidor principal del layout (sidebar + contingut) -->
<div class="layout">

  <!-- ===== Barra lateral de navegació (només si ha iniciat sessió) ===== -->
  <?php if (!empty($_SESSION['user'])): ?>
    <aside class="sidebar">
      <nav class="nav-vertical">
        <!-- Enllaços del menú principal -->
        <a class="nav-item<?= nav_active('index.php', $current) ?>" href="index.php">Tauler</a>
        <a class="nav-item<?= nav_active('parcelles.php', $current) ?>" href="parcelles.php">Parcel·les</a>
        <a class="nav-item<?= nav_active('mapa.php', $current) ?>" href="mapa.php">Mapa de Parcel·les</a>
        <a class="nav-item<?= nav_active('sectors.php', $current) ?>" href="sectors.php">Sectors</a>
        <a class="nav-item<?= nav_active('cultius.php', $current) ?>" href="cultius.php">Cultius</a>
        <a class="nav-item<?= nav_active('productes.php', $current) ?>" href="productes.php">Productes</a>
        <a class="nav-item<?= nav_active('tractaments.php', $current) ?>" href="tractaments.php">Tractaments</a>
        <a class="nav-item<?= nav_active('calculadora.php', $current) ?>" href="calculadora.php">Calculadora</a>
        <a class="nav-item<?= nav_active('plagues.php', $current) ?>" href="plagues.php">Plagues</a>
        <a class="nav-item<?= nav_active('analisi.php', $current) ?>" href="analisi.php">Anàlisi</a>
        <a class="nav-item<?= nav_active('collites.php', $current) ?>" href="collites.php">Collites</a>
        <a class="nav-item<?= nav_active('lots.php', $current) ?>" href="lots.php">Traçabilitat (Lots)</a>
        <a class="nav-item<?= nav_active('maquinaria.php', $current) ?>" href="maquinaria.php">Maquinària</a>
        <a class="nav-item<?= nav_active('personal.php', $current) ?>" href="personal.php">Personal</a>
        <a class="nav-item<?= nav_active('registre_hores.php', $current) ?>" href="registre_hores.php">Registre d'hores</a>
        <a class="nav-item<?= nav_active('tasques.php', $current) ?>" href="tasques.php">Tasques</a>
        <a class="nav-item<?= nav_active('alertes.php', $current) ?>" href="alertes.php">Alertes</a>
        <a class="nav-item<?= nav_active('reporting.php', $current) ?>" href="reporting.php">Reporting</a>

        <!-- Enllaç d'administració d'usuaris (només visible per admins) -->
        <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
          <div class="nav-sep"></div>
          <a class="nav-item<?= nav_active('usuaris.php', $current) ?>" href="usuaris.php">Usuaris</a>
        <?php endif; ?>

        <!-- Separador i botó de sortir -->
        <div class="nav-sep"></div>
        <a class="nav-item" href="logout.php">Sortir</a>
      </nav>
    </aside>
  <?php endif; ?>

  <!-- ===== Contingut principal ===== -->
  <main class="content">

    <!-- Barra superior amb el nom de l'app i les dades de l'usuari -->
    <header class="topbar">
      <div class="topbar-left">
        <a class="topbar-brand" href="index.php">
          <span class="dot"></span>
          <span><?= htmlspecialchars(APP_NAME) ?></span>
        </a>
      </div>

      <div class="topbar-right">
        <?php if (!empty($_SESSION['user'])): ?>
          <!-- Nom i rol de l'usuari connectat -->
          <div class="topbar-user">
            <div class="topbar-user-name"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Usuari') ?></div>
            <div class="topbar-user-meta">Rol: <?= htmlspecialchars($_SESSION['user']['role']) ?></div>
          </div>
        <?php endif; ?>
      </div>
    </header>

    <!-- Zona de contingut (on van les pàgines) -->
    <div class="wrap">

      <!-- Mostrem el missatge flash si n'hi ha algun pendent -->
      <?php if ($f = flash_get()): ?>
        <div class="flash <?= $f['type'] === 'bad' ? 'bad' : '' ?>">
          <?= htmlspecialchars($f['msg']) ?>
        </div>
      <?php endif; ?>
