<?php
session_start();
include 'conn.php';
include 'calculate_price_logic.php';

function get_pre_data($conn, $user_id) {
    $stmt = $conn->prepare("SELECT jmeno, prijmeni, email, telefon FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) return $res->fetch_assoc();
    return [];
}

// Načtení obsazených termínů pro kalendář
$occupied_dates = [];
$res_query = $conn->query("SELECT datum_prijezdu, datum_odjezdu FROM reservations");
while ($row = $res_query->fetch_assoc()) {
    $occupied_dates[] = $row;
}

function process_reservation($conn) {
    $is_logged_in = isset($_SESSION['user_id']);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
        $user_id = $_SESSION['user_id'];
        $start = $_POST['datum_prijezdu'];
        $end = $_POST['datum_odjezdu'];
        $poznamka = trim($_POST['poznamka']);
        $jmeno = trim($_POST['jmeno']);
        $prijmeni = trim($_POST['prijmeni']);
        $email = trim($_POST['email']);
        $telefon = trim($_POST['telefon']);

        // Základní validace
        if ($start >= $end) {
            echo "<script>alert('Datum odjezdu musí být později než datum příjezdu.'); window.history.back();</script>";
            exit;
        }
        if (!isTermAvailable($conn, $start, $end)) {
            echo "<script>alert('Chyba: Termín je již obsazen!'); window.location.href='rezervace.php';</script>";
            exit;
        }

        $final_price = calculateTotalPrice($conn, $start, $end);
        $stmt = $conn->prepare("INSERT INTO reservations (user_id, datum_prijezdu, datum_odjezdu, celkova_cena, poznamka, jmeno, prijmeni, email, telefon) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdsssss", $user_id, $start, $end, $final_price, $poznamka, $jmeno, $prijmeni, $email, $telefon);
        
        if ($stmt->execute()) {
            echo "<script>alert('Rezervace byla úspěšně vytvořena!'); window.location.href='profile.php';</script>";
            exit;
        } else {
            echo "<script>alert('Chyba při ukládání: " . $conn->error . "');</script>";
        }
    }
}

$is_logged_in = isset($_SESSION['user_id']);
$pre_data = [];
if ($is_logged_in) {
    $pre_data = get_pre_data($conn, $_SESSION['user_id']);
}
process_reservation($conn);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervace</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script>
      fetch("header.php").then(r => r.text()).then(d => document.getElementById("header-placeholder").innerHTML = d);
    </script>
