<?php
/**
 * @file admin.php
 * Administrační panel.
 * Tento soubor umožňuje administrátorovi spravovat uživatele, rezervace a ceny.
 * Obsahuje logiku pro mazání uživatelů, změnu rolí, správu sezónních cen a rezervací.
 */

session_start();
include 'conn.php'; // Připojení k databázi

/**
 * Zabezpečení přístupu.
 * Kontrola, zda je uživatel přihlášen a má administrátorská práva.
 * Pokud ne, je přesměrován na přihlašovací stránku.
 * 
 * @return void
 */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit;
}

/**
 * Zpracování akcí (mazání uživatelů, změna rolí).
 * Pokud je v URL parametr `action` a `id`, provede se odpovídající akce.
 */
if (isset($_GET['action']) && isset($_GET['id'])) {
    /**
     * @var int $action_id ID uživatele, na kterém se provádí akce.
     */
    $action_id = intval($_GET['id']);
    
    // Ochrana: Admin nemůže smazat/upravit roli sám sobě
    if ($action_id == $_SESSION['user_id']) {
        echo "<script>alert('Nemůžete smazat nebo změnit roli u vlastního účtu!'); window.location.href='admin.php';</script>";
        exit;
    }

    // 1. Smazání uživatele
    if ($_GET['action'] == 'delete') {
        /**
         * Smazání uživatele z databáze.
         * Po úspěšném smazání je administrátor přesměrován zpět na seznam uživatelů.
         */
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $action_id);
        $stmt->execute();
        header("Location: admin.php?tab=users&msg=deleted");
        exit;
    } 
    // 2. Změna role (User <-> Admin)
    elseif ($_GET['action'] == 'toggle_role') {
        /**
         * Změna role uživatele.
         * Pokud je uživatel admin, změní se na user, a naopak.
         */
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $action_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $curr_user = $res->fetch_assoc();
            $new_role = ($curr_user['role'] == 'admin') ? 'user' : 'admin';
            $update = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $update->bind_param("si", $new_role, $action_id);
            $update->execute();
        }
        header("Location: admin.php?tab=users&msg=role_changed");
        exit;
    }
}

/**
 * @var string $msg Zpráva pro administrátora (např. potvrzení akce).
 * Tato zpráva se zobrazí na stránce po provedení akce.
 */
$msg = "";
if(isset($_GET['msg'])) {
    if($_GET['msg'] == 'deleted') $msg = "Uživatel byl smazán.";
    if($_GET['msg'] == 'role_changed') $msg = "Role uživatele byla změněna.";
}

/**
 * Zpracování formuláře pro aktualizaci základní ceny.
 * Pokud je odeslán formulář, aktualizuje se základní cena v databázi.
 */
if (isset($_POST['update_base_price'])) {
    $new_price = floatval($_POST['base_price']);
    $conn->query("UPDATE base_price SET cena_za_noc = $new_price");
    $msg = "Základní cena aktualizována.";
}

/**
 * Zpracování formuláře pro přidání sezónní ceny.
 * Přidává novou sezónní cenu do databáze.
 */
if (isset($_POST['add_season'])) {
    $nazev = $_POST['nazev'];
    $od = $_POST['datum_od'];
    $do = $_POST['datum_do'];
    $cena = $_POST['cena'];
    $stmt = $conn->prepare("INSERT INTO season_prices (nazev, datum_od, datum_do, cena_za_noc) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssd", $nazev, $od, $do, $cena);
    $stmt->execute();
    $msg = "Sezóna přidána.";
}

/**
 * Zpracování požadavku na smazání sezónní ceny.
 */
if (isset($_GET['delete_season'])) {
    $id = intval($_GET['delete_season']);
    $conn->query("DELETE FROM season_prices WHERE id = $id");
    header("Location: admin.php?tab=ceny");
    exit;
}

/**
 * Zpracování požadavku na smazání rezervace.
 */
if (isset($_GET['delete_res'])) {
    $id = intval($_GET['delete_res']);
    $conn->query("DELETE FROM reservations WHERE id = $id");
    header("Location: admin.php?tab=rezervace");
    exit;
}
    
// ---------------------------------------------------------
// LOGIKA STRÁNKOVÁNÍ (Pagination)
// ---------------------------------------------------------
/**
 * @var int $limit Počet uživatelů na stránku.
 */
$limit = 5;
/**
 * @var int $page Aktuální stránka.
 */
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
/**
 * @var int $offset Offset pro SQL dotaz (pro stránkování).
 */
$offset = ($page - 1) * $limit;

// Získání celkového počtu uživatelů
$count_res = $conn->query("SELECT COUNT(*) as total FROM users");
$total_users = $count_res->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);

// Načtení uživatelů s limitem a offsetem
$stmt_users = $conn->prepare("SELECT * FROM users LIMIT ? OFFSET ?");
$stmt_users->bind_param("ii", $limit, $offset);
$stmt_users->execute();
$users_res = $stmt_users->get_result();

