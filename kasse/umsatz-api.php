<?php
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
