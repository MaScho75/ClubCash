<?php

session_start();

// Authentifizierung prüfen
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php');
    exit();
}

// ZipArchive prüfen
if (!class_exists('ZipArchive')) {
    die('❌ Das System unterstützt keine Zip-Archive. Bitte die ZipArchive-Erweiterung aktivieren.');
}

// cURL-Verfügbarkeit prüfen
if (!function_exists('curl_init')) {
    die('❌ cURL ist auf diesem Server nicht verfügbar. Bitte aktiviere die PHP-cURL-Erweiterung.');
}

echo '🔄 Aktualisiere ClubCash...<br>';

// GitHub API mit cURL abrufen
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

// Datei herunterladen mit cURL
function downloadFile($url, $path) {
    $fp = fopen($path, 'w+');
    if (!$fp) {
        echo '❌ Fehler beim Erstellen der Datei: ' . htmlspecialchars($path) . '<br>';
        return false;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ClubCash Update Script');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_exec($ch);
    $success = !curl_errno($ch);
    if (!$success) {
        echo '❌ Fehler beim Download: ' . curl_error($ch) . '<br>';
    }
    curl_close($ch);
