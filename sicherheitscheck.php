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

// Prüfen, ob der Benutzer eingeloggt ist

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php'); // Falls nicht eingeloggt, zurück zur Login-Seite
    exit();
}

function isHtaccessProtected($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 401) {
        return true;
    } elseif ($httpCode === 200) {
        return false;
    } else {
        return "Unklar (Status: $httpCode)";
    }
}

function absoluteUrl($relativePath) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $relativePath;
}

function checkPermissions($path, $expectedMode = '700') {
    if (!file_exists($path)) {
        return "❌ Pfad existiert nicht: $path";
    }

    $perms = fileperms($path);

    // Letzte 4 Ziffern der oktalen Darstellung extrahieren
    $mode = substr(decoct($perms & 07777), -4);

    if ($mode === $expectedMode) {
        return "✅ $path hat korrekten Modus: $mode";
    } else {
        return "⚠️ $path hat Modus $mode, erwartet: $expectedMode";
    }
}


// htaccess-geschützt kasse

// überprüfen, ob in der config.JSON ein kassenpw gesetzt ist
if (!file_exists('daten/config.json')) {
    die("❌ Die Datei config.json existiert nicht.");
}

$config = json_decode(file_get_contents('daten/config.json'), true);
if (!isset($config['kassenpw']) || empty($config['kassenpw'])) {
    die("❌ In der config.json ist kein Passwort für das Kassenmodul gesetzt. Bitte wechsel in die Programmeinstellungen und setze ein Passwort ein.");
}

echo "Prüfung, ob das Verzeichnis <b>/kasse</b> ausreichen abgesichert ist:";

$relativeUrl = '/kasse/';
$url = absoluteUrl($relativeUrl);

$result = isHtaccessProtected($url);

if ($result === true) {
    echo "<p>✅ Das Verzeichnis ist .htaccess-geschützt.</p>";
} elseif ($result === false) {
    echo "<p>❌ Das Verzeichnis ist NICHT geschützt.</p>";
} else {
    echo "<p>⚠️ Ergebnis: $result</p>";
}

// htaccess-geschützt daten

echo "Prüfung, ob das Verzeichnis <b>/daten</b> ausreichen abgesichert ist:";

$relativeUrl = '/daten/';
$url = absoluteUrl($relativeUrl);

$result = isHtaccessProtected($url);

if ($result === true) {
    echo "<p>✅ Das Verzeichnis ist .htaccess-geschützt.</p>";
} elseif ($result === false) {
    echo "<p>❌ Das Verzeichnis ist NICHT geschützt.</p>";
} else {
    echo "<p>⚠️ Ergebnis: $result</p>";
}

// Verzeichnisse prüfen ob 0700
echo "<p>Überprüfung von <b>/backup</b>:<br>";
echo checkPermissions('backup');
echo "</p>";
echo "<button class='kleinerBt' onclick=\"absicherungStarten()\">ABSICHERN</button></p>";

// prüfen, ob die datei install.php existiert
if (file_exists('install.php')) {
    echo "<div class='warnung' style='padding: 10px; border-radius: 5px;'>";
    echo " <p>⚠️ Achtung: Die Datei <b>install.php</b> existiert!<br>";
    echo "Bitte löschen Sie diese Datei, um die Sicherheit zu erhöhen. Sie wird nicht durch die oben ausgelöste Absicherung entfernt.";
    echo "Die Datei <b>install.php</b> sollte nicht mehr auf dem Server liegen, um Sicherheitsrisiken zu vermeiden.</p>";
    echo "<button class='kleinerBt' onclick=\"installLöschen()\">LÖSCHEN</button></p>";
    echo "</div>";
}
else {
    echo "<p>✅ Die Datei <b>install.php</b> existiert nicht.</p>";
}
?>

</body>
</html>

