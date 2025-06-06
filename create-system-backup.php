<?php
session_start();

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php');
    exit();
}

//überprüfe, ob das System zip-Archive unterstützt
if (!class_exists('ZipArchive')) {
    echo "Das System unterstützt keine Zip-Archive. Bitte installieren Sie die ZipArchive-Erweiterung.";
    exit();
}

$backupDir = 'backups/';
$backupFile = $backupDir . 'system_backup_' . date('Y-m-d_H-i-s') . '.zip';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$zip = new ZipArchive();
if ($zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('.'),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (!$file->isDir() && strpos($file->getRealPath(), $backupDir) === false) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(realpath('.')) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }

    $zip->close();
    echo "Backup erfolgreich erstellt: " . htmlspecialchars($backupFile);
} else {
    echo "Fehler beim Erstellen des Backups";
}

exit();
?>
