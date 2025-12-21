<?php
/**
 * @file profile.php
 * Stránka profilu uživatele.
 * Tento soubor zobrazuje osobní údaje uživatele, umožňuje jejich úpravu,
 * zobrazuje profilovou fotku a seznam rezervací uživatele.
 */

session_start();
include 'conn.php';

/**
 * Zkontroluje přihlášení uživatele, jinak přesměruje na login.
 * @return void
 */
function require_login() {
  if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
  }
}

/**
 * Načte informace o uživateli podle ID.
 * @param mysqli $conn
 * @param int $user_id
 * @return array|null
 */
function get_user_data($conn, $user_id) {
  $stmt = $conn->prepare("SELECT jmeno, prijmeni, telefon, email, datum_narozeni, profilovka_cesta FROM users WHERE id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows == 1) {
    return $result->fetch_assoc();
  }
  return null;
}

/**
 * Pokud uživatel nebyl nalezen, zruší session a přesměruje na login.
 * @param array|null $user
 * @return void
 */
function require_user_exists($user) {
  if (!$user) {
    session_unset();
    session_destroy();
    header("Location: login.html");
    exit;
  }
}

/**
 * Vrátí profilovou fotku uživatele nebo výchozí obrázek.
 * @param array $user
 * @return string
 */
function get_profile_image_path($user) {
  return htmlspecialchars($user['profilovka_cesta'] ?? 'profile-picture-default.jpg');
}

require_login();
$user_id = $_SESSION['user_id'];
$user = get_user_data($conn, $user_id);
require_user_exists($user);
$profile_image_path = get_profile_image_path($user);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <title>Můj Profil</title>
    <script>
      fetch("header.php")
        .then(response => response.text())
        .then(data => {
          document.getElementById("header-placeholder").innerHTML = data;
        });
    </script>
</head>
<body>
  <div id="header-placeholder"></div>
  <section class="profile-hero">
    <div class="background-image"></div>
    <div id="profile-container">
      <h1 class="profile-title">Můj Profil</h1>
      
      <section class="profile-details">
        <h2>Osobní údaje</h2>
        
        <div id="display-view">
          <ul>
            <li>Jméno: <span><?php echo htmlspecialchars($user['jmeno'] ?? 'N/A'); ?></span></li>
            <li>Příjmení: <span><?php echo htmlspecialchars($user['prijmeni'] ?? 'N/A'); ?></span></li>
            <li>E-mail: <span><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></span></li>
            <li>Telefon: <span><?php echo htmlspecialchars($user['telefon'] ?? 'N/A'); ?></span></li>
            <li>Datum narození: <span><?php echo htmlspecialchars($user['datum_narozeni'] ?? 'N/A'); ?></span></li>
          </ul>
          <button type="button" class="edit-profile-button" id="editProfileButton">Upravit údaje</button>
        </div>

        <div id="edit-view">
          <form id="profile-edit-form" method="post" action="update_profile.php">
            
            <div class="form-group-edit">
              <label for="jmeno">Jméno:</label>
              <input type="text" id="jmeno" name="jmeno" value="<?php echo htmlspecialchars($user['jmeno'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group-edit">
              <label for="prijmeni">Příjmení:</label>
              <input type="text" id="prijmeni" name="prijmeni" value="<?php echo htmlspecialchars($user['prijmeni'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group-edit">
              <label for="email">E-mail:</label>
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group-edit">
              <label for="telefon">Telefon:</label>
              <input type="tel" id="telefon" name="telefon" value="<?php echo htmlspecialchars($user['telefon'] ?? ''); ?>" pattern="[0-9]{9}" title="Zadejte 9-místné číslo (např. 123456789)" required>
            </div>
            
            <div class="form-group-edit">
              <label for="datum_narozeni">Datum narození:</label>
              <input type="date" id="datum_narozeni" name="datum_narozeni" value="<?php echo htmlspecialchars($user['datum_narozeni'] ?? ''); ?>" required>
            </div>
            
            <button type="submit" class="save-profile-button">Uložit změny</button>
            <button type="button" class="cancel-profile-button" id="cancelEditButton">Zrušit</button>
          </form>
        </div>

        <img src="<?php echo $profile_image_path; ?>" alt="Profilová fotka" class="profile-page-photo">
      </section>

      <section class="profile-photo-upload">
        <h2>Nahrát profilovou fotku</h2>
        <form id="photo-upload-form" method="post" action="upload_profile_photo.php" enctype="multipart/form-data">
          <input type="file" id="profile-photo" name="profile-photo" accept="image/*" required>
          <button type="submit">Nahrát</button>
        </form>
      </section>

      <section class="profile-reservations">
        <h2>Moje rezervace</h2>
        <ul id="reservations-list">
          <?php
             $res_stmt = $conn->prepare("SELECT * FROM reservations WHERE user_id = ? ORDER BY datum_prijezdu DESC");
             $res_stmt->bind_param("i", $user_id);
             $res_stmt->execute();
             $my_reservations = $res_stmt->get_result();

             if ($my_reservations->num_rows > 0):
                 while($r = $my_reservations->fetch_assoc()):
          ?>
            <li>
                <strong><?php echo date('d.m.Y', strtotime($r['datum_prijezdu'])); ?> - <?php echo date('d.m.Y', strtotime($r['datum_odjezdu'])); ?></strong>
                <br>
                Cena: <?php echo number_format($r['celkova_cena'], 0, ',', ' '); ?> Kč
                <?php if(!empty($r['poznamka'])): ?><br><i>Pozn: <?php echo htmlspecialchars($r['poznamka']); ?></i><?php endif; ?>
            </li>
          <?php 
                 endwhile; 
             else:
          ?>
            <li>Nemáte zatím žádné rezervace.</li>
          <?php endif; ?>
        </ul>
      </section>
    </div>
  </section>
  <?php include 'footer.html'; ?>
  
  <script>
    document.addEventListener("DOMContentLoaded", function () {
        const displayView = document.getElementById('display-view');
        const editView = document.getElementById('edit-view');
        const editProfileButton = document.getElementById('editProfileButton');
        const cancelEditButton = document.getElementById('cancelEditButton');

        function showEditMode() {
            displayView.style.display = 'none';
            editView.style.display = 'flex'; 
        }

        function showDisplayMode() {
            editView.style.display = 'none';
            displayView.style.display = 'block';
        }

        if(editProfileButton) editProfileButton.addEventListener('click', showEditMode);
        if(cancelEditButton) cancelEditButton.addEventListener('click', showDisplayMode);
    });
  </script>
  <script src="menu.js"></script>
</body>
</html>