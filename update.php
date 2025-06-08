<?php
session_start();
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php'); exit();
}
if (!class_exists('ZipArchive')) {
    die('❌ ZipArchive nicht verfügbar - bitte aktivieren.');
}
if (!function_exists('curl_init')) {
    die('❌ cURL nicht verfügbar - bitte aktivieren.');
}

echo "🔄 Aktualisiere ClubCash...<br>";

function getGitHubData($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'ClubCash Update Script',
        CURLOPT_HTTPHEADER => ['Accept: application/vnd.github.v3+json'],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    if (curl_errno($ch)) {
        echo '❌ cURL-Fehler: ' . curl_error($ch) . '<br>';
        curl_close($ch);
        return false;
    }
    curl_close($ch);
    return $resp;
}

function downloadFile($url, $dest) {
    $fp = fopen($dest, 'w+');
    if (!$fp) return false;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'ClubCash Update Script',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    curl_exec($ch);
    $ok = !curl_errno($ch);
    curl_close($ch); fclose($fp);
    return $ok;
}

$owner = 'MaScho75';
$repo  = 'ClubCash';
$url   = "https://api.github.com/repos/$owner/$repo/releases";
$data  = getGitHubData($url);
if ($data === false) die('❌ Fehler beim GitHub-Abruf.');

$releases = json_decode($data, true);
if (!is_array($releases) || empty($releases)) {
    die('❌ Keine Releases gefunden.');
}

// Debug-Ausgabe der ersten Releases
echo '<h3>📋 GitHub API-Antwort (assets-Werte):</h3>';
echo '<pre>' . htmlspecialchars(json_encode(array_column($releases, 'assets'), JSON_PRETTY_PRINT)) . '</pre>';

$downloadUrl = null;
$zipName = null;
$tag = '(unbekannt)';

foreach ($releases as $rel) {
    if (!empty($rel['assets']) && is_array($rel['assets'])) {
        foreach ($rel['assets'] as $asset) {
            echo '→ Asset gefunden: ' . htmlspecialchars($asset['name']) . '<br>';
            if (preg_match('/\.zip$/i', $asset['name'])) {
                $downloadUrl = $asset['browser_download_url'];
                $zipName = $asset['name'];
                $tag = $rel['tag_name'] ?? $tag;
                break 2;
            }
        }
    }
}

if (!$downloadUrl) {
    die('⚠️ Keine ZIP-Asset-Datei in den Releases gefunden. Überprüfe Name, JSON-Ausgabe oben.');
}

echo "✅ Gefundenes Release: $tag<br>";
echo "⬇️ Herunterladen: $zipName<br>";

if (!downloadFile($downloadUrl, 'update.zip')) {
    die('❌ Fehler beim Herunterladen.');
}

echo "📦 Entpacken...<br>";
$zip = new ZipArchive;
if ($zip->open('update.zip') === true) {
    $zip->extractTo('.');
    $zip->close();
    unlink('update.zip');
    echo "✅ Entpackt.<br>";
} else {
    unlink('update.zip');
    die('❌ Entpackfehler.');
}

if (file_exists('daten/config.json')) {
    $cfg = json_decode(file_get_contents('daten/config.json'), true);
    if (is_array($cfg)) {
        $cfg['Version'] = $tag;
        $cfg['letzteAktualisierung'] = date('Y-m-d H:i:s');
        file_put_contents('daten/config.json', json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        echo "✅ config.json aktualisiert.<br>";
    } else {
        echo "❌ Fehler beim Parsen von config.json.<br>";
    }
} else {
    echo "⚠️ config.json nicht gefunden.<br>";
}

echo "🎉 Update abgeschlossen!<br>";
echo '<button onclick="location.href=\'index.php\'">Zurück zur Startseite</button>';

?>