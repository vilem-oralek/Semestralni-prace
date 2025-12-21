<?php
/**
 * @file update_profile.php
 * Skript pro aktualizaci uživatelských údajů.
 * Tento soubor zpracovává data z formuláře pro úpravu profilu uživatele,
 * provádí validaci vstupů a aktualizuje údaje v databázi.
 */


session_start();
include 'conn.php';

/**
 * Zpracuje aktualizaci profilu uživatele, provede validace a uloží změny do DB.
 *
 * @global mysqli $conn
 * @return void
 */
function process_update_profile() {
    global $conn;
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.html");
        exit;
    }
    $user_id = $_SESSION['user_id'];
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $jmeno = $_POST['jmeno'];
        $prijmeni = $_POST['prijmeni'];
        $telefon = $_POST['telefon'];
        $email = $_POST['email'];
        $datum_narozeni = $_POST['datum_narozeni'];
        if (empty($jmeno) || empty($prijmeni) || empty($telefon) || empty($email) || empty($datum_narozeni)) {
            echo '<script>alert("Všechna pole musí být vyplněna!");window.location.href = "profile.php";</script>';
            exit;
        }
        if (strlen($jmeno) < 3 || strlen($jmeno) > 40 || strlen($prijmeni) < 3 || strlen($prijmeni) > 40) {
            echo '<script>alert("Jméno a příjmení musí mít alespoň 3 znaky a maximálně 40 znaků.");window.history.back();</script>';
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo '<script>alert("Neplatný formát e-mailu.");window.location.href = "profile.php";</script>';
            exit;
        }
        if (!preg_match('/^\d{9}$/', $telefon)) {
            echo '<script>alert("Telefonní číslo musí obsahovat přesně 9 číslic.");window.location.href = "profile.php";</script>';
            exit;
        }
        $today = new DateTime();
        $datum_narozeni_obj = new DateTime($datum_narozeni);
        $age = $today->diff($datum_narozeni_obj)->y;
        if ($age < 18) {
            echo '<script>alert("Musíte být starší 18 let.");window.location.href = "profile.php";</script>';
            exit;
        }
        $stmt = $conn->prepare("UPDATE users SET jmeno = ?, prijmeni = ?, telefon = ?, email = ?, datum_narozeni = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $jmeno, $prijmeni, $telefon, $email, $datum_narozeni, $user_id);
        if ($stmt->execute()) {
            $_SESSION['email'] = $email;
            echo '<script>alert("Profilové údaje byly úspěšně aktualizovány!");window.location.href = "profile.php";</script>';
            exit;
        } else {
            echo '<script>alert("Chyba při aktualizaci dat: Zkuste to prosím znovu.");window.location.href = "profile.php";</script>';
            exit;
        }
    } else {
        header("Location: profile.php");
        exit;
    }
}

process_update_profile();
?>