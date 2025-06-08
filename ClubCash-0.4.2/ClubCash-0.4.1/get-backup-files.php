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


$folderPath = 'backup'; // Der Ordner mit den Backups
$files = scandir($folderPath); // Listet alle Dateien im Ordner auf

// Filtere "." und ".." aus
$files = array_diff($files, array('.', '..'));

echo "<ul>";
foreach ($files as $file) {
    $filePath = $folderPath . '/' . $file;
    if (is_file($filePath)) {
        echo "<li><a href='$filePath' download>$file</a></li>"; // Zeigt Download-Link für jede Datei
    }
}
echo "</ul>";
?>
