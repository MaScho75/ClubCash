<?php
session_start();

// Prüfen, ob der Benutzer eingeloggt ist

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php'); // Falls nicht eingeloggt, zurück zur Login-Seite
    exit();
}

$installFiles = [
    'install.php'
    // Add other installation files here
];

$success = true;
foreach ($installFiles as $file) {
    if (file_exists($file)) {
        if (!unlink($file)) {
            $success = false;
            echo "Konnte nicht gelöscht werden: $file<br>";
        } else {
            echo "Erfolgreich gelöscht: $file<br>";
        }
    }
}

if ($success) {
    echo "Alle Sicherheitsrelevanten Dateien wurden erfolgreich entfernt.";
} else {
    echo "Einige Dateien konnten nicht gelöscht werden. Bitte überprüfen Sie die Berechtigungen.";
}
?>