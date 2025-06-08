<?php

/*
 * ClubCash Update Script
 * GNU AGPL v3 lizenziert
 */

session_start();

// Benutzer-Authentifizierung prüfen
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php');
    exit();
}

// ZIP-Unterstützung prüfen
if (!class_exists('ZipArchive')) {
    die('❌ Das System unterstützt keine Zip-Archive. Bitte aktivieren Sie die ZipArchive-Erweiterung.');
}

echo '🔄 Aktualisiere ClubCash...<br>';

// GitHub-API-Abfrage vorbereiten
$owner = 'MaScho75';
$repo = 'ClubCash';
$url = "https://api.github.com/repos/$owner/$repo/releases/latest";

// GitHub API aufrufen mit User-Agent
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
    die('❌ Fehler beim Abrufen der neuesten Version von GitHub.');
}

// JSON verarbeiten
$release = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die('❌ JSON-Fehler: ' . json_last_error_msg());
}

// Prüfen, ob Assets vorhanden sind
if (!isset($release['assets']) || !is_array($release['assets']) || count($release['assets']) === 0) {
    die('⚠️ Keine Release-Dateien (Assets) gefunden.');
}

echo '✅ Neue Version gefunden: ' . htmlspecialchars($release['tag_name']) . '<br>';

// Hilfsfunktion zum sicheren Herunterladen
function downloadFile($url, $path) {
    $fp = fopen($path, 'w+');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ClubCash Update Script');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_exec($ch);
    $success = !curl_errno($ch);
    curl_close($ch);
    fclose($fp);
    return $success;
}

// ZIP-Datei suchen und herunterladen
foreach ($release['assets'] as $asset) {
    if (strpos($asset['name'], 'zip') !== false) {
        $downloadUrl = $asset['browser_download_url'];
        $zipFile = 'update.zip';

        echo '⬇️ Herunterladen: ' . htmlspecialchars($asset['name']) . '<br>';
        if (!downloadFile($downloadUrl, $zipFile)) {
            die('❌ Fehler beim Herunterladen der Datei.');
        }

        // Backup der aktuellen Config
        $configFile = 'daten/config.json';
        if (file_exists($configFile)) {
            $backupFile = 'daten/config_backup_' . date('Ymd_His') . '.json';
            copy($configFile, $backupFile);
            echo '🗄️ Backup der config.json gespeichert: ' . htmlspecialchars($backupFile) . '<br>';
        }

        // Entpacken
        echo '📦 Entpacken der ZIP-Datei...<br>';
        $zip = new ZipArchive;
        if ($zip->open($zipFile) === true) {
            $zip->extractTo('.');
            $zip->close();
            echo '✅ Dateien erfolgreich entpackt.<br>';

            // ZIP löschen
            if (unlink($zipFile)) {
                echo '🧹 ZIP-Datei gelöscht.<br>';
            } else {
                echo '⚠️ Konnte ZIP-Datei nicht löschen.<br>';
            }
        } else {
            die('❌ Fehler beim Entpacken der ZIP-Datei.');
        }

        break; // Nur eine ZIP-Datei behandeln
    }
}

// Versionsnummer aktualisieren
echo '🔧 Aktualisiere config.json...<br>';
if (file_exists($configFile)) {
    $configData = file_get_contents($configFile);
    $config = json_decode($configData, true);

    if (is_array($config)) {
        $config['Version'] = $release['tag_name'] ?? 'unbekannt';
        $config['letzteAktualisierung'] = date('Y-m-d H:i:s');
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo '✅ config.json aktualisiert.<br>';
    } else {
        echo '❌ Fehler beim Parsen der config.json.<br>';
    }
} else {
    echo '❌ config.json nicht gefunden.<br>';
}

// Optional: Logging
file_put_contents('update.log', "[" . date('Y-m-d H:i:s') . "] Update auf Version {$release['tag_name']}\n", FILE_APPEND);

// Fertig
echo '🎉 Update abgeschlossen!<br>';
echo '<button class="kleinerBt" onclick="window.location.href=\'index.php\'">🔁 Zurück zur Startseite</button>';
