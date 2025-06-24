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
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Pfad zur CSV-Datei
$file = "../daten/umsatz.csv";

// Heute im Format YYYY-MM-DD
date_default_timezone_set('Europe/Berlin');
$today = date("Y-m-d");

// Datei lesen
if (($handle = fopen($file, "r")) !== FALSE) {
    // Spaltenüberschriften überspringen
    $headers = fgetcsv($handle, 1000, ";");
    
    // Array, um die Ergebnisse zu speichern
    $todayData = [];

    // Zeilen durchgehen
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        // Datum aus der CSV-Datei (erste Spalte)
        $date = $data[0];

        // Wenn das Datum mit dem heutigen übereinstimmt, fügen wir es zum Array hinzu
        if ($date == $today) {
            $todayData[] = array_combine($headers, $data);
        }
    }

    fclose($handle);

    // Die gefilterten Daten als JSON zurückgeben
    echo json_encode([
        "status" => "success", 
        "headers" => $headers,
        "data" => $todayData
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Die umsatz konnte nicht abgerufen werden!"]);
}
?>
