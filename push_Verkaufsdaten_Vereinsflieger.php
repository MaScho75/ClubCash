<?php
session_start();

// Prüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
    header('Location: index.php'); // Falls nicht eingeloggt, zurück zur Login-Seite
    exit();
}

// Fehleranzeige aktivieren
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Lade die Datei config.json
    $configFile = 'daten/config.json';
if (!file_exists($configFile)) {
    die("<pre>❌ Konfigurationsdatei nicht gefunden: $configFile</pre>");
}
$config = json_decode(file_get_contents($configFile), true);
if (!$config) {
    die("<pre>❌ Fehler beim Laden der Konfigurationsdatei: " . json_last_error_msg() . "</pre>");
}

echo "<p>✅ Konfigurationsdatei geladen.</p>";

// Lese die .env-Datei
$env = parse_ini_file('daten/.env');  // Lädt die Umgebungsvariablen aus der .env-Datei

// Wrapper-Datei einbinden
require_once 'VereinsfliegerRestInterface.php';

// Anmeldeinformationen
$UserName = $env['USERNAME'];
$Password = $env['PASSWORT'];
$AppKey = $env['APPKEY'];
$AuthSecret = $env['AUTRHSECRET'];

// VereinsfliegerRestInterface-Instanz erstellen
$restInterface = new VereinsfliegerRestInterface();

// Anmeldung durchführen
if ($restInterface->SignIn($UserName, $Password, 0, $AppKey, $AuthSecret)) {
    echo "<p>✅ Anmeldung in Vereinsflieger war erfolgreich.</p>\n";

    // Abfragen der übertragenen Verkaufsdaten
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // JSON-Daten aus dem Request-Body lesen
        $json = file_get_contents('php://input');
        $data = json_decode($json, true); // true = as associative array

        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p>✅ Empfangene JSON-Daten</p>";
            //für den Debugging-Zweck:
            //echo "<pre>";
            //print_r($data);
            //echo "</pre>";
        } else {
            echo "<p>❌Fehler beim Parsen der JSON-Daten:</p>";
            echo json_last_error_msg();
        }
    } else {
        echo "<p>❌ Keine POST-Daten empfangen.</p>";
    }

    $erfolg = false; // Dummy-Variable für den Erfolg der Übertragung

    foreach ($data as $key => $value) {
        echo "<p><b>Beginn der Übertragung des Datensatzes Nummer: </b>";
        print_r($key+1);
        echo "<br><b> Daten: </b>";   
        print_r($value);
        
        $erfolg = $restInterface->InsertSale($value);

        if ($erfolg) {
            echo "<br>✅ Datensatz wurde erfolgreich übertragen.";
        } else {
            echo "<br>❌ Fehler beim Übertragen des Datensatzes.";
        }

        echo "</p>";

    }
    

}else {
    echo "<p>❌ Anmeldung in Vereinsflieger fehlgeschlagen.</p>\n";
    echo "<p>Fehler: " . $restInterface->getError() . "</p>";
}

?>
