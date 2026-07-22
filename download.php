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
require_once __DIR__ . '/kasse/auth.php';

try {
    $config = loadKasseConfig();
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Konfiguration konnte nicht geladen werden';
    exit();
}

function hasDownloadAccess(array $config): bool
{
    if (isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true) {
        return true;
    }

    $requestedFile = str_replace('\\', '/', (string)($_GET['file'] ?? ''));
    $signature = (string)($_GET['sig'] ?? '');
    $timestamp = (int)($_GET['ts'] ?? 0);

    if ($requestedFile === '' || $signature === '' || $timestamp === 0) {
        return false;
    }

    return isValidDownloadSignature($requestedFile, $signature, $timestamp, $config);
}

// Prüfen, ob der Benutzer eingeloggt ist oder eine gültige Download-Signatur hat
if (!hasDownloadAccess($config)) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Nicht autorisiert';
    exit();
}

// Prüfen ob Dateiname übergeben wurde
if (!isset($_GET['file'])) {
    die('Kein Dateiname angegeben');
}

$requestedFile = str_replace('\\', '/', $_GET['file']);
$downloadName = basename($requestedFile);
$baseDir = __DIR__;

$allowedDataFiles = [
    'daten/produkte.json',
    'daten/kunden.json',
    'daten/externe.json',
    'daten/umsatz.csv',
];

if (in_array($requestedFile, $allowedDataFiles, true)) {
    $filepath = $baseDir . DIRECTORY_SEPARATOR . $requestedFile;
} elseif (preg_match('#^abrechnungen/[^/]+\.zip$#', $requestedFile) === 1) {
    $filename = basename($requestedFile);
    $filepath = $baseDir . DIRECTORY_SEPARATOR . 'abrechnungen' . DIRECTORY_SEPARATOR . $filename;
    $downloadName = $filename;
} else {
    $filename = basename($requestedFile); // Schutz vor Directory Traversal
    $filepath = $baseDir . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . $filename;
    $downloadName = $filename;
}

// Sicherheitsprüfungen
clearstatcache(true, $filepath);
if (!is_file($filepath)) {
    die('Datei nicht gefunden');
}

// Dateigröße ermitteln
$filesize = filesize($filepath);
if ($filesize === false) {
    die('Fehler beim Ermitteln der Dateigröße');
}

if ($filesize === 0) {
    die('Datei ist leer');
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

// Ausgabe-Kompression für Binärdownloads deaktivieren
if (function_exists('ini_set')) {
    @ini_set('zlib.output_compression', 'Off');
}
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $safeDownloadName . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . $filesize);
header('Content-Description: File Transfer');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');

// Datei ausgeben
if (readfile($filepath) === false) {
    die('Fehler beim Lesen der Datei');
}

exit();
