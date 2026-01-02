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

/**
 * Copies a directory and its contents recursively
 */


session_start();

// Login-Versuche tracken
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = 0;
}

// prüfe ob der Ordner "daten" existiert
if (!is_dir('daten')) {
    //kopiere den Ordner "daten_template" und nenne ihn "daten"
    if (!copyDirectory('daten_template', 'daten')) {
        die('Fehler beim Kopieren des Template-Verzeichnisses');
    }
}

// Prüfe, ob das Verzeichnis "backup" existiert, wenn nicht, erstelle es
if (!is_dir('backup')) {
    if (!mkdir('backup', 0755, true)) {
        die('Fehler beim Erstellen des Backup-Verzeichnisses');
    }
}

// Prüfe, ob die config.json-Datei einen Wert für "appkey" enthält
// Lade die Konfigurationsdatei
$configFile = 'daten/config.json';
$configData = json_decode(file_get_contents($configFile), true);
if (isset($configData['appkey']) && !empty($configData['appkey'])) {
    // App-Key ist gesetzt, weiter mit dem Login
} else {
    // App-Key ist nicht gesetzt, leite zur Konfiguration weiter
    header('Location: install.php');
    exit();
}


// Basis-URL für Vereinsflieger
$baseUrl = 'https://www.vereinsflieger.de';

// Kunden-Daten laden
$kundenDaten = json_decode(file_get_contents('daten/kunden.json'), true);

// Lade externe Kunendaten
$externeKundenDaten = json_decode(file_get_contents('daten/externe.json'), true);

// Füge externe Kundendaten zu den internen Kundendaten hinzu
if (is_array($externeKundenDaten)) {
    foreach ($externeKundenDaten as $externerKunde) {
        // Überprüfen, ob der externe Kunde bereits in den internen Kundendaten existiert
        $exists = false;
        foreach ($kundenDaten as $internerKunde) {
            if ($internerKunde['email'] === $externerKunde['email'] && $internerKunde['schlüssel'] === $externerKunde['schlüssel']) {
                $exists = true;
                break;
            }
        }
        // Wenn der externe Kunde nicht existiert, füge ihn hinzu
        if (!$exists) {
            $kundenDaten[] = [
                'email' => $externerKunde['email'],
                'schlüssel' => $externerKunde['schlüssel'],
                'uid' => $externerKunde['uid'] ?? null, // Optional
                'cc_seller' => $externerKunde['cc_seller'] ?? false, // Standardwert
                'cc_member' => $externerKunde['cc_member'] ?? false, // Standardwert
                'cc_guest' => $externerKunde['cc_guest'] ?? true, // Standardwert
                'cc_admin' => $externerKunde['cc_admin'] ?? false // Standardwert
            ];
        }
    }
}

// Prüfen, ob Benutzer bereits eingeloggt ist
if (isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true) {
    header('Location: portal.php');
    exit();
}

// --- Access Token prüfen oder neu anfordern ---
if (!isset($_SESSION['accessToken']) || !isset($_SESSION['tokenExpiry']) || $_SESSION['tokenExpiry'] < time()) {
    // Token neu anfordern
    $tokenResponse = @file_get_contents("$baseUrl/interface/rest/auth/accesstoken");

    if ($tokenResponse === false) {
        $error_message = "Fehler beim Abrufen des Access Tokens von Vereinsflieger.";
    } else {
        $tokenData = json_decode($tokenResponse, true);
        if (isset($tokenData['accesstoken'])) {
            $_SESSION['accessToken'] = $tokenData['accesstoken'];
            $_SESSION['tokenExpiry'] = time() + 3600; // Gültig für 1 Stunde
        } else {
            $error_message = "Ungültige Antwort vom Token-Server.";
        }
    }
}

// Wartezeit berechnen (5 * 2^versuche Sekunden)
$waitTime = 5 * pow(2, $_SESSION['login_attempts']); 
$remainingTime = ($_SESSION['last_attempt'] + $waitTime) - time();

