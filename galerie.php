<?php
/**
 * @file galerie.php
 * Stránka galerie pro zobrazení obrázků z dané složky s podporou stránkování.
 * Tento soubor načítá obrázky ze složky, filtruje je podle přípon a zobrazuje
 * je na stránce s možností přecházení mezi stránkami.
 */

 /**
 * @var string $dir Cesta ke složce s obrázky pro galerii.
 */
$dir = "uploads/Galerie/";

/**
 * @var array $files Pole obsahující názvy všech obrázků načtených ze složky.
 */
$files = [];

/**
 * Kontrola, zda složka s obrázky existuje.
 * Pokud složka existuje, načtou se všechny soubory ve složce.
 * 
 * @return void
 */
if (is_dir($dir)) {
    $scan = scandir($dir); // Načtení obsahu složky

    /**
     * Procházení všech souborů ve složce a filtrování pouze obrázků.
     * Obrázky jsou přidány do pole $files.
     */
    foreach ($scan as $file) {
        // Filtrujeme jen obrázky (jpg, png) a ignorujeme tečky
        if ($file !== '.' && $file !== '..' && preg_match('/\.(jpg|jpeg|png)$/i', $file)) {
            $files[] = $file;
        }
    }
}

/**
 * @var int $total_files Celkový počet obrázků ve složce.
 */
$total_files = count($files);
/**
 * @var int $per_page Počet obrázků zobrazených na jedné stránce.
 */
$per_page = 8;

/**
 * @var int $total_pages Celkový počet stránek vypočítaný na základě počtu obrázků a počtu obrázků na stránku.
 */
$total_pages = ceil($total_files / $per_page);

/**
 * Zjištění aktuální stránky z parametru GET.
 * Pokud není stránka nastavena, použije se výchozí hodnota 1.
 * 
 * @var int $page Aktuální stránka.
 */
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

/**
 * @var int $offset Výpočet offsetu pro načtení obrázků na aktuální stránku.
 */
$offset = ($page - 1) * $per_page;

/**
 * @var array $files_on_page Pole obsahující obrázky pro aktuální stránku.
 */
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