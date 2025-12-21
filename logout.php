<?php
/**
 * @file logout.php
 * Odhlášení uživatele.
 * Tento soubor ukončí uživatelskou session, odstraní všechny session proměnné
 * a přesměruje uživatele na přihlašovací stránku.
 */


/**
 * Odhlásí uživatele, ukončí session a přesměruje na přihlašovací stránku.
 *
 * @return void
 */
function logout_user() {
	session_start();
	session_unset();
	session_destroy();
	header("Location: login.html");
	exit;
}

logout_user();
?>