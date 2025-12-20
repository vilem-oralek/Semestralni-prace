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
    $poznamka = trim($_POST['poznamka']);
    $jmeno = trim($_POST['jmeno']);
    $prijmeni = trim($_POST['prijmeni']);
    $email = trim($_POST['email']);
    $telefon = trim($_POST['telefon']);

    // Sanitize inputs
    $poznamka = htmlspecialchars($poznamka, ENT_QUOTES, 'UTF-8');
    $jmeno = htmlspecialchars($jmeno, ENT_QUOTES, 'UTF-8');
    $prijmeni = htmlspecialchars($prijmeni, ENT_QUOTES, 'UTF-8');
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $telefon = htmlspecialchars($telefon, ENT_QUOTES, 'UTF-8');

    // Validate "poznámka" length
    if (strlen($poznamka) > 50) {
        echo "<script>alert('Poznámka nesmí být delší než 50 znaků.'); window.history.back();</script>";
        exit;
    }

    // Validate other fields (e.g., name, surname, email, phone, dates)
    if (strlen($jmeno) < 3 || strlen($prijmeni) < 3) {
        echo "<script>alert('Jméno a příjmení musí mít alespoň 3 znaky.'); window.history.back();</script>";
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Neplatný formát e-mailu.'); window.history.back();</script>";
        exit;
    }

    if (!preg_match('/^\d{9}$/', $telefon)) {
        echo "<script>alert('Telefonní číslo musí obsahovat přesně 9 číslic.'); window.history.back();</script>";
        exit;
    }

    if ($start >= $end) {
        echo "<script>alert('Datum odjezdu musí být později než datum příjezdu.'); window.history.back();</script>";
        exit;
    }

    // Validate availability
    if (!isTermAvailable($conn, $start, $end)) {
        echo "<script>alert('Chyba: Termín je již obsazen!'); window.location.href='rezervace.php';</script>";
        exit;
    }

    // Calculate price
    $final_price = calculateTotalPrice($conn, $start, $end);

    // Insert reservation into the database
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
                        <label>Poznámka (volitelné, max. 50 znaků)</label>
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
        document.addEventListener("DOMContentLoaded", function () {
            const startInput = document.getElementById('datum_prijezdu');
            const endInput = document.getElementById('datum_odjezdu');
            const nameInput = document.querySelector('input[name="jmeno"]');
            const surnameInput = document.querySelector('input[name="prijmeni"]');
            const emailInput = document.querySelector('input[name="email"]');
            const phoneInput = document.querySelector('input[name="telefon"]');
            const noteInput = document.querySelector('textarea[name="poznamka"]');
            const priceDisplay = document.getElementById('price-result');
            const errorDisplay = document.getElementById('date-error');
            const submitBtn = document.getElementById('submit-btn');

            // Validate name
            nameInput.addEventListener("blur", function () {
                const nameValue = nameInput.value.trim();
                if (nameValue.length < 3) {
                    nameInput.style.border = "solid red 3px";
                } else {
                    nameInput.style.border = "solid black 1px";
                }
                validateForm();
            });

            // Validate surname
            surnameInput.addEventListener("blur", function () {
                const surnameValue = surnameInput.value.trim();
                if (surnameValue.length < 3) {
                    surnameInput.style.border = "solid red 3px";
                } else {
                    surnameInput.style.border = "solid black 1px";
                }
                validateForm();
            });

            // Validate email
            emailInput.addEventListener("blur", function () {
                const emailValue = emailInput.value.trim();
                if (emailValue.length < 6 || !emailValue.includes("@")) {
                    emailInput.style.border = "solid red 3px";
                } else {
                    emailInput.style.border = "solid black 1px";
                }
                validateForm();
            });

            // Validate phone number
            phoneInput.addEventListener("blur", function () {
                const phoneValue = phoneInput.value.trim();
                if (!/^\d{9}$/.test(phoneValue)) {
                    phoneInput.style.border = "solid red 3px";
                } else {
                    phoneInput.style.border = "solid black 1px";
                }
                validateForm();
            });

            // Validate "poznámka" length
            noteInput.addEventListener("input", function () {
                if (noteInput.value.length > 50) {
                    noteInput.style.border = "solid red 3px";
                } else {
                    noteInput.style.border = "solid black 1px";
                }
            });

            // Validate arrival and departure dates
            function validateDates() {
                const start = startInput.value;
                const end = endInput.value;

                if (!start) {
                    startInput.style.border = "solid red 3px";
                    errorDisplay.innerText = "Datum příjezdu je povinné.";
                    errorDisplay.classList.add('active');
                    submitBtn.disabled = true;
                    return false;
                } else {
                    startInput.style.border = "solid black 1px";
                }

                if (!end) {
                    endInput.style.border = "solid red 3px";
                    errorDisplay.innerText = "Datum odjezdu je povinné.";
                    errorDisplay.classList.add('active');
                    submitBtn.disabled = true;
                    return false;
                } else {
                    endInput.style.border = "solid black 1px";
                }

                if (start >= end) {
                    errorDisplay.innerText = "Datum odjezdu musí být později než datum příjezdu.";
                    errorDisplay.classList.add('active');
                    startInput.style.border = "solid red 3px";
                    endInput.style.border = "solid red 3px";
                    submitBtn.disabled = true;
                    return false;
                } else {
                    errorDisplay.classList.remove('active');
                    startInput.style.border = "solid black 1px";
                    endInput.style.border = "solid black 1px";
                }

                return true;
            }

            // Update the "min" attribute of the departure date based on the arrival date
            startInput.addEventListener('change', function () {
                const startDate = startInput.value;
                if (startDate) {
                    endInput.setAttribute('min', startDate);

                    // Clear the departure date if it's earlier than the new arrival date
                    if (endInput.value && endInput.value < startDate) {
                        endInput.value = '';
                        submitBtn.disabled = true;
                        priceDisplay.classList.remove('active');
                    }
                }
                validateDates();
                validateForm();
            });

            // Validate departure date on change
            endInput.addEventListener('change', function () {
                validateDates();
                validateForm();
            });

            // Check the price when both dates are valid
            function checkPrice() {
                if (validateDates()) {
                    const start = startInput.value;
                    const end = endInput.value;

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
                                priceDisplay.innerHTML = `Počet nocí: ${data.nights}<br>Celková cena: ${data.price} Kč`;
                                priceDisplay.classList.add('active');
                                submitBtn.disabled = false;
                                submitBtn.innerText = "Závazně rezervovat";
                            }
                        })
                        .catch(err => console.error("Chyba:", err));
                }
            }

            // Add event listeners to check the price when dates are selected
            startInput.addEventListener('change', checkPrice);
            endInput.addEventListener('change', checkPrice);

            // Validate the entire form
            function validateForm() {
                const nameValue = nameInput.value.trim();
                const surnameValue = surnameInput.value.trim();
                const emailValue = emailInput.value.trim();
                const phoneValue = phoneInput.value.trim();

                if (
                    nameValue.length >= 3 &&
                    surnameValue.length >= 3 &&
                    emailValue.length >= 6 &&
                    emailValue.includes("@") &&
                    /^\d{9}$/.test(phoneValue) &&
                    validateDates()
                ) {
                    submitBtn.disabled = false;
                } else {
                    submitBtn.disabled = true;
                }
            }

            // Prevent form submission if fields are invalid
            const form = document.getElementById('rezervacni-formular');
            form.addEventListener('submit', function (event) {
                if (submitBtn.disabled) {
                    alert("Formulář obsahuje chyby. Opravte je prosím před odesláním.");
                    event.preventDefault();
                }

                if (noteInput.value.length > 50) {
                    alert("Poznámka nesmí být delší než 50 znaků.");
                    noteInput.style.border = "solid red 3px";
                    event.preventDefault();
                }
            });

            // Validate "poznámka" length in real-time
            noteInput.addEventListener("input", function () {
                if (noteInput.value.length > 50) {
                    noteInput.style.border = "solid red 3px";
                    submitBtn.disabled = true;
                } else {
                    noteInput.style.border = "solid black 1px";
                    submitBtn.disabled = false;
                }
            });

            // Prevent form submission if "poznámka" exceeds 50 characters
            form.addEventListener('submit', function (event) {
                if (noteInput.value.length > 50) {
                    alert("Poznámka nesmí být delší než 50 znaků.");
                    noteInput.style.border = "solid red 3px";
                    event.preventDefault();
                }
            });
        });
    </script>
    <script src="menu.js"></script>
</body>
</html>