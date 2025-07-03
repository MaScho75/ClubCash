
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

$filename = basename($_GET['file']); // Schutz vor Directory Traversal
$filepath = 'backup/' . $filename;

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
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $filesize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Datei ausgeben
if (readfile($filepath) === false) {
    die('Fehler beim Lesen der Datei');
}
?>