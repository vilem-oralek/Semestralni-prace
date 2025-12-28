<?php
/**
 * @file admin_edit_user.php
 * Stránka pro úpravu uživatelských údajů administrátorem.
 */

/**
 * Inicializuje session a připojení k databázi.
 * @return mysqli $conn Připojení k databázi
 */
function init_connection() {
  // Kontrola, zda session už neběží, aby nedošlo k chybě
  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }
  include 'conn.php'; 
  return $conn;
}

/**
 * Zabezpečí přístup pouze pro administrátory.
 */
function require_admin() {
  if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
  }
}

/**
 * Načte uživatele k editaci podle ID z GET.
 */
function get_user_to_edit($conn) {
  if (isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }
  return null;
}

/**
 * Zpracuje POST požadavek na úpravu uživatele.
 */
function handle_edit_user_post($conn) {
  if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $id = intval($_POST['user_id']);
    $jmeno = $_POST['jmeno'];
    $prijmeni = $_POST['prijmeni'];
    $email = $_POST['email'];
    $telefon = $_POST['telefon'];
    $datum_narozeni = $_POST['datum_narozeni'];
    
    $stmt = $conn->prepare("UPDATE users SET jmeno=?, prijmeni=?, email=?, telefon=?, datum_narozeni=? WHERE id=?");
    $stmt->bind_param("sssssi", $jmeno, $prijmeni, $email, $telefon, $datum_narozeni, $id);
    
    if ($stmt->execute()) {
      // Přesměrování zpět na tabulku uživatelů v adminu
      echo "<script>alert('Uživatel byl upraven.'); window.location.href='admin.php?tab=users';</script>";
      exit;
    } else {
      echo "<script>alert('Chyba při úpravě.');</script>";
    }
  }
}

/**
 * Pokud uživatel neexistuje, ukončí skript.
 */
function require_user_exists($user_to_edit) {
  if (!$user_to_edit) {
    echo "Uživatel nenalezen.";
    exit;
  }
}

// --- HLAVNÍ LOGIKA ---
$conn = init_connection();
require_admin();
handle_edit_user_post($conn);
$user_to_edit = get_user_to_edit($conn);
require_user_exists($user_to_edit);
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editace Uživatele - Admin</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script>
      fetch("header.php").then(r => r.text()).then(d => document.getElementById("header-placeholder").innerHTML = d);
    </script>
</head>
<body>
    <div id="header-placeholder"></div>
    <div class="background-image"></div>

    <section class="profile-hero">
        <main id="profile-container"> 
            <h1 class="profile-title">Editace Uživatele (Admin)</h1>
            
            <div class="profile-details">
                <div class="admin-edit-container">
                  <form id="profile-edit-form" method="post" action="admin_edit_user.php">
                    <input type="hidden" name="user_id" value="<?php echo $user_to_edit['id']; ?>">
                    
                    <div class="form-group-edit">
                      <label>Jméno:</label>
                      <input type="text" name="jmeno" value="<?php echo htmlspecialchars($user_to_edit['jmeno']); ?>" required>
                    </div>
                    
                    <div class="form-group-edit">
                      <label>Příjmení:</label>
                      <input type="text" name="prijmeni" value="<?php echo htmlspecialchars($user_to_edit['prijmeni']); ?>" required>
                    </div>
                    
                    <div class="form-group-edit">
                      <label>E-mail:</label>
                      <input type="email" name="email" value="<?php echo htmlspecialchars($user_to_edit['email']); ?>" required>
                    </div>
                    
                    <div class="form-group-edit">
                      <label>Telefon:</label>
                      <input type="text" name="telefon" value="<?php echo htmlspecialchars($user_to_edit['telefon']); ?>" required>
                    </div>
                    
                    <div class="form-group-edit">
                      <label>Datum narození:</label>
                      <input type="date" name="datum_narozeni" value="<?php echo htmlspecialchars($user_to_edit['datum_narozeni']); ?>" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="save-profile-button">Uložit změny</button>
                        <a href="admin.php?tab=users" class="cancel-profile-button">Zrušit</a>
                    </div>
                  </form>
                </div>
            </div>
        </main>
    </section>

    <?php include 'footer.html'; ?>
    <script src="menu.js"></script>
</body>
</html>