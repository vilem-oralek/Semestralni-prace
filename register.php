<?php
/**
 * @file register.php
 * Zpracování registrace nového uživatele.
 * Tento soubor ověřuje vstupy z registračního formuláře, kontroluje, zda e-mail již existuje,
 * hashuje heslo a ukládá nového uživatele do databáze.
 */

include 'conn.php'; // Připojení k databázi
session_start(); // Startování session pro uložení uživatelských dat

/**
 * Zpracování registračního formuláře.
 * Kontroluje, zda byl formulář odeslán metodou POST, a provádí validaci vstupů.
 * 
 * @return void
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  /**
   * @var string $first_name Křestní jméno zadané uživatelem.
   * @var string $last_name Příjmení zadané uživatelem.
   * @var string $phone Telefonní číslo zadané uživatelem.
   * @var string $email E-mailová adresa zadaná uživatelem.
   * @var string $confirm_email Potvrzení e-mailu zadané uživatelem.
   * @var string $password Heslo zadané uživatelem.
   * @var string $birthdate Datum narození zadané uživatelem.
   */
  $first_name = trim($_POST['first-name']);
  $last_name = trim($_POST['last-name']);
  $phone = trim($_POST['phone']);
  $email = trim($_POST['email']);
  $confirm_email = trim($_POST['confirm-email']);
  $password = $_POST['password'];
  $birthdate = $_POST['birthdate'];

  /**
   * Validace vstupů.
   * Kontrola, zda jsou všechna pole vyplněna.
   */
  if (empty($first_name) || empty($last_name) || empty($phone) || empty($email) || empty($confirm_email) || empty($password) || empty($birthdate)) {
    echo '<script>
            alert("Všechna pole musí být vyplněna!");
            window.history.back();
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
            window.history.back();
          </script>';
    exit;
  }
  /**
   * Kontrola, zda se e-mail a potvrzovací e-mail shodují.
   */
  if ($email !== $confirm_email) {
    echo '<script>
            alert("E-maily se neshodují. Zkontrolujte prosím zadané údaje.");
            window.history.back();
          </script>';
    exit;
  }
  /**
   * Validace telefonního čísla.
   * Kontrola, zda telefonní číslo obsahuje přesně 9 číslic.
   */
  if (!preg_match('/^\d{9}$/', $phone)) {
    echo '<script>
            alert("Telefonní číslo musí obsahovat přesně 9 číslic.");
            window.history.back();
          </script>';
    exit;
  }
  /**
   * Validace data narození.
   * Kontrola, zda je uživatel starší než 18 let.
   */
  $today = new DateTime();
  $birthdate_obj = new DateTime($birthdate); // Převede datum na objekt DateTime
  $age = $today->diff($birthdate_obj)->y; // Vypočítání věku

  if ($age < 18) {
    echo '<script>
            alert("Musíte být starší 18 let.");
            window.history.back();
          </script>';
    exit;
  }
  /**
   * Kontrola, zda e-mail již existuje v databázi.
   */
  $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    echo '<script>
            alert("Tento e-mail je již registrován. Použijte jiný e-mail.");
            window.history.back();
          </script>';
    exit;
  }

  /**
   * Zahashování hesla pomocí funkce password_hash.
   */
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);

  /**
   * SQL příkaz pro vložení nového uživatele do databáze.
   */
  $sql = "INSERT INTO users (jmeno, prijmeni, telefon, email, heslo, datum_narozeni, role) VALUES (?, ?, ?, ?, ?, ?, 'user')";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ssssss", $first_name, $last_name, $phone, $email, $hashed_password, $birthdate);

  /**
   * Provedení SQL dotazu a kontrola úspěšnosti.
   */
  if ($stmt->execute()) {
    $_SESSION['user_id'] = $conn->insert_id; // Získání ID nově vloženého uživatele
    $_SESSION['email'] = $email;
    $_SESSION['role'] = 'user'; // Defaultní role je 'user'
    $_SESSION['loggedin'] = true;

    echo '<script>
            alert("Jste úspěšně zaregistrovaný/á a přihlášený/á.");
            window.location.href = "profile.php";
          </script>';
  } else {
    echo "Error: " . $stmt->error;
  }
} else {
  echo "Nastal ERROR, prosím zkuste znovu později!";
}
?>