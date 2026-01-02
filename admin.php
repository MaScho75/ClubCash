<?php 

/*
 * This file is part of ClubCash.
 *
 * ClubCash is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 *
 * ClubCash is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with ClubCash. If not, see <https://www.gnu.org/licenses/>.
 */

session_start();
require_once 'VereinsfliegerRestInterface.php';

// Prüfen, ob der Benutzer bereits eingeloggt ist
if (isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true) {
    header('Location: portal.php');
    exit();
}

// // Lese die .env-Datei mit Fehlerbehandlung
// $envFile = 'daten/.env';
// if (!file_exists($envFile)) {
//     die("Fehler: .env Datei nicht gefunden in: $envFile");
// }

// $env = parse_ini_file($envFile);
// if ($env === false) {
//     die("Fehler beim Lesen der .env Datei");
// }


// Lade config.json
$config = json_decode(file_get_contents('daten/config.json'), true);
if ($config === null) {
    die('Fehler beim Lesen der config.json');
}

// Nach session_start() einfügen:
// Login-Versuche tracken
if (!isset($_SESSION['admin_login_attempts'])) {
    $_SESSION['admin_login_attempts'] = 0;
    $_SESSION['admin_last_attempt'] = 0;
}

// Wartezeit berechnen (5 * 2^versuche Sekunden)
$waitTime = 5 * pow(2, $_SESSION['admin_login_attempts']); 
$remainingTime = ($_SESSION['admin_last_attempt'] + $waitTime) - time();

// Login-Logik anpassen (vor dem HTML-Teil):
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prüfen ob Wartezeit vorbei ist
    if ($remainingTime > 0) {
        $error_message = "Bitte warten Sie noch {$remainingTime} Sekunden vor dem nächsten Versuch.";
    } else {
        $UserName = trim($_POST['username'] ?? '');
        $Password = trim($_POST['password'] ?? '');
        $Authentifizier = trim($_POST['authentifizier'] ?? '');

        if (!empty($UserName) && !empty($Password)) {
            $api = new VereinsfliegerRestInterface();
            
            if ($api->SignIn($UserName, $Password, 0, $config['appkey'], $Authentifizier)) {
                // Login erfolgreich
                $_SESSION['accessToken'] = $api->GetAccessToken();
                $_SESSION['tokenExpiry'] = time() + 3600;
                $_SESSION['user_authenticated'] = true;
                $_SESSION['username'] = $UserName;
                $_SESSION['customer_login'] = false;
                $_SESSION['admin_login_attempts'] = 0;
                $_SESSION['admin_last_attempt'] = 0;
                header('Location: portal.php');
                exit();
            } else {
                // Login fehlgeschlagen
                $_SESSION['admin_login_attempts']++;
                $_SESSION['admin_last_attempt'] = time();
                $waitTime = 5 * pow(2, $_SESSION['admin_login_attempts']);
                
                // Seite neu laden für sofortige Countdown-Anzeige
                header('Location: ' . $_SERVER['PHP_SELF'] . '?error=' . urlencode("Ungültige Zugangsdaten!"));
                exit();
            }
        } else {
            $error_message = "Bitte alle erforderlichen Felder ausfüllen.";
        }
    }
}

// Fehlermeldung aus URL-Parameter auslesen
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
    $remainingTime = ($_SESSION['admin_last_attempt'] + $waitTime) - time();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <!-- Zeichensatz auf UTF-8 setzen -->
    <meta charset="UTF-8">

    <!-- Skalierbarkeit für mobile Geräte sicherstellen -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ClubCashAdminLogin</title>

    <!-- Anweisung an Suchmaschinen, die Seite NICHT zu indexieren -->
    <meta name="robots" content="noindex, nofollow">
	
	<link rel="stylesheet" href="style-portal.css?v=<?php echo time(); ?>">

	<link rel="stylesheet" href="farben.css?v=<?php echo time(); ?>">

</head>
<body class="portal">
    <div id="login-container">
       <div id="kopf" style="display: block; align-items: center;">
            <a href="https://clubcash.net/"><img src="grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 130px; "></a>

        <p><b><a href="<?php echo $config['Webseite']; ?>" target="_blank" style="text-decoration: none; margin: 0px;">
                <span style="font-size: 24px; color: var(--warning-color););"><?php echo $config['Vereinsname']; ?></span>
            </a></b></p>
        <p><b>Admin-Login</b></p>
        </div>
        <?php if (isset($error_message)) echo "<p style='text-align: center; color: var(--error-color);'>$error_message</p>"; ?>
        
        <form method="POST" action="">

                <div class="grid-container" style="display: grid; grid-template-columns: auto auto; gap: 10px; margin-bottom: 20px;">

                    

                    <div style=" padding: 5px; text-align: right; width: 250px;">Email</div>
                    
                    <div style=" padding: 5px; text-align: center;"><input type="text" name="username" id="username"  style="font-size: 20px; border: none; font-family: 'Carlito', sans-serif; width: 300px;"></div>
                    
                    <div style=" padding: 5px; text-align: right;">Passwort</div>
                    
                    <div style=" padding: 5px; text-align: center;"><input type="password" name="password" id="password"  style="font-size: 20px; border: none; font-family: 'Carlito', sans-serif; width: 300px;"></div>
                    
                    <div style=" padding: 5px; text-align: right;">Zwei-Faktor-Authentifizierung</div>
                    
                    <div style=" padding: 5px; text-align: center;"><input type="text" name="authentifizier" id="authentifizier" placeholder="optional" style="font-size: 20px; border: none; font-family: 'Carlito', sans-serif; width: 300px;"></div>
            
                </div>

            <div style="text-align: center;">
                <input class="green button" type="submit" value="Anmelden">
                <br>
                <button style="background-color: var(--success-color);"  class="button" type="button" onclick="window.location.href='index.php';">Kunden-Login</button>
            </div>

        </form>

        <?php if ($remainingTime > 0): ?>
            <p style="text-align: center; color: var(--warning-color); margin-top: 30px;" id="countdown-container">
                Nächster Versuch in <span id="countdown"><?= $remainingTime ?></span> Sekunden möglich.
            </p>
            <script>
                (function() {
                    let timeLeft = <?= $remainingTime ?>;
                    const countdownElement = document.getElementById('countdown');
                    const countdownContainer = document.getElementById('countdown-container');
                    const loginForm = document.querySelector('form');
                    const submitButton = loginForm.querySelector('input[type="submit"]');
                    
                    // Login-Button sofort deaktivieren
                    submitButton.disabled = true;
                    
                    // Countdown-Funktion
                    function updateCountdown() {
                        if (timeLeft > 0) {
                            countdownElement.textContent = timeLeft;
                            timeLeft--;
                            setTimeout(updateCountdown, 1000);
                            submitButton.style = 'background-color: var(--border-color);';
                        } else {
                            submitButton.disabled = false;
                            countdownContainer.style.display = 'none';
                            submitButton.style = 'background-color: var(--success-color);';
                        }
                    }
                    
                    // Countdown sofort starten
                    updateCountdown();
                })();
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
