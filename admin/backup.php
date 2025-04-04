<?php

// Pfade definieren
define('SOURCE_FILE', '../daten/verkaufsliste.csv');
define('BACKUP_DIR', '../backup/');

// Datum für den Dateinamen erzeugen
$date = date('y-m-d');
$backupFile = BACKUP_DIR . "verkaufsliste - $date.csv";

// Prüfen, ob die Sicherung für heute bereits existiert
if (!file_exists($backupFile)) {
    
    // Datei kopieren
    if (copy(SOURCE_FILE, $backupFile)) {
        echo "Backup erfolgreich erstellt: $backupFile";
    } else {
        echo "Fehler beim Erstellen des Backups.";
    }
} else {
    echo "Backup für heute existiert bereits.";
}

if (copy(SOURCE_FILE, $backupFile2)) {
    echo "Backup2 erfolgreich erstellt: $backupFile";
} else {
    echo "Fehler beim Erstellen des Backup2.";
}

?>

