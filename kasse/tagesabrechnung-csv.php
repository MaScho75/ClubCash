<?php
ini_set('display_errors', 1); // Nur für Debug-Zwecke
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Header für JSON und CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$file = "../daten/umsatz.csv";

// Heute im Format YYYY-MM-DD
date_default_timezone_set('Europe/Berlin');
$today = date("Y-m-d");

if (!file_exists($file)) {
    echo json_encode(["status" => "error", "message" => "Die Datei umsatz.csv existiert nicht."]);
    exit;
}

if (($handle = fopen($file, "r")) === false) {
    echo json_encode(["status" => "error", "message" => "Die Datei konnte nicht geöffnet werden."]);
    exit;
}

$headers = fgetcsv($handle, 1000, ";");
if ($headers === false) {
    echo json_encode(["status" => "error", "message" => "Header-Zeile fehlt oder ungültig."]);
    fclose($handle);
    exit;
}

$todayData = [];

while (($data = fgetcsv($handle, 1000, ";")) !== false) {
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
?>