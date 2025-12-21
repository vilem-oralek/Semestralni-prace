<?php
/**
 * @file conn.php
 * Připojení k databázi.
 * Tento soubor inicializuje připojení k databázi pomocí rozhraní MySQLi
 * a kontroluje, zda bylo připojení úspěšné.
 */

/**
 * @var string $servername Název serveru databáze.
 */
$servername = "localhost";
/**
 * @var string $username Uživatelské jméno pro připojení k databázi.
 */
$username = "zlatnjir";
/**
 * @var string $password Heslo pro připojení k databázi.
 */
$password = "webove aplikace";
/**
 * @var string $database Název databáze, ke které se připojujeme.
 */
$database = "zlatnjir";

/**
 * Inicializace připojení k databázi pomocí MySQLi.
 * 
 * @var mysqli $conn Objekt reprezentující připojení k databázi.
 */
$conn = new mysqli($servername, $username, $password, $database);

/**
 * Kontrola připojení k databázi.
 * Pokud připojení selže, skript se ukončí a zobrazí chybovou zprávu.
 */
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>