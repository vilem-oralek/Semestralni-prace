<?php
session_start();
include 'conn.php';

// Kontrola přihlášení 
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$user = null; 

// Získání dat uživatele 
$stmt = $conn->prepare("SELECT jmeno, prijmeni, telefon, email, datum_narozeni, profilovka_cesta FROM users WHERE id = ?"); 
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
}

// Pokud uživatel nebyl nalezen (i když je přihlášen, což by nemělo), odhlásit
if (!$user) {
    session_unset();
    session_destroy();
    header("Location: login.html");
    exit;
}

// Zde se definuje, jaký obrázek se má zobrazit
$profile_image_path = htmlspecialchars($user['profilovka_cesta'] ?? 'profile-picture-default.jpg');
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
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
            <li>Jméno: <span id="user-first-name"><?php echo htmlspecialchars($user['jmeno'] ?? 'N/A'); ?></span></li>
            <li>Příjmení: <span id="user-last-name"><?php echo htmlspecialchars($user['prijmeni'] ?? 'N/A'); ?></span></li>
            <li>E-mail: <span id="user-email"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></span></li>
            <li>Telefon: <span id="user-phone"><?php echo htmlspecialchars($user['telefon'] ?? 'N/A'); ?></span></li>
            <li>Datum narození: <span id="user-birthdate"><?php echo htmlspecialchars($user['datum_narozeni'] ?? 'N/A'); ?></span></li>
          </ul>
          <button type="button" class="edit-profile-button" onclick="toggleEditMode()">Upravit údaje</button>
        </div>

        <div id="edit-view" style="display:none;">
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
            <button type="button" class="cancel-profile-button" onclick="toggleEditMode()">Zrušit</button>
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
             // Načtení rezervací uživatele
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
    <footer>
        <p>&copy; 2023 Vilémův strejda. Všechna práva vyhrazena.</p>
    </footer>
    <script>
      function toggleEditMode() {
        const displayView = document.getElementById('display-view');
        const editView = document.getElementById('edit-view');

        if (displayView.style.display !== 'none') {
          // Přepnout na formulář
          displayView.style.display = 'none';
          editView.style.display = 'block';
        } else {
          // Přepnout zpět na seznam
          editView.style.display = 'none';
          displayView.style.display = 'block';
        }
      }
    </script>
    <script src="menu.js"></script>
</body>
</html>