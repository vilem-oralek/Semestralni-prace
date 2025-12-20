<?php
session_start();
include 'conn.php';

// 1. Kontrola přihlášení
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$target_dir = "uploads/";

// Vytvoření složky, pokud neexistuje
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile-photo'])) {
    
    $file = $_FILES['profile-photo'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_error = $file['error'];

    // Získání přípony souboru
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed = array('jpg', 'jpeg', 'png');

    if (in_array($file_ext, $allowed)) {
        if ($file_error === 0) {
            
            // Generování unikátního názvu
            $new_file_name = "profile_" . $user_id . "_" . uniqid() . "." . $file_ext;
            $file_destination = $target_dir . $new_file_name;

            // --- ZAČÁTEK ZMENŠOVÁNÍ OBRÁZKU (GD Library) ---
            
            // 1. Získání původních rozměrů a typu
            list($width_orig, $height_orig) = getimagesize($file_tmp);

            // 2. Nastavení cílové velikosti (Max šířka 300px)
            $target_width = 300;
            
            // Výpočet výšky se zachováním poměru stran
            $ratio = $width_orig / $height_orig;
            $target_height = $target_width / $ratio;

            // 3. Vytvoření nového prázdného obrázku v paměti (TrueColor)
            $image_p = imagecreatetruecolor($target_width, $target_height);

            // 4. Načtení původního obrázku podle typu
            if ($file_ext == 'jpg' || $file_ext == 'jpeg') {
                $image = imagecreatefromjpeg($file_tmp);
            } elseif ($file_ext == 'png') {
                $image = imagecreatefrompng($file_tmp);
                
                // Zachování průhlednosti u PNG
                imagealphablending($image_p, false);
                imagesavealpha($image_p, true);
            }

            // 5. Zmenšení (Resampling - klíčová funkce z prezentace)
            // Parametry: cíl, zdroj, cíl_x, cíl_y, zdroj_x, zdroj_y, cíl_šířka, cíl_výška, zdroj_šířka, zdroj_výška
            imagecopyresampled($image_p, $image, 0, 0, 0, 0, $target_width, $target_height, $width_orig, $height_orig);

            // 6. Uložení nového obrázku do složky
            if ($file_ext == 'jpg' || $file_ext == 'jpeg') {
                imagejpeg($image_p, $file_destination, 90); // Kvalita 90
            } elseif ($file_ext == 'png') {
                imagepng($image_p, $file_destination, 9); // Komprese 9
            }

            // Uvolnění paměti
            imagedestroy($image_p);
            imagedestroy($image);

            // --- KONEC ZMENŠOVÁNÍ ---

            // Aktualizace cesty v databázi
            $stmt = $conn->prepare("UPDATE users SET profilovka_cesta = ? WHERE id = ?");
            $stmt->bind_param("si", $file_destination, $user_id);
            
            if ($stmt->execute()) {
                header("Location: profile.php?upload=success");
            } else {
                echo "Chyba při ukládání do DB.";
            }

        } else {
            echo "Nastala chyba při nahrávání souboru.";
        }
    } else {
        echo "Tento typ souboru není povolen (pouze JPG, JPEG, PNG).";
    }
} else {
    header("Location: profile.php");
}
?>