<?php
header('Content-Type: application/json');

// Verbindung zur Datenbank
$host = "localhost";
$user = "d042e086";
$pass = "CLK20250220";
$dbname = "d042e086";

$conn = new mysqli($host, $user, $pass, $dbname);

// Überprüfung der Verbindung
if ($conn->connect_error) {
    die(json_encode(["status" => "Fehler", "message" => "Verbindung fehlgeschlagen: " . $conn->connect_error]));
}

$kundenID = $_GET["id"];

// SQL-Abfrage

$sql = <<<SQL
SELECT 
    DATE(Kassenliste.Zeitstempel) AS Datum,
    TIME_FORMAT(Kassenliste.Zeitstempel, '%H:%i') AS Zeit,
    Kassenliste.Terminal, 
    Produkte.Bezeichnung AS Produkt, 
    Kassenliste.Preis
FROM 
    Kassenliste 
JOIN 
    Produkte ON Kassenliste.Produkt_EAN = Produkte.EAN 
WHERE
	Kassenliste.Kunde = $kundenID
ORDER BY 
    Kassenliste.Zeitstempel DESC
SQL;

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$daten = [];
while ($row = $result->fetch_assoc()) {
    $daten[] = $row;
}

// JSON-Antwort
echo json_encode(["status" => "Erfolg", "daten" => $daten]);

// Verbindung schließen
$stmt->close();
$conn->close();
?>
