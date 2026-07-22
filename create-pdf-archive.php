<?php

/*
 * This file is part of ClubCash.
 *
 * ClubCash is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 */

declare(strict_types=1);

session_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/kasse/auth.php';

use Dompdf\Dompdf;
use Dompdf\Options;

header('Content-Type: application/json; charset=UTF-8');

function pdfArchiveResponse(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function parseArchiveDate(string $value): DateTimeImmutable
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        throw new InvalidArgumentException('Ungültiges Datumsformat.');
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if ($date === false || $date->format('Y-m-d') !== $value) {
        throw new InvalidArgumentException('Ungültiges Datum.');
    }

    return $date;
}

function cleanArchiveFilenamePart(string $value): string
{
    $value = preg_replace('/[<>:"\/\\\\|?*\x00-\x1F]/u', '_', $value) ?? '';
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    $value = trim($value, " .\t\n\r\0\x0B");

    if ($value === '') {
        return 'Unbekannt';
    }

    return mb_substr($value, 0, 60, 'UTF-8');
}

if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    pdfArchiveResponse(401, ['success' => false, 'message' => 'Nicht autorisiert.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    pdfArchiveResponse(405, ['success' => false, 'message' => 'Nur POST-Anfragen sind erlaubt.']);
}

if (function_exists('set_time_limit')) {
    @set_time_limit(0);
}

if (!class_exists(Dompdf::class)) {
    pdfArchiveResponse(500, ['success' => false, 'message' => 'Die PDF-Bibliothek ist nicht installiert.']);
}

if (!class_exists('ZipArchive')) {
    pdfArchiveResponse(500, ['success' => false, 'message' => 'Die ZIP-Erweiterung ist nicht verfügbar.']);
}

$rawInput = file_get_contents('php://input');
$input = is_string($rawInput) ? json_decode($rawInput, true) : null;
if (!is_array($input)) {
    pdfArchiveResponse(400, ['success' => false, 'message' => 'Die übertragenen Daten sind ungültig.']);
}

try {
    $config = loadKasseConfig();
    $startDate = parseArchiveDate((string)($input['datum1'] ?? ''));
    $endDate = parseArchiveDate((string)($input['datum2'] ?? ''));

    if ($startDate > $endDate) {
        throw new InvalidArgumentException('Das Startdatum liegt nach dem Enddatum.');
    }

    $invoices = $input['abrechnungen'] ?? null;
    if (!is_array($invoices) || count($invoices) === 0) {
        throw new InvalidArgumentException('Es wurden keine Abrechnungen übergeben.');
    }
    if (count($invoices) > 2000) {
        throw new InvalidArgumentException('Es wurden zu viele Abrechnungen übergeben.');
    }

    $archiveDir = __DIR__ . DIRECTORY_SEPARATOR . 'abrechnungen';
    $tempDir = $archiveDir . DIRECTORY_SEPARATOR . 'temp';

    foreach ([$archiveDir, $tempDir] as $directory) {
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Das Verzeichnis konnte nicht erstellt werden: ' . basename($directory));
        }
    }

    $lockHandle = fopen($archiveDir . DIRECTORY_SEPARATOR . '.pdf-archive.lock', 'c');
    if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
        if (is_resource($lockHandle)) {
            fclose($lockHandle);
        }
        pdfArchiveResponse(409, ['success' => false, 'message' => 'Ein PDF-Archiv wird bereits erstellt.']);
    }

    foreach (glob($tempDir . DIRECTORY_SEPARATOR . '*.pdf') ?: [] as $oldPdf) {
        if (is_file($oldPdf) && !unlink($oldPdf)) {
            throw new RuntimeException('Eine alte temporäre PDF-Datei konnte nicht entfernt werden.');
        }
    }

    $datePrefix = $endDate->format('y-m-d');
    $createdPdfs = [];
    $usedNames = [];

    foreach ($invoices as $index => $invoice) {
        if (!is_array($invoice)) {
            throw new InvalidArgumentException('Abrechnung ' . ($index + 1) . ' ist ungültig.');
        }

        $html = (string)($invoice['html'] ?? '');
        if ($html === '' || strlen($html) > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('Der Inhalt von Abrechnung ' . ($index + 1) . ' ist ungültig.');
        }

        $lastname = cleanArchiveFilenamePart((string)($invoice['name'] ?? ''));
        $firstname = cleanArchiveFilenamePart((string)($invoice['vorname'] ?? ''));
        $baseName = $datePrefix . ' - ' . $lastname . ', ' . $firstname . ' - Abrechnung ClubCash';
        $pdfName = $baseName . '.pdf';
        $copyNumber = 2;
        while (isset($usedNames[mb_strtolower($pdfName, 'UTF-8')])) {
            $pdfName = $baseName . ' (' . $copyNumber . ').pdf';
            $copyNumber++;
        }
        $usedNames[mb_strtolower($pdfName, 'UTF-8')] = true;

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->setChroot(__DIR__);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        $pdfPath = $tempDir . DIRECTORY_SEPARATOR . $pdfName;
        if (file_put_contents($pdfPath, $dompdf->output()) === false || !is_file($pdfPath) || filesize($pdfPath) === 0) {
            throw new RuntimeException('PDF-Datei konnte nicht erstellt werden: ' . $pdfName);
        }

        $createdPdfs[] = ['path' => $pdfPath, 'name' => $pdfName];
        unset($dompdf);
    }

    $zipName = $datePrefix . ' - ClubCash-Abrechung.zip';
    $zipPath = $archiveDir . DIRECTORY_SEPARATOR . $zipName;
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Das ZIP-Archiv konnte nicht erstellt werden.');
    }

    foreach ($createdPdfs as $pdf) {
        if (!$zip->addFile($pdf['path'], $pdf['name'])) {
            $zip->close();
            throw new RuntimeException('Eine PDF-Datei konnte dem ZIP-Archiv nicht hinzugefügt werden.');
        }
    }

    if (!$zip->close() || !is_file($zipPath) || filesize($zipPath) === 0) {
        throw new RuntimeException('Das ZIP-Archiv konnte nicht abgeschlossen werden.');
    }

    $requestedFile = 'abrechnungen/' . $zipName;
    $timestamp = time();
    $signature = buildDownloadSignature($requestedFile, $timestamp, $config);
    $downloadUrl = 'download.php?file=' . urlencode($requestedFile)
        . '&ts=' . $timestamp
        . '&sig=' . urlencode($signature);

    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);

    pdfArchiveResponse(200, [
        'success' => true,
        'message' => 'PDF-Archiv erfolgreich erstellt.',
        'filename' => $zipName,
        'count' => count($createdPdfs),
        'downloadUrl' => $downloadUrl,
    ]);
} catch (Throwable $e) {
    if (isset($lockHandle) && is_resource($lockHandle)) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
    $status = $e instanceof InvalidArgumentException ? 400 : 500;
    pdfArchiveResponse($status, ['success' => false, 'message' => $e->getMessage()]);
}

