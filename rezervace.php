<?php
session_start();
include 'conn.php';
include 'calculate_price_logic.php'; // Načteme logiku výpočtu ceny

$is_logged_in = isset($_SESSION['user_id']);
$pre_data = [];

// 1. Pokud je uživatel přihlášen, načteme jeho data pro předvyplnění formuláře
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT jmeno, prijmeni, email, telefon FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $pre_data = $res->fetch_assoc();
    }
}

// 2. ZPRACOVÁNÍ REZERVACE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    $user_id = $_SESSION['user_id'];
    
    // Data z formuláře
    $start = $_POST['datum_prijezdu'];
    $end = $_POST['datum_odjezdu'];
    $poznamka = $_POST['poznamka'];
    
    // Kontaktní údaje (zapisujeme do sloupců jmeno, prijmeni... nikoliv host_jmeno)
    $jmeno = $_POST['jmeno'];
    $prijmeni = $_POST['prijmeni'];
    $email = $_POST['email'];
    $telefon = $_POST['telefon'];

    // Validace dostupnosti
    if (!isTermAvailable($conn, $start, $end)) {
        echo "<script>alert('Chyba: Termín je již obsazen!'); window.location.href='rezervace.php';</script>";
        exit;
    }

    // Výpočet ceny
    $final_price = calculateTotalPrice($conn, $start, $end);

    // Uložení do DB - OPRAVEN NÁZEV SLOUPCŮ
    $stmt = $conn->prepare("INSERT INTO reservations (user_id, datum_prijezdu, datum_odjezdu, celkova_cena, poznamka, jmeno, prijmeni, email, telefon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdsssss", $user_id, $start, $end, $final_price, $poznamka, $jmeno, $prijmeni, $email, $telefon);
    
    if ($stmt->execute()) {
        echo "<script>alert('Rezervace byla úspěšně vytvořena!'); window.location.href='profile.php';</script>";
        exit;
    } else {
        echo "<script>alert('Chyba při ukládání: " . $conn->error . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervace</title>
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
    
    <section class="reservation-hero">
        <div class="background-image"></div>
        
        <div id="rezervace">
            <h2>Nová Rezervace</h2>
            
            <?php if ($is_logged_in): ?>
                <p class="popis-text">Zkontrolujte údaje a vyberte termín.</p>

                <form id="rezervacni-formular" method="post" action="rezervace.php">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Jméno</label>
                            <input type="text" name="jmeno" value="<?php echo htmlspecialchars($pre_data['jmeno'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Příjmení</label>
                            <input type="text" name="prijmeni" value="<?php echo htmlspecialchars($pre_data['prijmeni'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($pre_data['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Telefon</label>
                            <input type="text" name="telefon" value="<?php echo htmlspecialchars($pre_data['telefon'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Datum příjezdu</label>
                            <input type="date" id="datum_prijezdu" name="datum_prijezdu" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Datum odjezdu</label>
                            <input type="date" id="datum_odjezdu" name="datum_odjezdu" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Poznámka (volitelné)</label>
                        <textarea name="poznamka" placeholder="Např. přistýlka pro dítě..."></textarea>
                    </div>

                    <div id="price-result" class="price-result-box"></div>
                    <div id="date-error" class="error-msg"></div>

                    <button type="submit" id="submit-btn" disabled>Vyberte termín</button>
                </form>

            <?php else: ?>
                <div class="not-logged-wrapper">
                    <p class="not-logged-text">Pro rezervaci se musíte přihlásit.</p>
                    <a href="login.html" class="login-button btn-link">Přihlásit se</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <p>&copy; 2023 Vilémův strejda. Všechna práva vyhrazena.</p>
    </footer>

    <script>
        const startInput = document.getElementById('datum_prijezdu');
        const endInput = document.getElementById('datum_odjezdu');
        const priceDisplay = document.getElementById('price-result');
        const errorDisplay = document.getElementById('date-error');
        const submitBtn = document.getElementById('submit-btn');

        function checkPrice() {
            const start = startInput.value;
            const end = endInput.value;

            if (start && end) {
                if (start >= end) {
                    errorDisplay.innerText = "Datum odjezdu musí být později než datum příjezdu.";
                    errorDisplay.classList.add('active');
                    priceDisplay.classList.remove('active');
                    submitBtn.disabled = true;
                    submitBtn.innerText = "Neplatný termín";
                    return;
                }

                fetch(`get_price_api.php?start=${start}&end=${end}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            errorDisplay.innerText = data.error;
                            errorDisplay.classList.add('active');
                            priceDisplay.classList.remove('active');
                            submitBtn.disabled = true;
                            submitBtn.innerText = "Termín obsazen";
                        } else {
                            errorDisplay.classList.remove('active');
                            // Přidána třída active pro zobrazení
                            priceDisplay.innerHTML = `Počet nocí: ${data.nights}<br>Celková cena: ${data.price} Kč`;
                            priceDisplay.classList.add('active');
                            submitBtn.disabled = false;
                            submitBtn.innerText = "Závazně rezervovat";
                        }
                    })
                    .catch(err => console.error("Chyba:", err));
            }
        }

        if(startInput && endInput) {
            startInput.addEventListener('change', checkPrice);
            endInput.addEventListener('change', checkPrice);
        }
    </script>
</body>
</html>