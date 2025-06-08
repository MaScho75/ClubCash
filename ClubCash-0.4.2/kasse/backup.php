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

