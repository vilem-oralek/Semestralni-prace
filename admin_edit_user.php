<?php
/**
 * @file admin_edit_user.php
 * Stránka pro úpravu uživatelských údajů administrátorem.
 * Tento soubor umožňuje administrátorovi zobrazit a upravit údaje konkrétního uživatele.
 */

session_start();
include 'conn.php'; // Připojení k databázi

/**
 * Zabezpečení přístupu.
 * Kontrola, zda je uživatel přihlášen a má administrátorská práva.
 * Pokud ne, je přesměrován na přihlašovací stránku.
 * 
 * @return void
 */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

/**
 * @var int $edit_id ID uživatele, jehož údaje se mají upravit.
 */
$edit_id = 0;
/**
 * @var array|null $user_to_edit Pole obsahující informace o uživateli, jehož údaje se upravují.
 * Pokud uživatel není nalezen, hodnota je null.
 */
$user_to_edit = null;

/**
 * Zpracování GET požadavku.
 * Pokud je v URL parametr `id`, načtou se údaje uživatele z databáze.
 */
if (isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $user_to_edit = $stmt->get_result()->fetch_assoc();
}

/**
 * Zpracování POST požadavku.
 * Pokud je odeslán formulář, aktualizují se údaje uživatele v databázi.
 */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    /**
     * @var int $id ID uživatele, jehož údaje se aktualizují.
     */
    $id = intval($_POST['user_id']);
    /**
     * @var string $jmeno Jméno uživatele.
     * @var string $prijmeni Příjmení uživatele.
     * @var string $email E-mailová adresa uživatele.
     * @var string $telefon Telefonní číslo uživatele.
     * @var string $datum_narozeni Datum narození uživatele.
     */
    $jmeno = $_POST['jmeno'];
    $prijmeni = $_POST['prijmeni'];
    $email = $_POST['email'];
    $telefon = $_POST['telefon'];
    $datum_narozeni = $_POST['datum_narozeni'];

    // Aktualizace údajů uživatele v databázi
    $stmt = $conn->prepare("UPDATE users SET jmeno=?, prijmeni=?, email=?, telefon=?, datum_narozeni=? WHERE id=?");
    $stmt->bind_param("sssssi", $jmeno, $prijmeni, $email, $telefon, $datum_narozeni, $id);
    
    /**
     * Kontrola úspěšnosti aktualizace.
     * Pokud je aktualizace úspěšná, uživatel je přesměrován zpět na stránku administrace.
     * Pokud aktualizace selže, zobrazí se chybová zpráva.
     */
    if ($stmt->execute()) {
        echo "<script>alert('Uživatel byl upraven.'); window.location.href='admin.php';</script>";
        exit;
    } else {
        echo "<script>alert('Chyba při úpravě.');</script>";
    }
}
/**
 * Kontrola, zda byl uživatel nalezen.
 * Pokud uživatel neexistuje, zobrazí se chybová zpráva a skript se ukončí.
 */
if (!$user_to_edit) {
    echo "Uživatel nenalezen.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editace Uživatele - Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <section class="profile-hero">
        <main id="profile-container"> <h1 class="profile-title">Editace Uživatele (Admin)</h1>
            
            <div class="profile-details">
                <div id="edit-view" id="view-edit-admin">
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
                    
                    <button type="submit" class="save-profile-button">Uložit změny</button>
                    <a href="admin.php" class="cancel-profile-button" id = "zrusit-admin">Zrušit</a>
                  </form>
                </div>
            </div>
        </main>
    </section>
</body>
</html>