<?php
session_start();
include 'conn.php';

function require_admin_access() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: login.html");
        exit;
    }
}

function handle_user_action($conn) {
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $action_id = intval($_GET['id']);
        if ($action_id == $_SESSION['user_id']) {
            echo "<script>alert('Nemůžete smazat nebo změnit roli u vlastního účtu!'); window.location.href='admin.php';</script>";
            exit;
        }
        if ($_GET['action'] == 'delete') {
            $conn->query("DELETE FROM users WHERE id = $action_id");
            header("Location: admin.php?tab=users&msg=deleted");
            exit;
        } elseif ($_GET['action'] == 'toggle_role') {
            $conn->query("UPDATE users SET role = IF(role='admin','user','admin') WHERE id = $action_id");
            header("Location: admin.php?tab=users&msg=role_changed");
            exit;
        }
    }
}

function get_admin_message() {
    if(isset($_GET['msg'])) {
        if($_GET['msg'] == 'deleted') return "Uživatel smazán.";
        if($_GET['msg'] == 'role_changed') return "Role uživatele byla změněna.";
    }
    return "";
}

function handle_forms($conn) {
    $msg = "";
    if (isset($_POST['update_base_price'])) {
        $new_price = floatval($_POST['base_price']);
        $conn->query("UPDATE base_price SET cena_za_noc = $new_price");
        $msg = "Základní cena aktualizována.";
    }
    if (isset($_POST['add_season'])) {
        $stmt = $conn->prepare("INSERT INTO season_prices (nazev, datum_od, datum_do, cena_za_noc) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssd", $_POST['nazev'], $_POST['datum_od'], $_POST['datum_do'], $_POST['cena']);
        $stmt->execute();
        $msg = "Sezóna přidána.";
    }
    if (isset($_GET['delete_season'])) {
        $conn->query("DELETE FROM season_prices WHERE id = ".intval($_GET['delete_season']));
        header("Location: admin.php?tab=ceny"); exit;
    }
    if (isset($_GET['delete_res'])) {
        $conn->query("DELETE FROM reservations WHERE id = ".intval($_GET['delete_res']));
        header("Location: admin.php?tab=rezervace"); exit;
    }
    return $msg;
}

require_admin_access();
handle_user_action($conn);
$msg = get_admin_message();
$msg2 = handle_forms($conn);
if ($msg2) $msg = $msg2;

// Data uživatelů - stránkování
$limit = 5; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);
$users_res = $conn->query("SELECT * FROM users LIMIT $limit OFFSET $offset");

// Rezervace
$reservations_res = $conn->query("SELECT r.*, u.email as user_email FROM reservations r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.datum_prijezdu ASC");

// Data pro kalendář (všechny rezervace)
$occupied_dates = [];
$cal_res = $conn->query("SELECT datum_prijezdu, datum_odjezdu FROM reservations");
while ($r = $cal_res->fetch_assoc()) {
    $occupied_dates[] = $r;
}

// Ceny
$base_price_val = $conn->query("SELECT cena_za_noc FROM base_price LIMIT 1")->fetch_assoc()['cena_za_noc'] ?? 0;
$seasons_res = $conn->query("SELECT * FROM season_prices ORDER BY datum_od ASC");
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'users';
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script>
      fetch("header.php").then(r => r.text()).then(d => document.getElementById("header-placeholder").innerHTML = d);
    </script>
