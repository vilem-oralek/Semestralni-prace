<?php
// Tento soubor obsahuje pouze logiku výpočtu, aby se dala použít na více místech

function calculateTotalPrice($conn, $start_date, $end_date) {
    // 1. Získání základní ceny
    $base_price_row = $conn->query("SELECT cena_za_noc FROM base_price LIMIT 1")->fetch_assoc();
    $base_price = $base_price_row ? floatval($base_price_row['cena_za_noc']) : 0;

    // 2. Získání sezónních cen
    $seasons = [];
    $result = $conn->query("SELECT * FROM season_prices");
    while ($row = $result->fetch_assoc()) {
        $seasons[] = $row;
    }

    // 3. Výpočet ceny den po dni
    $current_date = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);
    $total_price = 0;

    // Smyčka jede pro každou noc (proto < end_date, poslední den se nepočítá jako noc)
    while ($current_date < $end_date_obj) {
        $date_str = $current_date->format('Y-m-d');
        $night_price = $base_price; // Výchozí je základní cena

        // Zkontrolujeme, zda tento den spadá do nějaké sezóny
        foreach ($seasons as $season) {
            if ($date_str >= $season['datum_od'] && $date_str <= $season['datum_do']) {
                $night_price = floatval($season['cena_za_noc']);
                break; // Našli jsme sezónu, použijeme ji a jdeme dál
            }
        }

        $total_price += $night_price;
        $current_date->modify('+1 day');
    }

    return $total_price;
}

// Funkce pro kontrolu dostupnosti
function isTermAvailable($conn, $start_date, $end_date) {
    // Hledáme rezervaci, která se překrývá s požadovaným termínem
    // Logika: (NovýZačátek < ExistujícíKonec) A (NovýKonec > ExistujícíZačátek)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reservations WHERE datum_prijezdu < ? AND datum_odjezdu > ?");
    $stmt->bind_param("ss", $end_date, $start_date);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['count'] == 0;
}
?>