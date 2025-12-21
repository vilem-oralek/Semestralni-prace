<?php
/**
 * @file upload_profile_photo.php
 * Skript pro nahrávání a zpracování profilové fotografie uživatele.
 * Tento soubor kontroluje přihlášení uživatele, validuje nahraný soubor,
 * zmenšuje obrázek na maximální šířku 300px a ukládá cestu k obrázku do databáze.
 */


session_start();
include 'conn.php';

/**
 * Zpracuje nahrání a zmenšení profilové fotky uživatele, uloží cestu do DB.
 *
 * @global mysqli $conn
 * @return void
 */
function process_profile_photo_upload() {
    global $conn;
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.html");
        exit;
    }
    $user_id = $_SESSION['user_id'];
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile-photo'])) {
        $file = $_FILES['profile-photo'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_error = $file['error'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = array('jpg', 'jpeg', 'png');
        if (in_array($file_ext, $allowed)) {
            if ($file_error === 0) {
                $new_file_name = "profile_" . $user_id . "_" . uniqid() . "." . $file_ext;
                $file_destination = $target_dir . $new_file_name;
                list($width_orig, $height_orig) = getimagesize($file_tmp);
                $target_width = 300;
                $ratio = $width_orig / $height_orig;
                $target_height = $target_width / $ratio;
                $image_p = imagecreatetruecolor($target_width, $target_height);
                if ($file_ext == 'jpg' || $file_ext == 'jpeg') {
                    $image = imagecreatefromjpeg($file_tmp);
                } elseif ($file_ext == 'png') {
                    $image = imagecreatefrompng($file_tmp);
                    imagealphablending($image_p, false);
                    imagesavealpha($image_p, true);
                }
                imagecopyresampled($image_p, $image, 0, 0, 0, 0, $target_width, $target_height, $width_orig, $height_orig);
                if ($file_ext == 'jpg' || $file_ext == 'jpeg') {
                    imagejpeg($image_p, $file_destination, 90);
                } elseif ($file_ext == 'png') {
                    imagepng($image_p, $file_destination, 9);
                }
                imagedestroy($image_p);
                imagedestroy($image);
                $stmt = $conn->prepare("UPDATE users SET profilovka_cesta = ? WHERE id = ?");
                $stmt->bind_param("si", $file_destination, $user_id);
                if ($stmt->execute()) {
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
        header("Location: profile.php");
    }
}

process_profile_photo_upload();
?>