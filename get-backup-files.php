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

if (!is_dir($folderPath)) {
    echo "<p>❌ Der Ordner '$folderPath' existiert nicht.</p>";
    exit();
}

// Datei löschen, falls der Parameter gesetzt ist
if (isset($_GET['delete'])) {
    $deleteFile = basename($_GET['delete']); // schützt vor Pfadmanipulation
    $filePath = $folderPath . '/' . $deleteFile;

    if (is_file($filePath)) {
        if (unlink($filePath)) {
            // ✅ Erfolgreich gelöscht → weiterleiten zu portal.php mit Funktionsparameter
            header('Location: portal.php?action=backupliste');
            exit();
            
        } else {
            echo "<p>❌ Fehler beim Löschen von '$deleteFile'.</p>";
        }
    } else {
        echo "<p>⚠️ Datei '$deleteFile' nicht gefunden.</p>";
    }
}

// Dateien anzeigen (optional)
$files = scandir($folderPath);
$files = array_filter($files, fn($file) => is_file($folderPath . '/' . $file));

if (empty($files)) {
    echo "<p>Keine Backups gefunden.</p>";
} else {
    echo "<p>📦 Backups gefunden: " . count($files) . "</p>";
    foreach ($files as $file) {
        $fileUrl = $folderPath . '/' . $file;
        $deleteLink = $_SERVER['PHP_SELF'] . '?delete=' . urlencode($file);
        echo "<p>
                <a href=\"$deleteLink\" onclick=\"return confirm('Wirklich löschen?');\">🗑️</a>
                &nbsp;
                <a href=\"$fileUrl\" download>$file</a>
              </p>";
    }
}

?>
