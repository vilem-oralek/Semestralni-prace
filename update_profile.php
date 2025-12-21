<?php
/**
 * @file update_profile.php
 * Skript pro aktualizaci uživatelských údajů.
 * Tento soubor zpracovává data z formuláře pro úpravu profilu uživatele,
 * provádí validaci vstupů a aktualizuje údaje v databázi.
 */

session_start();
include 'conn.php'; // Připojení k databázi

/**
 * Kontrola přihlášení uživatele.
 * Pokud uživatel není přihlášen, je přesměrován na přihlašovací stránku.
 * 
 * @return void
 */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html"); 
    exit;
}

/**
 * @var int $user_id ID přihlášeného uživatele.
 */
$user_id = $_SESSION['user_id'];

/**
 * Zpracování POST požadavku.
 * Pokud je formulář odeslán metodou POST, provede se validace a aktualizace dat.
 */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    /**
     * @var string $jmeno Jméno uživatele zadané ve formuláři.
     * @var string $prijmeni Příjmení uživatele zadané ve formuláři.
     * @var string $telefon Telefonní číslo uživatele zadané ve formuláři.
     * @var string $email E-mailová adresa uživatele zadaná ve formuláři.
     * @var string $datum_narozeni Datum narození uživatele zadané ve formuláři.
     */
    $jmeno = $_POST['jmeno'];
    $prijmeni = $_POST['prijmeni'];
    $telefon = $_POST['telefon'];
    $email = $_POST['email'];
    $datum_narozeni = $_POST['datum_narozeni'];

    /**
     * Validace vstupů.
     * Kontrola, zda jsou všechna pole vyplněna a zda splňují požadované formáty.
     */
    if (empty($jmeno) || empty($prijmeni) || empty($telefon) || empty($email) || empty($datum_narozeni)) {
        echo '<script>
                alert("Všechna pole musí být vyplněna!");
                window.location.href = "profile.php";
              </script>';
        exit;
    }
    /**
     * Validace délky jména a příjmení.
     * Jméno i příjmení musí být alespoň 3 znaky dlouhé a méně jak 40.
     */
    if (strlen($first_name) < 3 || strlen($first_name) > 40 || strlen($last_name) < 3 || strlen($last_name) > 40) {
        echo '<script>
                alert("Jméno a příjmení musí mít alespoň 3 znaky a maximálně 40 znaků.");
                window.history.back();
            </script>';
        exit;
    }
    /**
     * Validace e-mailu.
     * Kontrola, zda je e-mail ve správném formátu.
     */
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo '<script>
                alert("Neplatný formát e-mailu.");
                window.location.href = "profile.php";
              </script>';
        exit;
    }
    /**
     * Validace telefonního čísla.
     * Kontrola, zda telefonní číslo obsahuje přesně 9 číslic.
     */
    if (!preg_match('/^\d{9}$/', $telefon)) {
        echo '<script>
                alert("Telefonní číslo musí obsahovat přesně 9 číslic.");
                window.location.href = "profile.php";
              </script>';
        exit;
    }

    $today = new DateTime();
    $datum_narozeni_obj = new DateTime($datum_narozeni); // Převede datum na objekt DateTime
    $age = $today->diff($datum_narozeni_obj)->y; // Vypočítání věku

    /**
     * Kontrola věku uživatele.
     * Pokud je uživatel mladší než 18 let, zobrazí se chybová zpráva.
     */
    if ($age < 18) {
        echo '<script>
                alert("Musíte být starší 18 let.");
                window.location.href = "profile.php";
            </script>';
        exit;
    }
    
    /**
     * Aktualizace údajů uživatele v databázi.
     * Používá se Prepared Statement pro zvýšení bezpečnosti.
     */
    $stmt = $conn->prepare("UPDATE users SET jmeno = ?, prijmeni = ?, telefon = ?, email = ?, datum_narozeni = ? WHERE id = ?");
    
    // 'sssssi' = 5x string, 1x integer (jmeno, prijmeni, telefon, email, datum_narozeni, user_id)
    $stmt->bind_param("sssssi", $jmeno, $prijmeni, $telefon, $email, $datum_narozeni, $user_id); 
    
    /**
     * Kontrola úspěšnosti aktualizace.
     * Pokud je aktualizace úspěšná, zobrazí se potvrzovací zpráva a uživatel je přesměrován zpět na profil.
     * Pokud aktualizace selže (např. kvůli duplicitnímu e-mailu), zobrazí se chybová zpráva.
     */
    if ($stmt->execute()) {
        // Změna v databázi byla úspěšná
        $_SESSION['email'] = $email; 
        
        echo '<script>
                alert("Profilové údaje byly úspěšně aktualizovány!");
                window.location.href = "profile.php"; // Přesměrování zpět na profil
              </script>';
        exit;
    } else {
        // Chyba při provádění dotazu (např. pokud je email již obsazen a je nastaven jako UNIQUE)
        echo '<script>
                alert("Chyba při aktualizaci dat: Zkuste to prosím znovu.");
                window.location.href = "profile.php";
              </script>';
        exit;
    }
} else {
    // Pokud se někdo pokusí přejít na soubor přímo bez POST metody
    header("Location: profile.php");
    exit;
}
?>