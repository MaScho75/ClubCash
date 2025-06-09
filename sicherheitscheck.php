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

?>

    <button class='kleinerBt' onclick="absicherungStarten()">Absichern!</button>
</body>
</html>

