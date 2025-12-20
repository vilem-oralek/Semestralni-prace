<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
// Zjistíme, jestli je admin
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

$profile_image_path = 'profile-picture-default.jpg'; 
$display_name = 'Profil'; 

if ($is_logged_in) {
    include 'conn.php'; 
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT jmeno, profilovka_cesta FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    
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
    <body id = "headerBody">
      <header class="navbar" id="headernav">
        <div class="logo">
          <a href="index.php">Chata</a>
        </div>
    
        <div class="menu-toggle" onclick="toggleMenu()">☰</div>
    
        <nav class="nav-menu" id="navMenu">
          <ul>
            <li><a href="galerie.php">Galerie</a></li>
            <li><a href="rezervace.php">Rezervace</a></li>
            
            <?php if ($is_admin): ?>
                <li><a href="admin.php" style="color: #ffcccc; font-weight: bold;">Admin Panel</a></li>
            <?php endif; ?>
            
            <li><a href="kontakty.html">Kontakt</a></li>
          </ul>
        </nav>
    
        <div class="user-controls" onclick="toggleDropdown()">
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
            <img src="<?php echo $profile_image_path; ?>" alt="Profilová fotka" class="user-photo" onclick="toggleDropdown()">
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