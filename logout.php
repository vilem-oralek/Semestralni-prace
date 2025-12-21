<?php
/**
 * @file logout.php
 * Odhlášení uživatele.
 * Tento soubor ukončí uživatelskou session, odstraní všechny session proměnné
 * a přesměruje uživatele na přihlašovací stránku.
 */

session_start(); // Startování session
session_unset(); // Odstranění všech session proměnných
session_destroy(); // Zničení session
header("Location: login.html"); // Přesměrování na přihlašovací stránku
exit; // Ukončení skriptu
?>