// Načtení rezervací
$reservations_res = $conn->query("SELECT r.*, u.email as user_email FROM reservations r JOIN users u ON r.user_id = u.id ORDER BY r.datum_prijezdu ASC");
// Načtení základní ceny
$base_price_row = $conn->query("SELECT cena_za_noc FROM base_price LIMIT 1");
$base_price_val = ($base_price_row && $base_price_row->num_rows > 0) ? $base_price_row->fetch_assoc()['cena_za_noc'] : 0;
// Načtení sezónních cen
$seasons_res = $conn->query("SELECT * FROM season_prices ORDER BY datum_od ASC");
/**
 * @var string $active_tab Aktivní záložka v administračním panelu.
 */
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'users';
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="style.css">
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
                <button class="tab-button <?php echo $active_tab=='users'?'active':''; ?>" data-tab="users-tab">Uživatelé</button>
                <button class="tab-button <?php echo $active_tab=='rezervace'?'active':''; ?>" data-tab="reservation-tab">Rezervace</button>
                <button class="tab-button <?php echo $active_tab=='ceny'?'active':''; ?>" data-tab="prices-tab">Ceník</button>
            </div>

            <div id="users-tab" class="tab-content <?php echo $active_tab=='users' ? 'active' : ''; ?>">
                <h2>Seznam uživatelů</h2>
                <div class="table-responsive">
                    <table class="admin-table" id="admin-user-table">
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
                                    <a href="admin.php?action=delete&id=<?php echo $u['id']; ?>&tab=users&page=<?php echo $page; ?>" class="btn-action btn-delete" data-confirm="Opravdu smazat tohoto uživatele?">Smazat</a>
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

                    <?php for($i=1; $i<=$total_pages; $i++): ?>
                        <a href="admin.php?tab=users&page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if($page < $total_pages): ?>
                        <a href="admin.php?tab=users&page=<?php echo $page+1; ?>">Další &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>

            <div id="reservations-tab" class="tab-content <?php echo $active_tab=='rezervace'?'active':''; ?>">
                <h2>Všechny Rezervace</h2>
                <div class="table-responsive">
                    <table class="admin-table" id="admin-reservation-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Host</th>
                                <th>Kontakt</th>
                                <th>Od - Do</th>
                                <th>Cena</th>
                                <th>Poznámka</th>
                                <th>Akce</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($reservations_res->num_rows == 0): ?>
                                <tr><td colspan="7">Žádné rezervace nebyly nalezeny.</td></tr>
                            <?php else: ?>
                                <?php while ($r = $reservations_res->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $r['id']; ?></td>
                                        <td><?php echo htmlspecialchars($r['jmeno'] . ' ' . $r['prijmeni']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($r['email']); ?><br>
                                            <small><?php echo htmlspecialchars($r['telefon']); ?></small>
                                        </td>
                                        <td><?php echo date('d.m.Y', strtotime($r['datum_prijezdu'])) . ' - ' . date('d.m.Y', strtotime($r['datum_odjezdu'])); ?></td>
                                        <td><strong><?php echo number_format($r['celkova_cena'], 0, ',', ' '); ?> Kč</strong></td>
                                        <td><?php echo htmlspecialchars($r['poznamka']); ?></td>
                                        <td>
                                            <a href="admin.php?delete_res=<?php echo $r['id']; ?>&tab=rezervace" class="btn-action btn-delete" data-confirm="Smazat rezervaci?">Storno</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="prices-tab" class="tab-content <?php echo $active_tab=='ceny'?'active':' '; ?>">
                <h2>Nastavení Cen</h2>
                <div class="price-settings-grid">
                    <div class="price-box">
                        <h3>Základní cena (mimo sezónu)</h3>
                        <form method="post" action="admin.php?tab=ceny">
                            <div class="form-group-edit">
                                <label>Cena za noc (Kč):</label>
                                <input type="number" name="base_price" value="<?php echo $base_price_val; ?>" required>
                            </div>
                            <button type="submit" name="update_base_price" class="save-profile-button">Uložit základní cenu</button>
                        </form>
                    </div>
                    <div class="price-box">
                        <h3>Přidat sezónní cenu</h3>
                        <form method="post" action="admin.php?tab=ceny">
                            <div class="form-group-edit"><label>Název:</label><input type="text" name="nazev" required></div>
                            <div class="form-group-edit"><label>Od:</label><input type="date" name="datum_od" required></div>
                            <div class="form-group-edit"><label>Do:</label><input type="date" name="datum_do" required></div>
                            <div class="form-group-edit"><label>Cena za noc (Kč):</label><input type="number" name="cena" required></div>
                            <button type="submit" name="add_season" class="edit-profile-button">Přidat sezónu</button>
                        </form>
                    </div>
                </div>
                <h3>Aktivní sezóny</h3>
                <table class="admin-table" id="admin-price-table">
                    <thead><tr><th>Název</th><th>Termín</th><th>Cena/noc</th><th>Akce</th></tr></thead>
                    <tbody>
                        <?php while($s = $seasons_res->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['nazev']); ?></td>
                            <td><?php echo date('d.m.Y', strtotime($s['datum_od'])).' - '.date('d.m.Y', strtotime($s['datum_do'])); ?></td>
                            <td><?php echo number_format($s['cena_za_noc'], 0, ',', ' '); ?> Kč</td>
                            <td><a href="admin.php?delete_season=<?php echo $s['id']; ?>&tab=ceny" class="btn-action btn-delete">Smazat</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </section>

    <footer><p>&copy; 2023 Vilémův strejda. Admin Sekce.</p></footer>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const tabButtons = document.querySelectorAll(".tab-button");
            const tabContents = document.querySelectorAll(".tab-content");

            function openTab(tabName) {
                tabContents.forEach(content => content.style.display = "none");
                tabButtons.forEach(button => button.classList.remove("active"));

                document.getElementById(tabName).style.display = "block";
                document.querySelector(`[data-tab="${tabName}"]`).classList.add("active");
            }

            tabButtons.forEach(button => {
                button.addEventListener("click", function () {
                    const tabName = this.getAttribute("data-tab");
                    openTab(tabName);
                });
            });

            const activeTab = document.querySelector(".tab-button.active");
            if (activeTab) {
                openTab(activeTab.getAttribute("data-tab"));
            }
        });
    </script>
    <script src="menu.js"></script>
</body>
</html>