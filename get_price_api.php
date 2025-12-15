<?php
// API endpoint pro JavaScript
include 'conn.php';
include 'calculate_price_logic.php';

header('Content-Type: application/json');

if (isset($_GET['start']) && isset($_GET['end'])) {
    $start = $_GET['start'];
    $end = $_GET['end'];

    if ($start >= $end) {
        echo json_encode(['error' => 'Datum odjezdu musí být až po datu příjezdu.']);
        exit;
    }

    // 1. Kontrola dostupnosti
    if (!isTermAvailable($conn, $start, $end)) {
        echo json_encode(['error' => 'Tento termín je již obsazený.']);
        exit;
    }

    // 2. Výpočet ceny
    $price = calculateTotalPrice($conn, $start, $end);
    
    // Spočítáme počet nocí
    $d1 = new DateTime($start);
    $d2 = new DateTime($end);
    $diff = $d1->diff($d2);
    $nights = $diff->days;

    echo json_encode([
        'price' => $price,
        'nights' => $nights,
        'available' => true
    ]);
}
?>