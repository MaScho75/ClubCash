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
    header('Location: index.php');
    exit();
}

// Überprüfen, ob ZipArchive unterstützt wird
if (!class_exists('ZipArchive')) {
    echo "<p>⚠️ Das System unterstützt keine Zip-Archive.<br>Bitte installieren Sie die ZipArchive-Erweiterung.<br>Ein Backup ist nicht möglich.</p>";
    exit();
}

$backupDir = 'backup';
$timestamp = date('Y-m-d_H-i-s');
$backupFile = $backupDir . DIRECTORY_SEPARATOR . 'ClubCash_Systembackup_' . $timestamp . '.zip';

// Backup-Ordner erstellen, falls nicht vorhanden
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$zip = new ZipArchive();
if ($zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {

    $excludeDirs = [
        realpath($backupDir),           // den Backup-Ordner selbst ausschließen
        realpath('.git'),               // Git (falls vorhanden)
        realpath('vendor'),             // Composer vendor-Ordner (optional)
    ];

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('.', FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if ($file->isFile()) {
            $filePath = $file->getRealPath();

            // Ausschluss prüfen
            $exclude = false;
            foreach ($excludeDirs as $dir) {
                if ($dir !== false && strpos($filePath, $dir) === 0) {
                    $exclude = true;
                    break;
                }
            }
            if ($exclude) continue;

            $relativePath = substr($filePath, strlen(realpath('.')) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }

    $zip->close();
    
    $backupFileName = basename($backupFile);
    
    echo "<p>✅ Backup erfolgreich erstellt:</p>";
    echo "<a class='kleinerBt' href='javascript:void(0)' onclick='return downloadBackup(\"" . 
         htmlspecialchars($backupFileName, ENT_QUOTES) . "\");'>" . 
         htmlspecialchars($backupFileName) . "</a>";
    
    // JavaScript für sicheren Download
    echo <<<HTML
    <script>
        function downloadBackup(filename) {
            if (!confirm('Möchten Sie das Backup ' + filename + ' herunterladen?')) {
                return false;
            }
            
            fetch('download.php?file=' + encodeURIComponent(filename), {
                method: 'GET',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) throw new Error('Download fehlgeschlagen');
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                setTimeout(() => {
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                }, 100);
            })
            .catch(error => {
                console.error('Download Fehler:', error);
                alert('Fehler beim Download: ' + error.message);
            });
            
            return false;
        }
    </script>
HTML;

} else {
    echo "<p>❌ Fehler beim Erstellen des Backups.</p>";
}
?>
