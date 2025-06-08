<?php
session_start();

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php');
    exit();
}

//überprüfe, ob das System zip-Archive unterstützt
if (!class_exists('ZipArchive')) {
    echo "<p>⚠️ Das System unterstützt keine Zip-Archive.<br> Bitte installieren Sie die ZipArchive-Erweiterung. <br>Ein Backup ist nicht möglich.</p>";
    exit();
}

$backupDir = 'backup/';
$backupFile = $backupDir . 'ClubCash_Systembackup_' . date('Y-m-d_H-i-s') . '.zip';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$zip = new ZipArchive();
if ($zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('.'),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    // First, ensure the backup directory exists in the ZIP
    $zip->addEmptyDir($backupDir);

    foreach ($files as $file) {
        // Skip if it's a directory or if the path contains the backup directory
        if (!$file->isDir() && strpos($file->getRealPath(), $backupDir) === false) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(realpath('.')) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }

    $zip->close();
    echo "Backup erfolgreich erstellt: " . htmlspecialchars($backupFile);
    echo "<br><button class='kleinerBt' onclick=\"window.location.href='" . htmlspecialchars($backupFile) . "'\">Download</button>";

} else {
    echo "Fehler beim Erstellen des Backups";
}

exit();
?>