// Wenn POST-Formular gesendet wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prüfen ob Wartezeit vorbei ist
    if ($remainingTime > 0) {
        $error_message = "Bitte warten Sie noch {$remainingTime} Sekunden vor dem nächsten Versuch.";
    } else {
        $KundenName = trim($_POST['kundenname'] ?? '');
        $Schlüsselnummer = trim($_POST['schlüsselnummer'] ?? '');

        if (!empty($KundenName) && !empty($Schlüsselnummer)) {
            $found = false;
            foreach ($kundenDaten as $kunde) {
                if ($kunde['email'] === $KundenName && $kunde['schlüssel'] === $Schlüsselnummer) {
                    // Login erfolgreich
                    $_SESSION['user_authenticated'] = true;
                    $_SESSION['username'] = $KundenName;
                    $_SESSION['customer_login'] = true;
                    $_SESSION['login_attempts'] = 0;
                    $_SESSION['last_attempt'] = 0;
                    header('Location: portal.php');
                    exit();
                }
            }
            // Login fehlgeschlagen
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt'] = time();
            $waitTime = 5 * pow(2, $_SESSION['login_attempts']); 
            
            // Seite sofort neu laden um den Countdown anzuzeigen
            header('Location: ' . $_SERVER['PHP_SELF'] . '?error=' . urlencode("Ungültige Email oder Schlüsselnummer!"));
            exit();
        } else {
            $error_message = "Bitte Email und Schlüsselnummer eingeben.";
        }
    }
}

// Fehlermeldung aus URL-Parameter auslesen (am Anfang der Datei nach session_start())
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
    $remainingTime = ($_SESSION['last_attempt'] + $waitTime) - time();
}



function copyDirectory($source, $destination) {
    if (!is_dir($source)) {
        return false;
    }
    
    if (!is_dir($destination)) {
        if (!mkdir($destination, 0755, true)) {
            return false;
        }
    }
    
    $dir = opendir($source);
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            $srcFile = $source . '/' . $file;
            $destFile = $destination . '/' . $file;
            
            if (is_dir($srcFile)) {
                copyDirectory($srcFile, $destFile);
            } else {
                copy($srcFile, $destFile);
            }
        }
    }
    closedir($dir);
    return true;
}

// Lade config.json
$config = json_decode(file_get_contents('daten/config.json'), true);
if ($config === null) {
    die('Fehler beim Lesen der config.json');
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubCash Login</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="style-portal.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="farben.css?v=<?= time(); ?>">
</head>
<body class="portal">
    <div id="login-container">
        <div id="kopf" style="display: block; align-items: center;">
            <a href="https://clubcash.net/"><img src="grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 130px; "></a>

        <p><b><a href="<?php echo $config['Webseite']; ?>" target="_blank" style="text-decoration: none; margin: 0px;">
                <span style="font-size: 24px; color: var(--warning-color););"><?php echo $config['Vereinsname']; ?></span>
            </a></b></p>
        <p><b>Kunden-Login</b></p>
        </div>

        <?php if (!empty($error_message)): ?>
            <p style="text-align: center; color: var(--error-color);"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="grid-container" style="display: grid; grid-template-columns: auto auto; gap: 10px; padding: 20px;">
                <div style="padding: 5px; text-align: right; width: 250px;">Email</div>
                <div style="padding: 5px; text-align: center;">
                    <input type="text" name="kundenname" id="kundenname" style="font-size: 20px; border: none; font-family: 'Carlito', sans-serif; width: 300px;">
                </div>

                <div style="padding: 5px; text-align: right;">Key</div>
                <div style="padding: 5px; text-align: center;">
                    <input type="password" name="schlüsselnummer" id="schlüsselnummer" style="font-size: 20px; border: none; font-family: 'Carlito', sans-serif; width: 300px;">
                </div>
            </div>

            <div style="text-align: center;">
                <input id="submit-button" class="green button" type="submit" value="Anmelden">
                <br>
                <button class="button" type="button" onclick="window.location.href='admin.php';">Admin-Login</button>
            </div>
        </form>

        <?php if ($remainingTime > 0): ?>
            <p style="text-align: center; color: var(--warning-color); margin-top: 30px;" id="countdown-container">
                Nächster Versuch in <span id="countdown"><?= $remainingTime ?></span> Sekunden möglich.
            </p>
            <script>
                // Sofort ausführende Funktion (IIFE)
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
                            document.getElementById('submit-button').style= 'background-color: var(--border-color);';
                        } else {
                            submitButton.disabled = false;
                            countdownContainer.style.display = 'none';
                            document.getElementById('submit-button').style= 'background-color: var(--success-color);';
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
