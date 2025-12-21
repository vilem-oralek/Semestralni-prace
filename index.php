<?php
/**
 * @file index.php
 * Hlavní stránka webu.
 * Tento soubor obsahuje logiku pro načítání obrázků do sekce Galerie
 * a jejich zobrazení na hlavní stránce.
 */

/**
 * @var string $dir Cesta ke složce s obrázky pro galerii.
 */
$dir = "uploads/index/";
/**
 * @var array $index_images Pole obsahující názvy obrázků načtených ze složky.
 * Obrázky jsou filtrovány podle přípon .jpg, .jpeg, nebo .png.
 */
$index_images = [];

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
     * Obrázky jsou přidány do pole $index_images.
     */
    foreach ($scan as $file) {
        if ($file !== '.' && $file !== '..' && preg_match('/\.(jpg|jpeg|png)$/i', $file)) {
            $index_images[] = $file;
        }
    }
}

/**
 * Výběr prvních čtyř obrázků pro zobrazení v galerii.
 * Pokud je ve složce více obrázků, zobrazí se pouze první čtyři.
 * 
 * @var array $index_images Aktualizované pole obsahující maximálně 4 obrázky.
 */
$index_images = array_slice($index_images, 0, 4);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vilémův strejda</title>
    <link rel="stylesheet" href="style.css">
    <script>
      fetch("header.php")
        .then(response => response.text())
        .then(data => {
          document.getElementById("index-header-placeholder").innerHTML = data;
        });
    </script>
</head>
<body>
  <div class="index-background-image">
    <div id="index-header-placeholder"></div>
    <section class="hero">
      <h1>Vítejte u Vilémova strejdy</h1>
      <p>Zažijte nezapomenutelný pobyt v srdci přírody.</p>
      <a href="#about" class="btn-primary">Zjistit více</a>
    </section>
  </div>
  
  <div class="index-content">
    
    <section id="about" class="about reveal">
      <div class="about-content">
        <h2>O nás</h2>
        <p>Vilémův strejda je místo, kde se příroda setkává s pohodlím. Nabízíme ubytování, skvělé jídlo a aktivity pro celou rodinu. Přijeďte si odpočinout od shonu velkoměsta a načerpat novou energii v malebném prostředí.</p>
      </div>
      <div class="about-image">
        <?php if (!empty($index_images)): ?>
            <img src="thumbnail.php?img=<?php echo $index_images[0]; ?>&dir=index" alt="O nás">
        <?php else: ?>
            <img src="placeholderimg.jpg" alt="O nás - placeholder" onerror="this.style.display='none'">
        <?php endif; ?>
      </div>
    </section>

    <section class="gallery reveal">
      <h2>Galerie</h2>
      <a href = "galerie.php">
      <div class="gallery-grid">
        
        <?php if (!empty($index_images)): ?>
            <?php foreach ($index_images as $img): ?>
                <img src="thumbnail.php?img=<?php echo $img; ?>&dir=index" alt="Náhled ubytování">
            <?php endforeach; ?>
        <?php else: ?>
            <p id = "p-index-style" >Zatím zde nejsou žádné obrázky.</p>
        <?php endif; ?>
      </div>
      </a>
    </section>

    <section class="contact reveal">
      <h2>Kontakt</h2>
      <p>Máte otázky? Kontaktujte nás na <a href="mailto:info@vilemuvstrejda.cz">info@vilemuvstrejda.cz</a> nebo volejte na <a href="tel:+420123456789">+420 123 456 789</a>.</p>
    </section>
  <?php include 'footer.html'; ?>
  </div> 
  <script src="menu.js"></script>
</body>
</html>