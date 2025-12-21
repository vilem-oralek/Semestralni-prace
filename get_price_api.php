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
 * Zpracování požadavku na výpočet ceny.
 * Kontroluje, zda jsou nastaveny parametry `start` a `end`, a provádí validaci vstupů.
 */
if (isset($_GET['start']) && isset($_GET['end'])) {
    /**
     * @var string $start Datum příjezdu zadané uživatelem.
     */
    $start = $_GET['start'];
    /**
     * @var string $end Datum odjezdu zadané uživatelem.
     */
    $end = $_GET['end'];

    /**
     * Validace dat.
     * Kontrola, zda je datum odjezdu později než datum příjezdu.
     */
    if ($start >= $end) {
        echo json_encode(['error' => 'Datum odjezdu musí být až po datu příjezdu.']);
        exit;
    }

    /**
     * Kontrola dostupnosti termínu.
     * Pokud je termín obsazený, vrací chybovou zprávu ve formátu JSON.
     */
    if (!isTermAvailable($conn, $start, $end)) {
        echo json_encode(['error' => 'Tento termín je již obsazený.']);
        exit;
    }

    /**
     * Výpočet celkové ceny rezervace.
     * 
     * @var float $price Celková cena rezervace.
     */
    $price = calculateTotalPrice($conn, $start, $end);
    
    /**
     * Výpočet počtu nocí mezi datem příjezdu a odjezdu.
     * 
     * @var DateTime $d1 Objekt reprezentující datum příjezdu.
     * @var DateTime $d2 Objekt reprezentující datum odjezdu.
     * @var int $nights Počet nocí mezi daty.
     */
    $d1 = new DateTime($start);
    $d2 = new DateTime($end);
    $diff = $d1->diff($d2);
    $nights = $diff->days;

    /**
     * Odpověď ve formátu JSON.
     * Vrací cenu, počet nocí a informaci o dostupnosti termínu.
     */
    echo json_encode([
        'price' => $price,
        'nights' => $nights,
        'available' => true
    ]);
}
?>