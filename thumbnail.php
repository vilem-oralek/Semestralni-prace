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
 * Vygeneruje a odešle náhled obrázku na základě parametrů v URL.
 * Podporuje složky Galerie a index, typy JPEG a PNG.
 *
 * @return void
 */
function generate_thumbnail() {
    $allowed_folders = ['Galerie', 'index'];
    $folder_param = isset($_GET['dir']) ? $_GET['dir'] : 'Galerie';
    if (!in_array($folder_param, $allowed_folders)) {
        $folder_param = 'Galerie';
    }
    $base_dir = "uploads/" . $folder_param . "/";
    $image_name = isset($_GET['img']) ? basename($_GET['img']) : '';
    $source_path = $base_dir . $image_name;
    if (!file_exists($source_path) || empty($image_name)) {
        header("HTTP/1.0 404 Not Found");
        exit;
    }
    $image_info = getimagesize($source_path);
    if ($image_info === false) exit;
    $width_orig = $image_info[0];
    $height_orig = $image_info[1];
    $mime_type = $image_info['mime'];
    $target_width = 400;
    $ratio = $width_orig / $height_orig;
    $target_height = $target_width / $ratio;
    $image_p = imagecreatetruecolor($target_width, $target_height);
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
            exit;
    }
    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $target_width, $target_height, $width_orig, $height_orig);
    header("Content-Type: " . $mime_type);
    if ($mime_type == 'image/jpeg') {
        imagejpeg($image_p, null, 85);
    } elseif ($mime_type == 'image/png') {
        imagepng($image_p, null, 9);
    }
    imagedestroy($image_p);
    imagedestroy($image);
}

generate_thumbnail();
?>