<?php
// Tento skript slouží k dynamickému generování zmenšenin (thumbnails)
// Volá se jako: thumbnail.php?img=nazev_souboru.jpg

$base_dir = "uploads/Galerie/"; // Cesta k obrázkům
$image_name = isset($_GET['img']) ? basename($_GET['img']) : ''; // basename pro bezpečnost
$source_path = $base_dir . $image_name;

// Pokud obrázek neexistuje, nic nevypisujeme (nebo bychom mohli vrátit placeholder)
if (!file_exists($source_path) || empty($image_name)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Zjištění informací o obrázku
$image_info = getimagesize($source_path);
if ($image_info === false) {
    exit; // Není to obrázek
}

$width_orig = $image_info[0];
$height_orig = $image_info[1];
$mime_type = $image_info['mime'];

// Nastavení maximální šířky náhledu (např. 400px)
$target_width = 400;

// Výpočet nové výšky se zachováním poměru
$ratio = $width_orig / $height_orig;
$target_height = $target_width / $ratio;

// Vytvoření cílového obrázku v paměti
$image_p = imagecreatetruecolor($target_width, $target_height);

// Načtení zdroje podle typu
switch ($mime_type) {
    case 'image/jpeg':
        $image = imagecreatefromjpeg($source_path);
        break;
    case 'image/png':
        $image = imagecreatefrompng($source_path);
        // Zachování průhlednosti u PNG
        imagealphablending($image_p, false);
        imagesavealpha($image_p, true);
        break;
    default:
        exit; // Podporujeme jen JPG a PNG
}

// Zmenšení (Resampling)
imagecopyresampled($image_p, $image, 0, 0, 0, 0, $target_width, $target_height, $width_orig, $height_orig);

// Odeslání hlavičky a obrázku do prohlížeče
header("Content-Type: " . $mime_type);

if ($mime_type == 'image/jpeg') {
    imagejpeg($image_p, null, 85); // Kvalita 85
} elseif ($mime_type == 'image/png') {
    imagepng($image_p, null, 9);
}

// Úklid paměti
imagedestroy($image_p);
imagedestroy($image);
?>