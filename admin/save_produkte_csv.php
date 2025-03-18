<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Prüfe, ob der richtige Content-Type gesetzt ist
    $contentType = $_SERVER["CONTENT_TYPE"] ?? '';
    if (stripos($contentType, "application/json") === false) {
        echo "Fehler: Falscher Content-Type ($contentType)";
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);

    // Debug: Überprüfen, ob JSON-Daten angekommen sind
    file_put_contents("debug.log", print_r($data, true));

    if (isset($data['csvData'])) {
        file_put_contents("../daten/produkte.csv", $data['csvData']);
        echo "CSV erfolgreich gespeichert!";
    } else {
        echo "Fehler: Keine Daten erhalten.";
    }
} else {
    echo "Ungültige Anfrage.";
}
?>