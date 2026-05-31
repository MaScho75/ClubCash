
<?php
/*
 * This file is part of ClubCash.
 *
 * ClubCash is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 */

session_start();

// Prüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php');
    exit();
}

// Prüfen ob Dateiname übergeben wurde
if (!isset($_GET['file'])) {
    die('Kein Dateiname angegeben');
}

$requestedFile = str_replace('\\', '/', $_GET['file']);
$downloadName = basename($requestedFile);

$allowedDataFiles = [
    'daten/produkte.json',
    'daten/kunden.json',
    'daten/externe.json',
    'daten/umsatz.csv',
];

if (in_array($requestedFile, $allowedDataFiles, true)) {
    $filepath = $requestedFile;
} else {
    $filename = basename($requestedFile); // Schutz vor Directory Traversal
    $filepath = 'backup/' . $filename;
    $downloadName = $filename;
}

// Sicherheitsprüfungen
if (!is_file($filepath)) {
    die('Datei nicht gefunden');
}

// Dateigröße ermitteln
$filesize = filesize($filepath);
if ($filesize === false) {
    die('Fehler beim Ermitteln der Dateigröße');
}

// Download-Header setzen
$extension = strtolower(pathinfo($downloadName, PATHINFO_EXTENSION));
$contentTypes = [
    'csv' => 'text/csv; charset=UTF-8',
    'json' => 'application/json; charset=UTF-8',
    'zip' => 'application/zip',
];
$contentType = $contentTypes[$extension] ?? 'application/octet-stream';
$safeDownloadName = str_replace(['"', "\r", "\n"], '', $downloadName);

header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $safeDownloadName . '"');
header('Content-Length: ' . $filesize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Datei ausgeben
if (readfile($filepath) === false) {
    die('Fehler beim Lesen der Datei');
}
?>
