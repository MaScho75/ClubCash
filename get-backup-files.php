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

// Dateien anzeigen mit Fehlerbehandlung
try {
    $files = @scandir($folderPath);
    
    if ($files === false) {
        throw new Exception("Konnte Verzeichnis nicht lesen");
    }
    
    // Filtere nur echte Dateien (keine . und ..)
    $backupFiles = array_filter($files, function($file) use ($folderPath) {
        return is_file($folderPath . '/' . $file);
    });

    if (empty($backupFiles)) {
        echo "<p>Keine Backups gefunden.</p>";
    } else {
        echo "<p>📦 Backups gefunden: " . count($backupFiles) . "</p>";
        foreach ($backupFiles as $file) {
            $deleteLink = $_SERVER['PHP_SELF'] . '?delete=' . urlencode($file);
            $downloadLink = 'download.php?file=' . urlencode($file);
            echo "<p>
                    <a href=\"$deleteLink\" onclick=\"return confirm('Wirklich löschen?');\">🗑️</a>
                    &nbsp;
                    <a href=\"$downloadLink\">" . htmlspecialchars($file) . "</a>
                  </p>";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?>
