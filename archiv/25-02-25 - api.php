<?php
header('Content-Type: application/json');

// Verbindung zur Datenbank herstellen
$host = "localhost";
$user = "d042e086";
$pass = "CLK20250220";
$dbname = "d042e086";

$conn = new mysqli($host, $user, $pass, $dbname);

// Überprüfung der Verbindung
if ($conn->connect_error) {
    die(json_encode(["status" => "Fehler", "message" => "Verbindung fehlgeschlagen: " . $conn->connect_error]));
}

// JSON-Daten abrufen
$input = file_get_contents("php://input");
$decodedData = json_decode($input, true);

file_put_contents("debug_log.txt", json_encode($decodedData) . PHP_EOL, FILE_APPEND);
error_log(json_encode($decodedData)); // Debugging (entfernen, wenn nicht mehr benötigt)

if (isset($decodedData['empfangen']['produkte'])) {
    foreach ($decodedData['empfangen']['produkte'] as $produkt) {
        // SQL-Statement vorbereiten
        $stmt = $conn->prepare("INSERT INTO Kassenliste (Datum, Zeit, Terminal, Produkt_EAN, Preis, MwSt, Kunde) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("ssidsii",
            $produkt['Datum'],
            $produkt['Zeit'],
            $produkt['Terminal'],
            $produkt['EAN'],
            $produkt['Kosten'],
            $produkt['MwSt'],
            $produkt['Kunde']
        );

        // SQL-Statement ausführen
        if (!$stmt->execute()) {
            echo json_encode(["status" => "Fehler", "message" => "Fehler beim Einfügen: " . $stmt->error]);
            exit;
        }
    }

    echo json_encode(["status" => "Erfolg", "message" => "Daten erfolgreich gespeichert"]);
} else {
    echo json_encode(["status" => "Fehler", "message" => "Ungültige Daten"]);
}

// Verbindung schließen
$conn->close();
?>
