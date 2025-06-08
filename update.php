<?php

session_start();

// Authentifizierung prüfen
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php');
    exit();
}

// ZipArchive-Unterstützung prüfen
if (!class_exists('ZipArchive')) {
    die('❌ Das System unterstützt keine Zip-Archive. Bitte die ZipArchive-Erweiterung aktivieren.');
}

echo '🔄 Aktualisiere ClubCash...<br>';

// GitHub API aufrufen via cURL
function getGitHubRelease($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ClubCash Update Script');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/vnd.github.v3+json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo '❌ cURL-Fehler: ' . curl_error($ch);
        curl_close($ch);
        return false;
    }
    curl_close($ch);
    return $response;
}

// Datei herunterladen via cURL
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

$owner = 'MaScho75';
$repo = 'ClubCash';
$apiUrl = "https://api.github.com/repos/$owner/$repo/releases/latest";
$response = getGitHubRelease($apiUrl);

if ($response === false) {
    die('❌ Fehler beim Abrufen der GitHub-Daten.');
}

$release = json_decode($response, true);

if (!isset($release['assets']) || !is_array($release['assets']) || count($release['assets']) === 0) {
    echo '⚠️ Keine Release-Dateien (Assets) gefunden.<br>';
    exit();
}

$found = false;
foreach ($release['assets'] as $asset) {
    if (preg_match('/\.zip$/i', $asset['name'])) {
        $found = true;
        echo '⬇️ Gefundene ZIP-Datei: ' . htmlspecialchars($asset['name']) . '<br>';

        $downloadUrl = $asset['browser_download_url'];
        $zipFile = 'update.zip';

        echo '⬇️ Herunterladen...<br>';
        if (!downloadFile($downloadUrl, $zipFile)) {
            die('❌ Fehler beim Herunterladen der ZIP-Datei.');
        }

        // ZIP entpacken
        echo '📦 Entpacken...<br>';
        $zip = new ZipArchive;
        if ($zip->open($zipFile) === true) {
            $zip->extractTo('.');
            $zip->close();
            echo '✅ Dateien erfolgreich entpackt.<br>';
            unlink($zipFile);
        } else {
            die('❌ Fehler beim Entpacken der ZIP-Datei.');
        }

        break;
    }
}

if (!$found) {
    echo '⚠️ Keine passende ZIP-Datei im Release gefunden.<br>';
    exit();
}

// config.json aktualisieren
$configFile = 'daten/config.json';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (is_array($config)) {
        $config['Version'] = $release['tag_name'] ?? 'unbekannt';
        $config['letzteAktualisierung'] = date('Y-m-d H:i:s');
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo '✅ config.json wurde aktualisiert.<br>';
    } else {
        echo '❌ config.json konnte nicht gelesen werden.<br>';
    }
} else {
    echo '❌ config.json nicht gefunden.<br>';
}

// Fertig
echo '🎉 Update abgeschlossen!<br>';
echo '<button class="kleinerBt" onclick="window.location.href=\'index.php\'">🔁 Zurück zur Startseite</button>';
