<?php

// Pfade definieren
define('SOURCE_FILE', '../daten/umsatz.csv');
define('BACKUP_DIR', '../backup/');

// Datum für den Dateinamen erzeugen
$date = date('y-m-d');
$backupFile = BACKUP_DIR . "umsatz - $date.csv";

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

?>

