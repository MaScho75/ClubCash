<?php
session_start();
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php'); exit();
}
if (!class_exists('ZipArchive')) {
    die('❌ ZipArchive nicht verfügbar.');
}
if (!function_exists('curl_init')) {
    die('❌ cURL nicht verfügbar.');
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
    curl_close($ch);
    fclose($fp);
    return $ok;
}

$owner = 'MaScho75';
$repo  = 'ClubCash';
$apiUrl = "https://api.github.com/repos/$owner/$repo/releases/latest";

$response = getGitHubData($apiUrl);
if ($response === false) die('❌ Fehler beim GitHub-Abruf.');

$release = json_decode($response, true);
if (!is_array($release) || empty($release)) die('❌ Keine Releases gefunden.');

$tag = $release['tag_name'] ?? null;
if (!$tag) die('❌ Keine Tag-Information im Release.');

echo "⬇️ Gefundene Version: $tag<br>";

// GitHub-Source-Code ZIP-URL (immer verfügbar)
$zipUrl = "https://github.com/$owner/$repo/archive/refs/tags/$tag.zip";
$zipFile = 'update.zip';

echo "⬇️ Lade Quellcode-ZIP herunter: $zipUrl<br>";

if (!downloadFile($zipUrl, $zipFile)) {
    die('❌ Fehler beim Herunterladen der ZIP-Datei.');
}

echo "📦 Entpacken...<br>";
$zip = new ZipArchive;
if ($zip->open($zipFile) === true) {
    $zip->extractTo('.');
    $zip->close();
    unlink($zipFile);
    echo "✅ Entpackt.<br>";
} else {
    unlink($zipFile);
    die('❌ Entpackfehler.');
}

$configPath = 'daten/config.json';
if (file_exists($configPath)) {
    $config = json_decode(file_get_contents($configPath), true);
    if (is_array($config)) {
        $config['Version'] = $tag;
        $config['letzteAktualisierung'] = date('Y-m-d H:i:s');
        file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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
