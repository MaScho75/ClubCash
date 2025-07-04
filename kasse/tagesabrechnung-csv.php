<?php
ini_set('display_errors', 0); // Keine Fehler im Production Mode anzeigen
error_reporting(0);

// Header für JSON und CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

$file = "../daten/umsatz.csv";

// Heute im Format YYYY-MM-DD
date_default_timezone_set('Europe/Berlin');
$today = date("Y-m-d");

try {
    if (!file_exists($file)) {
        throw new Exception("Die Datei umsatz.csv existiert nicht.");
    }

    if (($handle = fopen($file, "r")) === false) {
        throw new Exception("Die Datei konnte nicht geöffnet werden.");
    }

    // fgetcsv mit allen Parametern
    $headers = fgetcsv($handle, 1000, ";", "\"", "\\");
    if ($headers === false) {
        throw new Exception("Header-Zeile fehlt oder ungültig.");
    }

    $todayData = [];

    while (($data = fgetcsv($handle, 1000, ";", "\"", "\\")) !== false) {
        $date = $data[0];

        if ($date == $today) {
            if (count($headers) === count($data)) {
                $todayData[] = array_combine($headers, $data);
            }
        }
    }

    fclose($handle);

    echo json_encode([
        "status" => "success",
        "headers" => $headers,
        "data" => $todayData
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>