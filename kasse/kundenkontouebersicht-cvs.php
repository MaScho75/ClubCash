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

// Pfad zur CSV-Datei
$file = "../daten/umsatz.csv";

$kundennummer = json_decode(file_get_contents("php://input"), true);

// Datei lesen
if (($handle = fopen($file, "r")) !== FALSE) {
    // Spaltenüberschriften überspringen
    $headers = fgetcsv($handle, 1000, ";");
    
    // Array, um die Ergebnisse zu speichern
    $kundenData = [];

    // Zeilen durchgehen
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
   
        $kundeDS = $data[4];

        if ($kundennummer == $kundeDS) {
            $kundenData[] = array_combine($headers, $data);
        }
    }

    fclose($handle);

    // Die gefilterten Daten als JSON zurückgeben
    //echo json_encode(["status" => "success", "data" => $kundenData]);

    echo json_encode([
        "status" => "success", 
        "headers" => $headers,
        "data" => $kundenData
    ]);
    
} else {
    echo json_encode(["status" => "error", "message" => "Die umsatz konnte nicht geöffnet werden!"]);
}

?>
