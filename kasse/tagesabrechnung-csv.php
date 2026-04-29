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

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

$file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'daten' . DIRECTORY_SEPARATOR . 'umsatz.csv';

date_default_timezone_set('Europe/Berlin');
$today = date('Y-m-d');

$fallbackHeaders = ['Datum', 'Zeit', 'Terminal', 'SchlÃ¼ssel', 'Kundennummer', 'EAN', 'Produkt', 'Kategorie', 'Preis', 'MwSt'];
$requiredHeaderIndexes = [
    0 => 'Datum',
    1 => 'Zeit',
    2 => 'Terminal',
    4 => 'Kundennummer',
    6 => 'Produkt',
    8 => 'Preis',
];

try {
    if (!file_exists($file)) {
        throw new Exception('Die Datei umsatz.csv existiert nicht.');
    }

    $handle = fopen($file, 'r');
    if ($handle === false) {
        throw new Exception('Die Datei konnte nicht geöffnet werden.');
    }

    $firstRow = fgetcsv($handle, 0, ';', '"', '\\');
    if ($firstRow === false) {
        fclose($handle);
        echo json_encode([
            'status' => 'success',
            'headers' => $fallbackHeaders,
            'data' => []
        ]);
        exit;
    }

    $isHeaderRow = true;
    foreach ($requiredHeaderIndexes as $index => $expectedValue) {
        if (!isset($firstRow[$index]) || trim((string) $firstRow[$index]) !== $expectedValue) {
            $isHeaderRow = false;
            break;
        }
    }

    $headers = $isHeaderRow ? $fallbackHeaders : $fallbackHeaders;
    $headerCount = count($headers);
    $todayData = [];

    $normalizeAndAddRow = function (array $row) use (&$todayData, $headers, $headerCount, $today): void {
        if (count($row) === 1 && trim((string) $row[0]) === '') {
            return;
        }

        if (count($row) < $headerCount) {
            $row = array_pad($row, $headerCount, '');
        } elseif (count($row) > $headerCount) {
            $row = array_slice($row, 0, $headerCount);
        }

        if (($row[0] ?? '') !== $today) {
            return;
        }

        $combined = array_combine($headers, $row);
        if (is_array($combined)) {
            $todayData[] = $combined;
        }
    };

    if (!$isHeaderRow) {
        $normalizeAndAddRow($firstRow);
    }

    while (($data = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
        $normalizeAndAddRow($data);
    }

    fclose($handle);

    echo json_encode([
        'status' => 'success',
        'headers' => $headers,
        'data' => $todayData
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
