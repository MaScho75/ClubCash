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

// Wenn POST-Formular gesendet wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $KundenName = trim($_POST['kundenname'] ?? '');
    $Schlüsselnummer = trim($_POST['schlüsselnummer'] ?? '');

    if (!empty($KundenName) && !empty($Schlüsselnummer)) {
        $found = false;
        foreach ($kundenDaten as $kunde) {
            if ($kunde['email'] === $KundenName && $kunde['key2designation'] === $Schlüsselnummer) {
                $_SESSION['user_authenticated'] = true;
                $_SESSION['username'] = $KundenName;
                $_SESSION['customer_login'] = true;
                header('Location: portal.php');
                exit();
            }
        }
        $error_message = "Ungültige Email oder Schlüsselnummer!";
    } else {
        $error_message = "Bitte Email und Schlüsselnummer eingeben.";
    }
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



?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubCash Login</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="farben.css?v=<?= time(); ?>">
</head>
<body class="portal">
    <div id="login-container">
        <div id="kopf" style="display: flex; align-items: center;">
            <img src="grafik/ClubCashLogo-gelbblauschwarz.svg" style="width: 130px; margin: 30px;">
        </div>

        <?php if (!empty($error_message)): ?>
            <p style="text-align: center; color: var(--error-color);"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="grid-container" style="display: grid; grid-template-columns: auto auto; gap: 10px; padding: 20px;">
                <p></p><p style="margin: 0px;"><b>Kunden-Login</b></p>

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
                <input class="button" type="submit" value="Anmelden">
                <br>
                <button class="button" type="button" onclick="window.location.href='admin.php';">Admin-Login</button>
            </div>
        </form>
    </div>
</body>
</html>
