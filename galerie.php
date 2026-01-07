<?php
/**
 * @file galerie.php
 * Stránka galerie s Lightboxem.
 */

function get_gallery_files($dir) {
    $files = [];
    if (is_dir($dir)) {
        $scan = scandir($dir);
        foreach ($scan as $file) {
            if ($file !== '.' && $file !== '..' && preg_match('/\.(jpg|jpeg|png)$/i', $file)) {
                $files[] = $file;
            }
        }
    }
    return $files;
}

function get_files_on_page($files, $page, $per_page) {
    $offset = ($page - 1) * $per_page;
    return array_slice($files, $offset, $per_page);
}

function get_total_pages($total_files, $per_page) {
    return (int)ceil($total_files / $per_page);
}

$dir = "uploads/Galerie/";
$files = get_gallery_files($dir);
$total_files = count($files);
$per_page = 8;
$total_pages = get_total_pages($total_files, $per_page);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
$files_on_page = get_files_on_page($files, $page, $per_page);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galerie - Vilémův strejda</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script>
      fetch("header.php")
        .then(response => response.text())
        .then(data => {
          document.getElementById("header-placeholder").innerHTML = data;
        });
    </script>
</head>
<body>
    <div id="header-placeholder"></div>

    <section class="gallery-hero">
        <div class="background-image"></div>
        <div class="gallery-content-wrapper">
            <h1>Naše Galerie</h1>
            <p>Prohlédněte si krásy našeho ubytování a okolí.</p>

            <div class="gallery-grid">
                <?php if (count($files_on_page) > 0): ?>
                    <?php foreach ($files_on_page as $img): ?>
                        <div class="gallery-item">
                            <a href="<?php echo $dir . $img; ?>" class="lightbox-trigger">
                                <img src="thumbnail.php?img=<?php echo $img; ?>" alt="<?php echo $img; ?>" loading="lazy">
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p id = "p-text">Zatím zde nejsou žádné fotografie.</p>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="gallery-pagination">
                <?php if ($page > 1): ?>
                    <a href="galerie.php?page=<?php echo $page - 1; ?>" class="page-link">&laquo; Předchozí</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="galerie.php?page=<?php echo $i; ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="galerie.php?page=<?php echo $page + 1; ?>" class="page-link">Další &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <div id="lightbox">
        <span id="lightbox-close">&times;</span>
        <img id="lightbox-content" src="" alt="Zvětšený obrázek">
    </div>

    <?php include 'footer.html'; ?>
    <script src="menu.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const lightbox = document.getElementById('lightbox');
            const lightboxImg = document.getElementById('lightbox-content');
            const closeBtn = document.getElementById('lightbox-close');
            const triggers = document.querySelectorAll('.lightbox-trigger');

            triggers.forEach(trigger => {
                trigger.addEventListener('click', function(e) {
                    e.preventDefault(); // Zabrání otevření odkazu v novém okně
                    lightbox.style.display = "flex";
                    lightboxImg.src = this.href; // Načte velký obrázek z href odkazu
                });
            });

            // Zavření křížkem
            closeBtn.addEventListener('click', function() {
                lightbox.style.display = "none";
            });

            // Zavření kliknutím mimo obrázek
            lightbox.addEventListener('click', function(e) {
                if (e.target === lightbox) {
                    lightbox.style.display = "none";
                }
            });
        });
    </script>
</body>
</html>