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

// GitHub-Daten abrufen
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

// Datei herunterladen
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

// Rekursiv Ordner löschen
function deleteDir($dirPath) {
    if (!is_dir($dirPath)) return;
    $items = scandir($dirPath);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dirPath . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            deleteDir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dirPath);
}

// GitHub-Repo-Daten
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

// Verschiebe Dateien aus dem Unterordner ins Hauptverzeichnis
$extractedFolder = "$repo-" . ltrim($tag, 'v');
if (is_dir($extractedFolder)) {
    echo "🚚 Verschiebe Dateien aus $extractedFolder...<br>";
    $files = scandir($extractedFolder);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === basename(__FILE__) || $file === 'install.php') {
            continue;
        }

        $source = "$extractedFolder/$file";
        $target = $file;

        // Existierende Datei/Ordner löschen
        if (file_exists($target)) {
            if (is_dir($target)) {
                deleteDir($target);
            } else {
                unlink($target);
            }
        }

        // Verschieben
        if (!rename($source, $target)) {
            echo "❌ Fehler beim Verschieben von '$file'<br>";
        } else {
            echo "✅ Verschoben: $file<br>";
        }
    }

    // Entpackten Ordner löschen
    deleteDir($extractedFolder);
    echo "✅ Alle Dateien verschoben.<br>";
} else {
    die("❌ Entpackter Ordner '$extractedFolder' nicht gefunden.");
}

// config.json aktualisieren
$configPath = 'daten/config.json';
if (file_exists($configPath)) {
    $config = json_decode(file_get_contents($configPath), true);
    if (is_array($config)) {
        $config['Version'] = $tag;
        $config['letzteAktualisierung'] = date('Y-m-d H:i:s');
        file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        echo "✅ config.json aktualisiert.<br>";
    } else {
        echo "❌ Fehler beim Parsen von config.json: " . json_last_error_msg() . "<br>";
    }
} else {
    echo "⚠️ config.json nicht gefunden.<br>";
}

echo "🎉 Update abgeschlossen!<br>";
echo '<button onclick="location.href=\'index.php\'">Zurück zur Startseite</button>';
?>
