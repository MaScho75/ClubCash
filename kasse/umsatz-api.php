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

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

$file = "../daten/umsatz.csv";
$input = file_get_contents("php://input");

if (!$input) {
    echo json_encode(["status" => "error", "message" => "Keine Daten empfangen!"]);
    exit;
}

$data = json_decode($input, true);

if (!is_array($data)) {
    echo json_encode(["status" => "error", "message" => "Ungültige JSON-Daten!"]);
    exit;
}

$fileHandle = fopen($file, "a");

if (!$fileHandle) {
    echo json_encode(["status" => "error", "message" => "Fehler beim Öffnen der Datei!"]);
    exit;
}

foreach ($data as $row) {
    if (!is_array($row)) {
        echo json_encode(["status" => "error", "message" => "Fehlerhafte Zeilenstruktur!"]);
        fclose($fileHandle);
        exit;
    }
    // Korrigierter fputcsv-Aufruf mit explizitem Escape-Parameter
    fputcsv($fileHandle, $row, ";", '"', "\\");
}

fclose($fileHandle);

echo json_encode(["status" => "success", "message" => "Daten erfolgreich gespeichert!"]);
?>
