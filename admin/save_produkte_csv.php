<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    
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
