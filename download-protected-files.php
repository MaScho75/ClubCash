<?php

/*
 * This file is part of ClubCash.
 *
 * ClubCash is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 */

session_start();

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Nicht autorisiert';
    exit();
}

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'ZipArchive ist auf dem Server nicht verfügbar.';
    exit();
}

$baseDir = __DIR__;
$sourceDirs = [
    $baseDir . DIRECTORY_SEPARATOR . 'backup',
    $baseDir . DIRECTORY_SEPARATOR . 'daten',
];

$zipFile = tempnam(sys_get_temp_dir(), 'clubcash_zip_');
if ($zipFile === false) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Temporäre Datei konnte nicht erstellt werden.';
    exit();
}

$zipPath = $zipFile . '.zip';
rename($zipFile, $zipPath);

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    @unlink($zipPath);
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'ZIP-Archiv konnte nicht erstellt werden.';
    exit();
}

foreach ($sourceDirs as $sourceDir) {
    if (!is_dir($sourceDir)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        $filePath = $fileInfo->getRealPath();
        if ($filePath === false) {
            continue;
        }

        $relativePath = substr($filePath, strlen($baseDir) + 1);
        $zip->addFile($filePath, $relativePath);
    }
}

$zip->close();

$downloadName = 'ClubCash_geschuetzte_Dateien_' . date('Y-m-d_H-i-s') . '.zip';

if (function_exists('ini_set')) {
    @ini_set('zlib.output_compression', 'Off');
}
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($zipPath));
header('Content-Description: File Transfer');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');

readfile($zipPath);
@unlink($zipPath);

exit();
