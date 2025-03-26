<?php

// Pfade definieren
define('SOURCE_FILE', 'daten/verkaufsliste.csv');
define('BACKUP_DIR', 'backup/');
define('BACKUP_DIR2', 'https://schommer.berlin/ClubCash_DatenBackup/');

// Datum für den Dateinamen erzeugen
$date = date('y-m-d');
$backupFile = BACKUP_DIR . "verkaufsliste - $date.csv";
$backupFile2 = BACKUP_DIR2 . "verkaufsliste - $date.csv";

// Prüfen, ob die Sicherung für heute bereits existiert
if (!file_exists($backupFile)) {
    // Sicherstellen, dass das Backup-Verzeichnis existiert
    if (!is_dir(BACKUP_DIR)) {
        mkdir(BACKUP_DIR, 0777, true);
    }
    
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