</head>
<body>
    <div id="header-placeholder"></div>
    <div class="background-image"></div>

    <section class="admin-hero">
        <main id="admin-container">
            <h1 class="admin-title">Admin Panel</h1>
            <?php if($msg): ?><div class="msg-box"><?php echo $msg; ?></div><?php endif; ?>

            <div class="admin-tabs">
                <a href="?tab=users" class="tab-button <?php echo $active_tab=='users'?'active':''; ?>">Uživatelé</a>
                <a href="?tab=rezervace" class="tab-button <?php echo $active_tab=='rezervace'?'active':''; ?>">Rezervace</a>
                <a href="?tab=calendar" class="tab-button <?php echo $active_tab=='calendar'?'active':''; ?>">Kalendář</a>
                <a href="?tab=ceny" class="tab-button <?php echo $active_tab=='ceny'?'active':''; ?>">Ceník</a>
            </div>

            <div id="users-tab" class="tab-content <?php echo $active_tab=='users'?'active':''; ?>">
                <h2>Seznam uživatelů</h2>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead><tr><th>ID</th><th>Jméno</th><th>Email</th><th>Role</th><th>Akce</th></tr></thead>
                        <tbody>
                            <?php while($u = $users_res->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['jmeno'].' '.$u['prijmeni']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><span class="badge <?php echo $u['role']=='admin'?'badge-admin':'badge-user'; ?>"><?php echo $u['role']; ?></span></td>
                                <td class="actions-cell">
                                    <a href="admin_edit_user.php?id=<?php echo $u['id']; ?>" class="btn-action btn-edit">Upravit</a>
                                    
                                    <a href="admin.php?action=toggle_role&id=<?php echo $u['id']; ?>&tab=users&page=<?php echo $page; ?>" class="btn-action btn-role">
                                        <?php echo $u['role'] == 'admin' ? '⬇ User' : '⬆ Admin'; ?>
                                    </a>
                                    
                                    <a href="admin.php?action=delete&id=<?php echo $u['id']; ?>&tab=users&page=<?php echo $page; ?>" class="btn-action btn-delete" onclick="return confirm('Opravdu smazat uživatele?');">Smazat</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="pagination">
                    <?php if($page > 1): ?>
                        <a href="admin.php?tab=users&page=<?php echo $page-1; ?>">&laquo; Předchozí</a>
                    <?php endif; ?>
                    
                    <span>Strana <?php echo $page; ?> z <?php echo $total_pages; ?></span>
                    
                    <?php if($page < $total_pages): ?>
                        <a href="admin.php?tab=users&page=<?php echo $page+1; ?>">Další &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>

            <div id="reservations-tab" class="tab-content <?php echo $active_tab=='rezervace'?'active':''; ?>">
                <h2>Seznam Rezervací</h2>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead><tr><th>ID</th><th>Host</th><th>Kontakt</th><th>Termín</th><th>Cena</th><th>Akce</th></tr></thead>
                        <tbody>
                            <?php while($r = $reservations_res->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $r['id']; ?></td>
                                <td><?php echo htmlspecialchars($r['jmeno'].' '.$r['prijmeni']); ?></td>
                                <td><?php echo htmlspecialchars($r['email']); ?><br><small><?php echo htmlspecialchars($r['telefon']); ?></small></td>
                                <td><?php echo date('d.m.Y', strtotime($r['datum_prijezdu'])).' - '.date('d.m.Y', strtotime($r['datum_odjezdu'])); ?></td>
                                <td><?php echo number_format($r['celkova_cena'], 0, ',', ' '); ?> Kč</td>
                                <td><a href="admin.php?delete_res=<?php echo $r['id']; ?>&tab=rezervace" class="btn-action btn-delete" onclick="return confirm('Opravdu stornovat rezervaci?');">Storno</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="calendar-tab" class="tab-content <?php echo $active_tab=='calendar'?'active':''; ?>">
                <h2>Obsazenost pokojů</h2>
                <p>Červeně vyznačené dny jsou obsazené.</p>
                <div class="calendar-wrapper" id="admin-calendar"></div>
            </div>

            <div id="prices-tab" class="tab-content <?php echo $active_tab=='ceny'?'active':''; ?>">
                <h2>Ceník</h2>
                <div class="price-settings-grid">
                    <div class="price-box">
                        <h3>Základní cena (mimo sezónu)</h3>
                        <form method="post" action="admin.php?tab=ceny">
                            <div class="form-group-edit">
                                <label>Cena za noc (Kč):</label>
                                <input type="number" name="base_price" value="<?php echo $base_price_val; ?>" required class="form-input">
                            </div>
                            <button type="submit" name="update_base_price" class="save-profile-button">Uložit základní cenu</button>
                        </form>
                    </div>
                    <div class="price-box">
                        <h3>Přidat sezónní cenu</h3>
                        <form method="post" action="admin.php?tab=ceny">
                            <div class="form-group-edit"><label>Název:</label><input type="text" name="nazev" required class="form-input"></div>
                            <div class="form-group-edit"><label>Od:</label><input type="date" name="datum_od" required class="form-input"></div>
                            <div class="form-group-edit"><label>Do:</label><input type="date" name="datum_do" required class="form-input"></div>
                            <div class="form-group-edit"><label>Cena za noc (Kč):</label><input type="number" name="cena" required class="form-input"></div>
                            <button type="submit" name="add_season" class="edit-profile-button">Přidat sezónu</button>
                        </form>
                    </div>
                </div>
                <h3>Aktivní sezóny</h3>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead><tr><th>Název</th><th>Termín</th><th>Cena/noc</th><th>Akce</th></tr></thead>
                        <tbody>
                            <?php while($s = $seasons_res->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['nazev']); ?></td>
                                <td><?php echo date('d.m.Y', strtotime($s['datum_od'])).' - '.date('d.m.Y', strtotime($s['datum_do'])); ?></td>
                                <td><?php echo number_format($s['cena_za_noc'], 0, ',', ' '); ?> Kč</td>
                                <td><a href="admin.php?delete_season=<?php echo $s['id']; ?>&tab=ceny" class="btn-action btn-delete" onclick="return confirm('Smazat sezónu?');">Smazat</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </section>
    <?php include 'footer.html'; ?>
    <script src="menu.js"></script>

    <script>
    const occupiedData = <?php echo json_encode($occupied_dates); ?>;
    let currentDate = new Date();
    const monthNames = ["Leden", "Únor", "Březen", "Duben", "Květen", "Červen", "Červenec", "Srpen", "Září", "Říjen", "Listopad", "Prosinec"];

    function renderAdminCalendar(year, month) {
        const container = document.getElementById('admin-calendar');
        if(!container) return;
        container.innerHTML = '';

        const header = document.createElement('div');
        header.className = 'calendar-header';
        
        const prev = document.createElement('button'); prev.innerText = "<";
        prev.onclick = () => { currentDate.setMonth(currentDate.getMonth() - 1); renderAdminCalendar(currentDate.getFullYear(), currentDate.getMonth()); };
        
        const next = document.createElement('button'); next.innerText = ">";
        next.onclick = () => { currentDate.setMonth(currentDate.getMonth() + 1); renderAdminCalendar(currentDate.getFullYear(), currentDate.getMonth()); };
        
        const title = document.createElement('span'); title.style.fontWeight = 'bold';
        title.innerText = `${monthNames[month]} ${year}`;
        
        header.append(prev, title, next);
        container.append(header);

        const grid = document.createElement('div');
        grid.className = 'calendar-grid';
        
        // Dny v týdnu
        const daysCZ = ['Po','Út','St','Čt','Pá','So','Ne'];
        daysCZ.forEach(d => {
            const dn = document.createElement('div'); 
            dn.className = 'calendar-day-name'; 
            dn.innerText = d; 
            grid.append(dn);
        });

        // Logika prvního dne a počtu dní
        const firstDay = new Date(year, month, 1).getDay();
        const startOffset = (firstDay === 0) ? 6 : firstDay - 1; // 0=Ne -> 6, 1=Po -> 0
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Prázdná políčka
        for(let i=0; i<startOffset; i++) {
            const e = document.createElement('div'); e.className = 'calendar-day empty'; grid.append(e);
        }

        // Dny v měsíci
        for(let d=1; d<=daysInMonth; d++) {
            const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            const cell = document.createElement('div');
            cell.className = 'calendar-day';
            cell.innerText = d;

            let isOccupied = false;
            occupiedData.forEach(res => {
                if (dateStr >= res.datum_prijezdu && dateStr < res.datum_odjezdu) isOccupied = true;
            });
            if(isOccupied) {
                cell.classList.add('occupied');
                cell.title = "Obsazeno";
            }
            grid.append(cell);
        }
        container.append(grid);
    }

    if(document.getElementById('admin-calendar')) {
        renderAdminCalendar(currentDate.getFullYear(), currentDate.getMonth());
    }
    </script>
</body>
</html>