<?php
include 'conn.php';
session_start(); // Start the session to store user data

// Je použita metoda POST?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first_name = $_POST['first-name'];
  $last_name = $_POST['last-name'];
  $phone = $_POST['phone'];
  $email = $_POST['email'];
  $confirm_email = $_POST['confirm-email'];
  $password = $_POST['password'];
  $birthdate = $_POST['birthdate'];

  if ($email !== $confirm_email) {
    echo '<script>
            alert("E-maily se neshodují. Zkontrolujte prosím zadané údaje.");
            window.history.back(); // Vrátí uživatele zpět na registrační formulář
          </script>';
    exit; // Zastaví další zpracování
  }
  
  $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    // E-mail již existuje
    echo '<script>
            alert("Tento e-mail je již registrován. Použijte jiný e-mail.");
            window.history.back(); // Vrátí uživatele zpět na registrační formulář
          </script>';
    exit; // Zastaví další zpracování
  }

  // Zahashování hesla do proměné 
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);

  // SQL command který vkládá uživatele do databáze
  $sql = "INSERT INTO users (jmeno, prijmeni, telefon, email, heslo, datum_narozeni, role) VALUES (?, ?, ?, ?, ?, ?, 'user')";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ssssss", $first_name, $last_name, $phone, $email, $hashed_password, $birthdate);

  // Provedení SQL commandu
  if ($stmt->execute()) {
    // Nastavení session proměnných pro automatické přihlášení
    $_SESSION['user_id'] = $conn->insert_id; // Získání ID nově vloženého uživatele
    $_SESSION['email'] = $email;
    $_SESSION['role'] = 'user'; // Defaultní role je 'user'
    $_SESSION['loggedin'] = true;

    // Přesměrování na profilovou stránku
    echo '<script>
            alert("Jste úspěšně zaregistrovaný/á a přihlášený/á.");
            window.location.href = "profile.php";
          </script>';
  } else {
      echo "Error: " . $stmt->error;
  }
} else {
  // form nebyl submitnutý metodou POST
  echo "Nastal ERROR, prosím zkuste znovu později!";
}
?>