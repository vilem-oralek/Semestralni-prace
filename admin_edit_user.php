<?php
session_start();
include 'conn.php';

// Zabezpečení
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

$edit_id = 0;
$user_to_edit = null;

// Pokud přicházíme přes GET (zobrazení formuláře)
if (isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $user_to_edit = $stmt->get_result()->fetch_assoc();
}

// Pokud odesíláme formulář (POST)
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
        echo "<script>alert('Uživatel byl upraven.'); window.location.href='admin.php';</script>";
        exit;
    } else {
        echo "<script>alert('Chyba při úpravě.');</script>";
    }
}

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