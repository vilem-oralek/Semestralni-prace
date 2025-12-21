<?php
// Nastavení složky s obrázky
$dir = "uploads/Galerie/";

// Získání všech souborů ze složky
$files = [];
if (is_dir($dir)) {
    $scan = scandir($dir);
    foreach ($scan as $file) {
        // Filtrujeme jen obrázky (jpg, png) a ignorujeme tečky
        if ($file !== '.' && $file !== '..' && preg_match('/\.(jpg|jpeg|png)$/i', $file)) {
            $files[] = $file;
        }
    }
}

// LOGIKA STRÁNKOVÁNÍ
// Necháme 8, to je dobré číslo (na desktopu 2 řádky po 4, na tabletu 4 řádky po 2)
$total_files = count($files);
$per_page = 8; 
$total_pages = ceil($total_files / $per_page);

// Zjištění aktuální stránky
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

// Výpočet offsetu
$offset = ($page - 1) * $per_page;

// Získání obrázků pro aktuální stránku
$files_on_page = array_slice($files, $offset, $per_page);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galerie - Vilémův strejda</title>
    <link rel="stylesheet" href="style.css">
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
                            <a href="<?php echo $dir . $img; ?>" target="_blank">
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

    <?php include 'footer.html'; ?>
    <script src="menu.js"></script>
</body>
</html>