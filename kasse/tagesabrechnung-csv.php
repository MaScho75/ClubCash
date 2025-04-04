<?php
// Header für JSON und CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Pfad zur CSV-Datei
$file = "../daten/verkaufsliste.csv";

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
    echo json_encode(["status" => "error", "message" => "Die Verkaufsliste konnte nicht abegerufen werden!"]);
}
?>
