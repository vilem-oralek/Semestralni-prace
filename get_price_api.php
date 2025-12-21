<?php
/**
 * @file get_price_api.php
 * API endpoint pro výpočet ceny rezervace.
 * Tento soubor přijímá data o datu příjezdu a odjezdu, kontroluje dostupnost termínu,
 * počítá cenu a vrací odpověď ve formátu JSON.
 */

include 'conn.php'; // Připojení k databázi
include 'calculate_price_logic.php'; // Načtení logiky pro výpočet ceny

header('Content-Type: application/json'); // Nastavení hlavičky pro JSON odpověď

/**
 * Zpracuje požadavek na výpočet ceny rezervace a vypíše JSON odpověď.
 * Kontroluje vstupy, dostupnost termínu a počítá cenu i počet nocí.
 *
 * @global mysqli $conn
 * @return void
 */
function process_price_request() {
    global $conn;
    if (isset($_GET['start']) && isset($_GET['end'])) {
        $start = $_GET['start'];
        $end = $_GET['end'];
        // Validace dat
        if ($start >= $end) {
            echo json_encode(['error' => 'Datum odjezdu musí být až po datu příjezdu.']);
            exit;
        }
        // Kontrola dostupnosti termínu
        if (!isTermAvailable($conn, $start, $end)) {
            echo json_encode(['error' => 'Tento termín je již obsazený.']);
            exit;
        }
        // Výpočet ceny
        $price = calculateTotalPrice($conn, $start, $end);
        // Výpočet počtu nocí
        $d1 = new DateTime($start);
        $d2 = new DateTime($end);
        $diff = $d1->diff($d2);
        $nights = $diff->days;
        // Odpověď ve formátu JSON
        echo json_encode([
            'price' => $price,
            'nights' => $nights,
            'available' => true
        ]);
    }
}

process_price_request();
?>