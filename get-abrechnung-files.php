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
require_once __DIR__ . '/kasse/auth.php';

try {
    $config = loadKasseConfig();
} catch (Throwable $e) {
    echo '<p>❌ Konfiguration konnte nicht geladen werden.</p>';
    exit();
}

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    http_response_code(401);
    echo '<p>❌ Nicht autorisiert.</p>';
    exit();
}

$folderPath = __DIR__ . DIRECTORY_SEPARATOR . 'abrechnungen';

if (!is_dir($folderPath)) {
    echo '<p>Keine Abrechnungsarchive gefunden.</p>';
    exit();
}

if (isset($_GET['delete'])) {
    $deleteFile = basename((string)$_GET['delete']);
    $extension = strtolower(pathinfo($deleteFile, PATHINFO_EXTENSION));

    if ($extension !== 'zip') {
        echo '<p>❌ Ungültiger Dateityp.</p>';
        exit();
    }

    $filePath = $folderPath . DIRECTORY_SEPARATOR . $deleteFile;
    if (is_file($filePath)) {
        if (unlink($filePath)) {
            header('Location: portal.php?action=abrechnungsliste');
            exit();
        }
        echo '<p>❌ Die Datei konnte nicht gelöscht werden.</p>';
    } else {
        echo '<p>⚠️ Die Datei wurde nicht gefunden.</p>';
    }
}

try {
    $files = scandir($folderPath);
    if ($files === false) {
        throw new RuntimeException('Das Verzeichnis konnte nicht gelesen werden.');
    }

    $archiveFiles = array_values(array_filter($files, static function (string $file) use ($folderPath): bool {
        return strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'zip'
            && is_file($folderPath . DIRECTORY_SEPARATOR . $file);
    }));
    rsort($archiveFiles, SORT_NATURAL | SORT_FLAG_CASE);

    if ($archiveFiles === []) {
        echo '<p>Keine Abrechnungsarchive gefunden.</p>';
        exit();
    }

    echo '<p>📦 Abrechnungsarchive gefunden: ' . count($archiveFiles) . '</p>';

    foreach ($archiveFiles as $file) {
        $requestedFile = 'abrechnungen/' . $file;
        $timestamp = time();
        $signature = buildDownloadSignature($requestedFile, $timestamp, $config);
        $downloadLink = 'download.php?file=' . urlencode($requestedFile)
            . '&ts=' . $timestamp
            . '&sig=' . urlencode($signature);
        $deleteLink = 'get-abrechnung-files.php?delete=' . urlencode($file);

        echo '<p>'
            . '<a href="' . htmlspecialchars($deleteLink, ENT_QUOTES, 'UTF-8') . '" '
            . 'onclick="return confirm(\'Wirklich löschen?\');">🗑️</a>'
            . '&nbsp; '
            . '<a href="' . htmlspecialchars($downloadLink, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($file, ENT_QUOTES, 'UTF-8')
            . '</a>'
            . '</p>';
    }
} catch (Throwable $e) {
    echo '<p>❌ Fehler: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
}

