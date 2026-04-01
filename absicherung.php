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

session_start();

// Prüfen, ob der Benutzer eingeloggt ist

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php'); // Falls nicht eingeloggt, zurück zur Login-Seite
    exit();
}

// Configurationsdatei einbinden
    $jsonConfigDatei = file_get_contents("daten/config.json");
    $jsonConfigDaten = json_decode($jsonConfigDatei, true); // true gibt ein assoziatives Array zurück

//***Backup auf 700 setzten:
$path = __DIR__ . '/backup';

if (is_dir($path)) {
    if (chmod($path, 0700)) {
        echo "<p>✅ Berechtigung erfolgreich auf 0700 gesetzt für: $path</p>"; 
    } else {
        echo "<p>❌ Konnte Berechtigung nicht ändern für: $path</p>";
    }
} else {
    echo "<p>❌ Verzeichnis existiert nicht: $path</p>";
}

//***daten auf 700 setzten:
$path = __DIR__ . '/daten';

if (is_dir($path)) {
    if (chmod($path, 0700)) {
        echo "<p>✅ Berechtigung erfolgreich auf 0700 gesetzt für: $path</p>"; 
    } else {
        echo "<p>❌ Konnte Berechtigung nicht ändern für: $path</p>";
    }
} else {
    echo "<p>❌ Verzeichnis existiert nicht: $path</p>";
}


?>

