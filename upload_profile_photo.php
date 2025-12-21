<?php
/**
 * @file upload_profile_photo.php
 * Skript pro nahrávání a zpracování profilové fotografie uživatele.
 * Tento soubor kontroluje přihlášení uživatele, validuje nahraný soubor,
 * zmenšuje obrázek na maximální šířku 300px a ukládá cestu k obrázku do databáze.
 */

session_start();
include 'conn.php'; // Připojení k databázi

// 1. Kontrola přihlášení
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

/**
 * @var int $user_id ID přihlášeného uživatele.
 */
$user_id = $_SESSION['user_id'];

/**
 * @var string $target_dir Cílová složka pro nahrané soubory.
 */
$target_dir = "uploads/";

// Vytvoření složky, pokud neexistuje
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

/**
 * Zpracování nahraného souboru.
 * Kontrola, zda byl soubor odeslán metodou POST a zda je přítomen soubor `profile-photo`.
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile-photo'])) {
    
    /**
     * @var array $file Informace o nahraném souboru.
     * @var string $file_name Název nahraného souboru.
     * @var string $file_tmp Dočasná cesta k nahranému souboru.
     * @var int $file_error Chybový kód nahrávání.
     * @var string $file_ext Přípona souboru.
     */
    $file = $_FILES['profile-photo'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_error = $file['error'];

    // Získání přípony souboru
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    /**
     * @var array $allowed Povolené typy souborů.
     */
    $allowed = array('jpg', 'jpeg', 'png');

    // Kontrola, zda je typ souboru povolen
    if (in_array($file_ext, $allowed)) {
        // Kontrola, zda nenastala chyba při nahrávání
        if ($file_error === 0) {
            
            // Generování unikátního názvu souboru
            $new_file_name = "profile_" . $user_id . "_" . uniqid() . "." . $file_ext;
            $file_destination = $target_dir . $new_file_name;

            // --- ZAČÁTEK ZMENŠOVÁNÍ OBRÁZKU (GD Library) ---
            
            // 1. Získání původních rozměrů a typu obrázku
            list($width_orig, $height_orig) = getimagesize($file_tmp);

            // 2. Nastavení cílové velikosti (Max šířka 300px)
            /**
             * @var int $target_width Cílová šířka obrázku.
             * @var float $ratio Poměr šířky a výšky původního obrázku.
             * @var int $target_height Cílová výška obrázku vypočítaná na základě poměru.
             */
            $target_width = 300;
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

            // 5. Zmenšení obrázku (Resampling)
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

            // Aktualizace cesty k profilové fotografii v databázi
            $stmt = $conn->prepare("UPDATE users SET profilovka_cesta = ? WHERE id = ?");
            $stmt->bind_param("si", $file_destination, $user_id);
            
            if ($stmt->execute()) {
                // Přesměrování na profilovou stránku s úspěšnou zprávou
                header("Location: profile.php?upload=success");
            } else {
                echo "Chyba při ukládání do databáze.";
            }
        } else {
            echo "Nastala chyba při nahrávání souboru.";
        }
    } else {
        echo "Tento typ souboru není povolen (pouze JPG, JPEG, PNG).";
    }
} else {
    // Pokud nebyl soubor odeslán, přesměruj zpět na profil
    header("Location: profile.php");
}
?>