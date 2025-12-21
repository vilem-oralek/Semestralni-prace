<?php
/**
 * @file register.php
 * Zpracování registrace nového uživatele.
 * Tento soubor ověřuje vstupy z registračního formuláře, kontroluje, zda e-mail již existuje,
 * hashuje heslo a ukládá nového uživatele do databáze.
 */


include 'conn.php';
session_start();

/**
 * Zpracuje registraci nového uživatele, provede validace a uloží do DB.
 * Nastaví session a přesměruje na profil při úspěchu.
 *
 * @global mysqli $conn
 * @return void
 */
function process_registration() {
  global $conn;
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first-name']);
    $last_name = trim($_POST['last-name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $confirm_email = trim($_POST['confirm-email']);
    $password = $_POST['password'];
    $birthdate = $_POST['birthdate'];
    if (empty($first_name) || empty($last_name) || empty($phone) || empty($email) || empty($confirm_email) || empty($password) || empty($birthdate)) {
      echo '<script>alert("Všechna pole musí být vyplněna!");window.history.back();</script>';
      exit;
    }
    if (strlen($first_name) < 3 || strlen($first_name) > 40 || strlen($last_name) < 3 || strlen($last_name) > 40) {
      echo '<script>alert("Jméno a příjmení musí mít alespoň 3 znaky a maximálně 40 znaků.");window.history.back();</script>';
      exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      echo '<script>alert("Neplatný formát e-mailu.");window.history.back();</script>';
      exit;
    }
    if ($email !== $confirm_email) {
      echo '<script>alert("E-maily se neshodují. Zkontrolujte prosím zadané údaje.");window.history.back();</script>';
      exit;
    }
    if (!preg_match('/^\d{9}$/', $phone)) {
      echo '<script>alert("Telefonní číslo musí obsahovat přesně 9 číslic.");window.history.back();</script>';
      exit;
    }
    $today = new DateTime();
    $birthdate_obj = new DateTime($birthdate);
    $age = $today->diff($birthdate_obj)->y;
    if ($age < 18) {
      echo '<script>alert("Musíte být starší 18 let.");window.history.back();</script>';
      exit;
    }
    $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
      echo '<script>alert("Tento e-mail je již registrován. Použijte jiný e-mail.");window.history.back();</script>';
      exit;
    }
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (jmeno, prijmeni, telefon, email, heslo, datum_narozeni, role) VALUES (?, ?, ?, ?, ?, ?, 'user')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $first_name, $last_name, $phone, $email, $hashed_password, $birthdate);
    if ($stmt->execute()) {
      $_SESSION['user_id'] = $conn->insert_id;
      $_SESSION['email'] = $email;
      $_SESSION['role'] = 'user';
      $_SESSION['loggedin'] = true;
      echo '<script>alert("Jste úspěšně zaregistrovaný/á a přihlášený/á.");window.location.href = "profile.php";</script>';
    } else {
      echo "Error: " . $stmt->error;
    }
  } else {
    echo "Nastal ERROR, prosím zkuste znovu později!";
  }
}

process_registration();
?>