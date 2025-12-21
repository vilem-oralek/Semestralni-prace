<?php
/**
 * @file login.php
 * Zpracování přihlášení uživatele.
 * Tento soubor ověřuje přihlašovací údaje uživatele, nastavuje session proměnné
 * a přesměrovává uživatele na odpovídající stránku podle jeho role.
 */

session_start();
include 'conn.php'; // Připojení k databázi

/**
 * Zpracování přihlášení uživatele.
 * Kontroluje, zda byl formulář odeslán metodou POST, a ověřuje přihlašovací údaje.
 * 
 * @return void
 */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    /**
     * @var string $email E-mailová adresa zadaná uživatelem.
     */
    $email = $_POST['email'];
    /**
     * @var string $password Heslo zadané uživatelem.
     */
    $password = $_POST['password'];

    /**
     * Kontrola, zda jsou vyplněny všechny povinné údaje.
     * Pokud nejsou, zobrazí se chybová zpráva.
     */
    if (!empty($email) && !empty($password)) {
        /**
         * Příprava SQL dotazu pro ověření uživatele podle e-mailu.
         * 
         * @var mysqli_stmt $stmt Připravený SQL dotaz.
         */
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        /**
         * @var mysqli_result $result Výsledek dotazu obsahující informace o uživateli.
         */
        $result = $stmt->get_result();

        /**
         * Kontrola, zda byl nalezen uživatel s daným e-mailem.
         */
        if ($result->num_rows == 1) {
            /**
             * @var array $user Pole obsahující informace o uživateli.
             */
            $user = $result->fetch_assoc();

            /**
             * Ověření hesla pomocí funkce password_verify.
             * Pokud heslo odpovídá, nastaví se session proměnné a uživatel je přesměrován.
             */
            if (password_verify($password, $user['heslo'])) {
                // Nastavení session proměnných
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $user['role']; // Ukládáme roli uživatele (admin/user)
                $_SESSION['loggedin'] = true;
                
                /**
                 * Přesměrování uživatele podle jeho role.
                 * Pokud je uživatel admin, přesměruje se na stránku administrace.
                 * Pokud je uživatel běžný uživatel, přesměruje se na profilovou stránku.
                 */
                if ($user['role'] === 'admin') {
                     echo '<script>
                        alert("Vítejte v administraci!");
                        window.location.href = "admin.php";
                      </script>';
                } else {
                    // Chybné heslo
                     echo '<script>
                        alert("Jste úspěšně přihlášený/á");
                        window.location.href = "profile.php";
                      </script>';
                }
                exit; 
            } else {
                // Chybné heslo
                echo '<script>
                        alert("Špatný E-mail nebo heslo");
                        window.location.href = "login.html";
                      </script>';
            }
        } else {
            // Uživatel s daným e-mailem nebyl nalezen
            echo '<script>
                    alert("Žádný uživatel s tímto E-mailem nenalezený");
                    window.location.href = "login.html";
                  </script>';
        }
    } else {
        // Chybí e-mail nebo heslo
        echo '<script>
                alert("Prosím zadejte E-mail i heslo");
                window.location.href = "login.html";
              </script>';
    }
}
?>