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

// Prüfen, ob ZipArchive verfügbar ist
if (!class_exists('ZipArchive')) {
    die('❌ Das System unterstützt keine Zip-Archive.<br>Bitte installieren Sie die ZipArchive-Erweiterung.<br>Ein Update ist nicht möglich.');
}

// Das aktuelle Repository aus GitHub abrufen und die Dateien aktualisieren

echo 'Aktualisiere ClubCash...<br>';

$owner = 'MaScho75';
$repo = 'ClubCash';
$url = "https://api.github.com/repos/$owner/$repo/releases/latest";

$context = stream_context_create([
    'http' => [
        'header' => [
            'User-Agent: ClubCash Update Script',
            'Accept: application/vnd.github.v3+json'
        ]
    ]
]);

$response = file_get_contents($url, false, $context);

if ($response === false) {
    die('❌ Fehler beim Abrufen der neuesten Version. Bitte versuchen Sie es später erneut.');
}

$release = json_decode($response, true);

if (isset($release['assets']) && is_array($release['assets'])) {
    echo 'Neue Version gefunden: ' . htmlspecialchars($release['tag_name']) . '<br>';
    foreach ($release['assets'] as $asset) {
        echo 'Starte Dateiupload...<br>';
        if (strpos($asset['name'], 'zip') !== false) {
            $downloadUrl = $asset['browser_download_url'];
            $zipFile = 'update.zip';
            
            // Herunterladen der ZIP-Datei
            echo 'Herunterladen der Datei: ' . htmlspecialchars($asset['name']) . '<br>';
            file_put_contents($zipFile, fopen($downloadUrl, 'r'));

            // Entpacken der ZIP-Datei
            echo 'Entpacken der Datei...<br>';
            $zip = new ZipArchive;
            if ($zip->open($zipFile) === true) {
                $zip->extractTo('.');
                $zip->close();
                echo 'Dateien erfolgreich entpackt.<br>';
                unlink($zipFile); // ZIP-Datei nach dem Entpacken löschen

                echo '✅ Update erfolgreich durchgeführt!<br>';
            } else {
                echo '❌ Fehler beim Entpacken der ZIP-Datei.<br>';
            }
            break;
        }
    }
} else {
    echo '⚠️ Keine neuen Versionen gefunden.<br>';
}

// Versionsnummer aktualisieren in der config.json

echo 'Aktualisiere die Versionsnummer...<br>';
$configFile = 'daten/config.json';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (is_array($config)) {
        $config['Version'] = $release['tag_name'] ?? 'unbekannt';
        $config['letzteAktualisierung'] = date('Y-m-d H:i:s'); // Aktuelles Datum und Uhrzeit des Updates
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo '✅ Versionsnummer und Aktualisierungszeitüunkt in der config.json aktualisiert.<br>';
    } else {
        echo '❌ Fehler beim Lesen der config.json. Die Versionsnummer konnte nicht aktualisiert werden<br>';
    }
} else {
    echo '❌ config.json nicht gefunden. Die Versionsnummer konnte nicht aktualisiert werden<br>';
}

echo '⚠️ Laden Sie die Seite neu, um die Änderungen zu sehen.<br>';
echo '<button class="kleinerBt" onclick="window.location.href=\'index.php\'">Startseite</button>';
?>