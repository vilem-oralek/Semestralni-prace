<?php
/**
 * @file is_logged_in.php
 * Kontrola přihlášení uživatele.
 * Tento soubor kontroluje, zda je uživatel přihlášen, a vrací odpověď ve formátu JSON.
 * Pokud je uživatel přihlášen, vrací `{"loggedIn": true}`, jinak vrací `{"loggedIn": false}`.
 */


session_start();
header('Content-Type: application/json');

/**
 * Vrátí JSON odpověď s informací, zda je uživatel přihlášen.
 * Zaznamená informace do logu (session ID, user ID, session data).
 *
 * @return void
 */
function respond_is_logged_in() {
    error_log("=== SESSION CHECK ===");
    error_log("Session ID: " . session_id());
    error_log("User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
    error_log("All session data: " . print_r($_SESSION, true));
    if (isset($_SESSION['user_id'])) {
        echo json_encode(['loggedIn' => true]);
    } else {
        echo json_encode(['loggedIn' => false]);
    }
}

respond_is_logged_in();
?>