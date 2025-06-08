<?php
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

    $downloadUrl = $backupDir . '/' . basename($backupFile);

    echo "<p>✅ Backup erfolgreich erstellt:</p>";
    echo "<a class='kleinerBt' href='" . htmlspecialchars($downloadUrl) . "' download>ClubCash_Systembackup_" . $timestamp . ".zip</a>";
 
} else {
    echo "<p>❌ Fehler beim Erstellen des Backups.</p>";
}
?>
