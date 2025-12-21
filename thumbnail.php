<?php
/**
 * @file thumbnail.php
 * Skript pro generování náhledů obrázků.
 * Tento soubor přijímá parametry `img` (název obrázku) a `dir` (složka),
 * zmenšuje obrázek na cílovou šířku a vrací jej jako výstup.
 * 
 * Volání: thumbnail.php?img=nazev.jpg&dir=index (nebo bez dir pro Galerii).
 */

/**
 * @var array $allowed_folders Pole povolených složek, ze kterých lze načítat obrázky.
 */
$allowed_folders = ['Galerie', 'index'];
/**
 * @var string $folder_param Název složky z parametru `dir`. Pokud není zadán, použije se výchozí složka `Galerie`.
 */
$folder_param = isset($_GET['dir']) ? $_GET['dir'] : 'Galerie';

// Kontrola, zda je složka povolena
if (!in_array($folder_param, $allowed_folders)) {
    $folder_param = 'Galerie';
}

/**
 * @var string $base_dir Cesta ke složce s obrázky.
 */
$base_dir = "uploads/" . $folder_param . "/";
/**
 * @var string $image_name Název obrázku z parametru `img`.
 */
$image_name = isset($_GET['img']) ? basename($_GET['img']) : ''; 
/**
 * @var string $source_path Plná cesta k obrázku.
 */
$source_path = $base_dir . $image_name;

// Kontrola, zda obrázek existuje
if (!file_exists($source_path) || empty($image_name)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

/**
 * @var array|false $image_info Informace o obrázku získané pomocí `getimagesize`.
 * Pokud se nepodaří načíst informace, skript se ukončí.
 */
$image_info = getimagesize($source_path);
if ($image_info === false) exit;

/**
 * @var int $width_orig Původní šířka obrázku.
 * @var int $height_orig Původní výška obrázku.
 * @var string $mime_type MIME typ obrázku (např. `image/jpeg` nebo `image/png`).
 */
$width_orig = $image_info[0];
$height_orig = $image_info[1];
$mime_type = $image_info['mime'];

/**
 * @var int $target_width Cílová šířka obrázku (např. 400px).
 * @var float $ratio Poměr šířky a výšky původního obrázku.
 * @var int $target_height Cílová výška obrázku vypočítaná na základě poměru.
 */
$target_width = 400;
$ratio = $width_orig / $height_orig;
$target_height = $target_width / $ratio;

/**
 * @var resource $image_p Nový obrázek vytvořený pro zmenšení.
 */
$image_p = imagecreatetruecolor($target_width, $target_height);

/**
 * Načtení obrázku podle jeho MIME typu.
 * Podporované typy: JPEG a PNG.
 * 
 * @var resource $image Zdrojový obrázek.
 */
switch ($mime_type) {
    case 'image/jpeg':
        $image = imagecreatefromjpeg($source_path);
        break;
    case 'image/png':
        $image = imagecreatefrompng($source_path);
        imagealphablending($image_p, false);
        imagesavealpha($image_p, true);
        break;
    default:
        exit; // Nepodporovaný typ obrázku
}

// Zmenšení obrázku
imagecopyresampled($image_p, $image, 0, 0, 0, 0, $target_width, $target_height, $width_orig, $height_orig);

// Nastavení hlavičky pro výstup obrázku
header("Content-Type: " . $mime_type);

// Výstup obrázku podle jeho typu
if ($mime_type == 'image/jpeg') {
    imagejpeg($image_p, null, 85); // JPEG s kvalitou 85
} elseif ($mime_type == 'image/png') {
    imagepng($image_p, null, 9); // PNG s kompresí 9
}

// Uvolnění paměti
imagedestroy($image_p);
imagedestroy($image);
?>