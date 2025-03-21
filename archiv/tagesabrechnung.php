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

// Aktuelles Datum abrufen
$heute = date("Y-m-d");

// SQL-Abfrage

$sql = <<<SQL
SELECT 
    Kassenliste.Terminal, 
    CONCAT(Kunden.Name, ', ', Kunden.Vorname) AS Kunde, 
    Produkte.Bezeichnung AS Produkt, 
    Kassenliste.Preis,
    TIME_FORMAT(Kassenliste.Zeitstempel, '%H:%i') AS Zeit
FROM 
    Kassenliste 
JOIN 
    Kunden ON Kassenliste.Kunde = Kunden.ID 
JOIN 
    Produkte ON Kassenliste.Produkt_EAN = Produkte.EAN 
WHERE 
    DATE(Kassenliste.Zeitstempel) = CURDATE() 
ORDER BY 
    TIME_FORMAT(Kassenliste.Zeitstempel, '%H:%i') ASC;
SQL;

$stmt = $conn->prepare($sql);
//$stmt->bind_param("s", $heute);
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
