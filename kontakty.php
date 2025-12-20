<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Kontakty</title>
    <script>
      fetch("header.php")
        .then(response => response.text())
        .then(data => {
          document.getElementById("header-placeholder").innerHTML = data;
        });
    </script>
</head>

<body id="kontakty-body">
  <div id="header-placeholder"></div>
  <section class="hero-kontakty">
    <div class="background-image"></div>
    <main id="kontakty-page">
        <div id="kontakty-content">
          <section id="kontakty-mapa">
            <h2>Najdete nás zde</h2>
            <div id="kontakty-map-container">
              <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2563.123456789!2d14.123456789!3d48.123456789!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x123456789abcdef!2sModr%C3%BD%20Jelen%20Lipno!5e0!3m2!1scs!2scz!4v1234567890"
                allowfullscreen
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
              </iframe>
            </div>
          </section>
      
          <section id="kontakty-adresa">
            <h2>Naše adresa</h2>
            <div class="kontakty-info">
              <p><strong>Modrý Jelen Lipno</strong></p>
              <p>Adresa: Lipno nad Vltavou 123, 382 78</p>
              <p>Telefon: <a href="tel:+420123456789">+420 123 456 789</a></p>
              <p>Email: <a href="mailto:info@modryjelenlipno.cz">info@modryjelenlipno.cz</a></p>
            </div>
          </section>
        </div>
      </section>
      </main>
    <?php include 'footer.html'; ?>
  <script src="menu.js"></script>
</body>
</html>