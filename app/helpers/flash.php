<?php
/* ===== Helper de missatges flash ===== */
// Serveix per mostrar missatges temporals a l'usuari (avisos, errors, confirmacions)
// El missatge es guarda a la sessió i es mostra un sol cop

// Guarda un missatge flash a la sessió
// $msg = text del missatge, $type = 'ok' (èxit) o 'bad' (error)
function flash_set(string $msg,string $type='ok'): void { $_SESSION['flash']=['msg'=>$msg,'type'=>$type]; }

// Recupera el missatge flash i l'esborra de la sessió (només es mostra un cop)
// Retorna null si no n'hi ha cap
function flash_get(): ?array { if(empty($_SESSION['flash'])) return null; $f=$_SESSION['flash']; unset($_SESSION['flash']); return $f; }
