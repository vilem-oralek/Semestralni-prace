<?php
// Inicializace pole pro chyby
$errors = [];

// Zpracování formuláře po odeslání
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validace uživatelského jména
    $username = trim($_POST['username'] ?? '');
    if (empty($username)) {
        $errors[] = "Uživatelské jméno je povinné.";
    } elseif (strlen($username) < 3) {
        $errors[] = "Uživatelské jméno musí mít alespoň 3 znaky.";
    }

    // Validace e-mailu
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $errors[] = "E-mail je povinný.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Neplatný formát e-mailu.";
    }

    // Validace hesla
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if (empty($password)) {
        $errors[] = "Heslo je povinné.";
    } elseif (strlen($password) < 4) {
        $errors[] = "Heslo musí mít alespoň 4 znaky.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Hesla se neshodují.";
    }

    // Validace obrázku
    if (!empty($_FILES['image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['image']['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Profilový obrázek musí být ve formátu JPEG, PNG nebo GIF.";
        }
    }

    // Pokud nejsou žádné chyby, zpracujeme data
    if (empty($errors)) {
        header('Location: welcome.php');
        // Zde můžete přidat logiku pro uložení dat do databáze nebo další zpracování
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrace</title>
    <style>
        .error { color: red; }
        .row { margin-bottom: 10px; }
        label { display: inline-block; width: 150px; }
        .errors { border: 1px solid red; padding: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Registrační formulář</h1>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li class="error"><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="row">
            <label for="username">Uživatelské jméno:</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required>
            <?php 
            if(isset($errors['username'])) {
                echo '<span class="error">' . htmlspecialchars($errors['username']) . '</span>';
            }
            ?>
        </div>

        <div class="row">
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
            <?php 
            if(isset($errors['email'])) {
                echo '<span class="error">' . htmlspecialchars($errors['email']) . '</span>';
            }
            
            
            ?>
        </div>  

        <div class="row">
            <label for="image">Profilový obrázek:</label>
            <input type="file" id="image" name="image" accept="image/*">
        </div>

        <div class="row">
            <label for="password">Heslo:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="row">
            <label for="confirm_password">Potvrzení hesla:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>

        <input type="submit" name="register" value="Registrovat">
    </form>
</body>
</html>
