<?php
/**
 * @file login.php
 * Zpracování přihlášení uživatele.
 * Tento soubor ověřuje přihlašovací údaje uživatele, nastavuje session proměnné
 * a přesměrovává uživatele na odpovídající stránku podle jeho role.
 */


session_start();
include 'conn.php';

/**
 * Zpracuje přihlášení uživatele, nastaví session a přesměruje podle role.
 * Ověřuje přihlašovací údaje a vypisuje odpovídající zprávy.
 *
 * @global mysqli $conn
 * @return void
 */
function process_login() {
    global $conn;
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'];
        $password = $_POST['password'];
        if (!empty($email) && !empty($password)) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['heslo'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['loggedin'] = true;
                    if ($user['role'] === 'admin') {
                        echo '<script>alert("Vítejte v administraci!");window.location.href = "admin.php";</script>';
                    } else {
                        echo '<script>alert("Jste úspěšně přihlášený/á");window.location.href = "profile.php";</script>';
                    }
                    exit;
                } else {
                    echo '<script>alert("Špatný E-mail nebo heslo");window.location.href = "login.html";</script>';
                }
            } else {
                echo '<script>alert("Žádný uživatel s tímto E-mailem nenalezený");window.location.href = "login.html";</script>';
            }
        } else {
            echo '<script>alert("Prosím zadejte E-mail i heslo");window.location.href = "login.html";</script>';
        }
    }
}

process_login();
?>