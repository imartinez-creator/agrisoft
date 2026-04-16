<?php
/* ===== Tancar sessió ===== */
// Iniciem la sessió, la destruïm i redirigim al login
session_start();
session_destroy();
header('Location: login.php');
