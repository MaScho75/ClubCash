
<?php
session_start();

// Prüfen ob Benutzer authentifiziert ist
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

// Gewünschte Datei aus Query-Parameter
$file = $_GET['file'] ?? '';
$filepath = 'daten/' . basename($file);

// Sicherheitscheck: Nur Dateien im daten-Verzeichnis erlauben
if (!file_exists($filepath) || dirname(realpath($filepath)) !== realpath('daten')) {
    header('HTTP/1.0 404 Not Found');
    exit('File not found');
}

// MIME-Type ermitteln und senden
$mime = mime_content_type($filepath);
header('Content-Type: ' . $mime);
readfile($filepath);

?>