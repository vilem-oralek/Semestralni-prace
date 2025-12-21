<?php
/**
 * @file is_logged_in.php
 * Kontrola přihlášení uživatele.
 * Tento soubor kontroluje, zda je uživatel přihlášen, a vrací odpověď ve formátu JSON.
 * Pokud je uživatel přihlášen, vrací `{"loggedIn": true}`, jinak vrací `{"loggedIn": false}`.
 */

session_start(); // Spuštění session
header('Content-Type: application/json'); // Nastavení hlavičky pro JSON odpověď

/**
 * Záznam informací do logu.
 * Tyto informace zahrnují ID session, ID uživatele a všechny session proměnné.
 */
error_log("=== SESSION CHECK ===");
error_log("Session ID: " . session_id());
error_log("User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("All session data: " . print_r($_SESSION, true));

/**
 * Kontrola, zda je uživatel přihlášen.
 * Pokud je nastavena session proměnná `user_id`, vrací JSON odpověď `{"loggedIn": true}`.
 * Pokud není nastavena, vrací JSON odpověď `{"loggedIn": false}`.
 */
if (isset($_SESSION['user_id'])) {
    echo json_encode(['loggedIn' => true]);
} else {
    echo json_encode(['loggedIn' => false]);
}
?>