</head>
<body>
    <div id="header-placeholder"></div>
    
    <section class="reservation-hero">
        <div class="background-image"></div>
        
        <div id="rezervace">
            <h2>Nová Rezervace</h2>
            
            <?php if ($is_logged_in): ?>
                <p class="popis-text">Klikněte do kalendáře pro výběr data příjezdu a odjezdu.</p>


                <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 16px;">
                    <button type="button" id="open-range-calendar" class="calendar-btn calendar-btn-big" style="margin: 0 auto;">Vyberte termín pobytu</button>
                    <div id="range-label" class="calendar-range-label" style="margin-top: 8px;"></div>
                </div>
                                <div class="calendar-overlay" id="calendar-overlay" style="display:none;">
                                    <div class="calendar-wrapper" id="booking-calendar"></div>
                                </div>

                <form id="rezervacni-formular" method="post" action="rezervace.php">
                    <input type="hidden" id="datum_prijezdu" name="datum_prijezdu" required>
                    <input type="hidden" id="datum_odjezdu" name="datum_odjezdu" required>
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

                    <div class="form-group">
                        <label>Poznámka (volitelné)</label>
                        <textarea name="poznamka" placeholder="Např. přistýlka pro dítě..."></textarea>
                    </div>

                    <div id="price-result" class="price-result-box"></div>
                    
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
    <?php include 'footer.html'; ?>
    <script src="menu.js"></script>
    <script>
    // --- Calendar logic wrapped in function and grid defined locally ---
    // Data from PHP
    const occupiedData = <?php echo json_encode($occupied_dates); ?>;
    let selectedStart = null;
    let selectedEnd = null;
    let currentDate = new Date();

    function renderCalendar(year, month) {
        const calendarContainer = document.getElementById('booking-calendar');
        calendarContainer.innerHTML = '';

        // Calendar header with month navigation
        const header = document.createElement('div');
        header.className = 'calendar-header';

        const prevBtn = document.createElement('button');
        prevBtn.textContent = '<';
        prevBtn.onclick = function() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
        };

        const nextBtn = document.createElement('button');
        nextBtn.textContent = '>';
        nextBtn.onclick = function() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
        };

        const monthLabel = document.createElement('span');
        monthLabel.textContent = `${year} / ${String(month+1).padStart(2,'0')}`;
        monthLabel.style.fontWeight = 'bold';
        monthLabel.style.fontSize = '1.1em';

        header.appendChild(prevBtn);
        header.appendChild(monthLabel);
        header.appendChild(nextBtn);
        calendarContainer.appendChild(header);

        const grid = document.createElement('div');
        grid.className = 'calendar-grid';

        // Názvy dnů
        const daysShortCZ = ['Po', 'Út', 'St', 'Čt', 'Pá', 'So', 'Ne'];
        daysShortCZ.forEach(d => {
            const dayName = document.createElement('div');
            dayName.className = 'calendar-day-name';
            dayName.innerText = d;
            grid.appendChild(dayName);
        });

        // První den v měsíci
        const firstDay = new Date(year, month, 1).getDay(); // 0 = Ne, 1 = Po...
        // Přepočet na Po=0, Ne=6
        const startOffset = (firstDay === 0) ? 6 : firstDay - 1;
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Prázdná políčka před začátkem
        for(let i=0; i < startOffset; i++) {
            const empty = document.createElement('div');
            empty.className = 'calendar-day empty';
            grid.appendChild(empty);
        }

        // Dny
        for(let d=1; d <= daysInMonth; d++) {
            const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            const dayCell = document.createElement('div');
            dayCell.className = 'calendar-day';
            dayCell.innerText = d;

            // Kontrola obsazenosti
            let isOccupied = false;
            occupiedData.forEach(res => {
                if (dateStr >= res.datum_prijezdu && dateStr < res.datum_odjezdu) {
                    isOccupied = true;
                }
            });
            // Kontrola minulosti
            const todayStr = new Date().toISOString().split('T')[0];
            if (dateStr < todayStr) isOccupied = true;

            if (isOccupied) {
                dayCell.classList.add('occupied');
            } else {
                dayCell.onclick = () => handleDateClick(dateStr);
            }

            // Vizuální označení výběru
            if (dateStr === selectedStart) dayCell.classList.add('selected');
            if (dateStr === selectedEnd) dayCell.classList.add('selected');
            if (selectedStart && selectedEnd && dateStr > selectedStart && dateStr < selectedEnd) {
                dayCell.classList.add('in-range');
            }

            grid.appendChild(dayCell);
        }
        calendarContainer.appendChild(grid);
    }

    function handleDateClick(dateStr) {
        // Reset pokud už máme vybráno obojí nebo klikneme před start
        if ((selectedStart && selectedEnd) || (selectedStart && dateStr < selectedStart)) {
            selectedStart = dateStr;
            selectedEnd = null;
        } else if (!selectedStart) {
            selectedStart = dateStr;
        } else {
            // Kontrola, zda mezi start a end není obsazeno
            let collision = false;
            occupiedData.forEach(res => {
                // Pokud rezervace zasahuje do vybraného intervalu
                if ((res.datum_prijezdu >= selectedStart && res.datum_prijezdu < dateStr) || 
                    (res.datum_prijezdu <= selectedStart && res.datum_odjezdu > selectedStart)) {
                        collision = true;
                }
            });
            if (collision) {
                alert("Ve vybraném rozmezí je obsazený termín.");
                selectedStart = dateStr;
                selectedEnd = null;
            } else {
                selectedEnd = dateStr;
            }
        }
        updateInputs();
        renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
    }

    function updateInputs() {
        document.getElementById('datum_prijezdu').value = selectedStart || '';
        document.getElementById('datum_odjezdu').value = selectedEnd || '';
        if (selectedStart && selectedEnd) {
            checkPrice(selectedStart, selectedEnd);
            // Close calendar overlay when both dates are selected
            const overlay = document.getElementById('calendar-overlay');
            if (overlay) overlay.style.display = 'none';
        } else {
            document.getElementById('submit-btn').disabled = true;
            document.getElementById('submit-btn').innerText = "Vyberte termín";
            document.getElementById('price-result').innerHTML = "";
            document.getElementById('price-result').classList.remove('active');
        }
    }

    function checkPrice(start, end) {
        fetch(`get_price_api.php?start=${start}&end=${end}`)
            .then(response => response.json())
            .then(data => {
                const btn = document.getElementById('submit-btn');
                const priceBox = document.getElementById('price-result');
                if (data.error) {
                    priceBox.innerHTML = `<span style=\"color:red\">${data.error}</span>`;
                    priceBox.classList.add('active');
                    btn.disabled = true;
                } else {
                    priceBox.innerHTML = `Počet nocí: ${data.nights}<br>Celková cena: ${data.price} Kč`;
                    priceBox.classList.add('active');
                    btn.disabled = false;
                    btn.innerText = "Závazně rezervovat";
                }
            });
    }

    // --- Initialization ---
    document.addEventListener("DOMContentLoaded", function() {
        const calendarWrapper = document.getElementById('booking-calendar');
        const openRangeBtn = document.getElementById('open-range-calendar');
        const rangeLabel = document.getElementById('range-label');
        let calendarVisible = false;
        const overlay = document.getElementById('calendar-overlay');

        function updateRangeLabel() {
            function formatDateDMY(dateStr) {
                if (!dateStr) return '';
                const [y, m, d] = dateStr.split('-');
                return `${d}.${m}.${y}`;
            }
            if (selectedStart && selectedEnd) {
                rangeLabel.textContent = `Vybráno: od ${formatDateDMY(selectedStart)} do ${formatDateDMY(selectedEnd)}`;
            } else if (selectedStart) {
                rangeLabel.textContent = `Vyberte datum odjezdu po ${formatDateDMY(selectedStart)}`;
            } else {
                rangeLabel.textContent = 'Nevybrán žádný termín.';
            }
        }

        openRangeBtn.addEventListener('click', function() {
            overlay.style.display = (overlay.style.display === 'flex') ? 'none' : 'flex';
        });

        // Close calendar when clicking outside the calendar-wrapper
        overlay.addEventListener('mousedown', function(e) {
            if (e.target === overlay) {
                calendarVisible = false;
                overlay.style.display = 'none';
            }
        });

        // Update label after each selection
        const origUpdateInputs = updateInputs;
        updateInputs = function() {
            origUpdateInputs();
            updateRangeLabel();
        };

        renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
        updateRangeLabel();
    });
    </script>
</body>
</html>