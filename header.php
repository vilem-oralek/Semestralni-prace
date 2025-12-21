<?php
/**
 * @file header.php
 * Hlavička webu.
 * Tento soubor obsahuje logiku pro zobrazení hlavičky webu, včetně navigace,
 * uživatelského menu a profilové fotky. Dynamicky kontroluje, zda je uživatel přihlášen,
 * a zda má administrátorská práva, aby zobrazil admin panel.
 */

session_start();
/**
 * @var bool $is_logged_in Informace o tom, zda je uživatel přihlášen.
 */
$is_logged_in = isset($_SESSION['user_id']);
/**
 * @var bool $is_admin Informace o tom, zda je uživatel administrátor.
 */
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

/**
 * @var string $profile_image_path Cesta k profilové fotce uživatele.
 * Pokud není nastavena, použije se výchozí obrázek.
 */
$profile_image_path = 'profile-picture-default.jpg';
/**
 * @var string $display_name Jméno uživatele zobrazené v hlavičce.
 * Pokud není nastavena hodnota, použije se výchozí text "Profil".
 */
$display_name = 'Profil'; 

/**
 * Načtení dat přihlášeného uživatele z databáze.
 * Pokud je uživatel přihlášen, načtou se jeho jméno a cesta k profilové fotce.
 */
if ($is_logged_in) {
    include 'conn.php'; // Připojení k databázi
    /**
     * @var int $user_id ID přihlášeného uživatele.
     */
    $user_id = $_SESSION['user_id'];
    
    // Příprava SQL dotazu pro načtení uživatelských dat
    $stmt = $conn->prepare("SELECT jmeno, profilovka_cesta FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    /**
     * @var array|null $user_data Pole obsahující informace o uživateli.
     * Pokud uživatel není nalezen, hodnota je null.
     */
    $user_data = $result->fetch_assoc();
    
    // Nastavení profilové fotky a zobrazení jména
    if ($user_data) {
        if (!empty($user_data['profilovka_cesta'])) {
            $profile_image_path = htmlspecialchars($user_data['profilovka_cesta']);
        }
        if (!empty($user_data['jmeno'])) {
            $display_name = htmlspecialchars($user_data['jmeno']);
        }
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chata</title>
    <link rel="stylesheet" href="style.css">
    </head>
    <body id="headerBody">
      <header class="navbar" id="headernav">
        <div class="logo">
          <a href="index.php">Chata</a>
        </div>
    
        <div class="menu-toggle" id="menuToggle">☰</div>
    
        <nav class="nav-menu" id="navMenu">
          <ul>
            <li><a href="galerie.php">Galerie</a></li>
            <li><a href="rezervace.php">Rezervace</a></li>
            
            <?php if ($is_admin): ?>
                <li><a href="admin.php" id="admin-panel">Admin Panel</a></li>
            <?php endif; ?>
            
            <li><a href="kontakty.php">Kontakt</a></li>
          </ul>
        </nav>
    
        <div class="user-controls">
          <div class="user-dropdown">
            <div class="username"><?php echo $display_name; ?></div>
            <div class="dropdown-content">
              <?php if ($is_logged_in): ?>
                <a href="profile.php">Profil</a>
                <?php if ($is_admin): ?>
                    <a href="admin.php">Admin Panel</a>
                <?php endif; ?>
                <a href="logout.php">Odhlásit se</a>
              <?php else: ?>
                <a href="login.html">Přihlásit se</a> <a href="registration.html">Registrovat</a> <?php endif; ?>
            </div>
          </div>
          <div class="user-photo-dropdown">
            <img src="<?php echo $profile_image_path; ?>" alt="Profilová fotka" class="user-photo" id="userPhoto">
            <div class="dropdown-content">
              <?php if ($is_logged_in): ?>
                <a href="profile.php">Profil</a>
                <a href="logout.php">Odhlásit se</a>
              <?php else: ?>
                <a href="login.html">Přihlásit se</a> <a href="registration.html">Registrovat</a> <?php endif; ?>
            </div>
          </div>
        </div>
      </header>
    </body>
</html>