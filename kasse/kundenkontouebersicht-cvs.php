<?php

// Header für JSON und CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Pfad zur CSV-Datei
$file = "../daten/verkaufsliste.csv";

$kundennummer = json_decode(file_get_contents("php://input"), true);

// Datei lesen
if (($handle = fopen($file, "r")) !== FALSE) {
    // Spaltenüberschriften überspringen
    $headers = fgetcsv($handle, 1000, ";");
    
    // Array, um die Ergebnisse zu speichern
    $kundenData = [];

    // Zeilen durchgehen
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        // Datum aus der CSV-Datei (erste Spalte)
        $kundeDS = $data[3];

        // Wenn das Datum mit dem heutigen übereinstimmt, fügen wir es zum Array hinzu
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
    echo json_encode(["status" => "error", "message" => "Die Verkaufsliste konnte nicht geöffnet werden!"]);
}

?>
