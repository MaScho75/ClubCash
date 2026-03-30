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


// Header für JSON und CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

ini_set('display_errors', '0');
error_reporting(E_ALL);

// Pfad zur CSV-Datei
$file = "../daten/umsatz.csv";

$kundennummerInput = json_decode(file_get_contents("php://input"), true);
$kundennummer = is_array($kundennummerInput) ? ($kundennummerInput['kundennummer'] ?? '') : $kundennummerInput;
$kundennummer = (string)$kundennummer;

if ($kundennummer === '') {
    echo json_encode(["status" => "error", "message" => "Keine Kundennummer übergeben."]);
    exit;
}

// Datei lesen
if (($handle = fopen($file, "r")) !== FALSE) {
    $fallbackHeaders = ['Datum', 'Zeit', 'Terminal', 'Schlüssel', 'Kundennummer', 'EAN', 'Produkt', 'Kategorie', 'Preis', 'MwSt'];
    $requiredHeaders = ['Datum', 'Zeit', 'Terminal', 'Produkt', 'Preis', 'Kundennummer'];

    // Erste Zeile lesen (kann Header oder bereits Datensatz sein)
    $firstRow = fgetcsv($handle, 0, ";");

    if ($firstRow === false) {
        fclose($handle);
        echo json_encode([
            "status" => "success",
            "headers" => $fallbackHeaders,
            "data" => []
        ]);
        exit;
    }

    $isHeaderRow = true;
    foreach ($requiredHeaders as $requiredHeader) {
        if (!in_array($requiredHeader, $firstRow, true)) {
            $isHeaderRow = false;
            break;
        }
    }

    $headers = $isHeaderRow ? $firstRow : $fallbackHeaders;
    $headerCount = count($headers);
    $kundenData = [];

    $normalizeAndAddRow = function(array $row) use (&$kundenData, $headers, $headerCount, $kundennummer) {
        // Leere Zeilen ignorieren
        if (count($row) === 1 && trim((string)$row[0]) === '') {
            return;
        }

        if (count($row) < $headerCount) {
            $row = array_pad($row, $headerCount, '');
        } elseif (count($row) > $headerCount) {
            $row = array_slice($row, 0, $headerCount);
        }

        $kundeDS = $row[4] ?? '';
        if ((string)$kundeDS === $kundennummer) {
            $combined = array_combine($headers, $row);
            if (is_array($combined)) {
                $kundenData[] = $combined;
            }
        }
    };

    // Wenn keine gültige Headerzeile vorhanden ist, erste Zeile als Datensatz behandeln
    if (!$isHeaderRow) {
        $normalizeAndAddRow($firstRow);
    }

    while (($data = fgetcsv($handle, 0, ";")) !== false) {
        $normalizeAndAddRow($data);
    }

    fclose($handle);

    echo json_encode([
        "status" => "success",
        "headers" => $headers,
        "data" => $kundenData
    ]);

} else {
    echo json_encode(["status" => "error", "message" => "Die umsatz konnte nicht geöffnet werden!"]);
}

?>
