<?php

/*
 * This file is part of ClubCash.
 *
 * ClubCash is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 *
 * ClubCash is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with ClubCash. If not, see <https://www.gnu.org/licenses/>.
 */

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

if (!$env) {
    die("<pre>❌ Fehler beim Laden der Umgebungsvariablen: " . json_last_error_msg() . "</pre>");
}
echo "<p>✅ Umgebungsvariablen geladen.</p>";

// Wrapper-Datei einbinden

if (!file_exists('VereinsfliegerRestInterface.php')) {
    die("<pre>❌ Datei VereinsfliegerRestInterface.php nicht gefunden.</pre>");
}
require_once 'VereinsfliegerRestInterface.php';

echo "<p>✅ Wrapper-Datei VereinsfliegerRestInterface.php eingebunden.</p>";

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
            // echo "<pre>Folgede Daten wurden empfangen:<br>";
            // echo "----------------------------------------<br>";
            // print_r($data);
            // echo "</pre>";
        } else {
            echo "<p>❌Fehler beim Parsen der JSON-Daten:</p>";
            echo json_last_error_msg();
        }
    } else {
        echo "<p>❌ Keine POST-Daten empfangen.</p>";
    }

    $erfolg = false; // Variable für den Erfolg der Übertragung

    foreach ($data as $key => $value) {
        echo "<p><b>Beginn der Übertragung des Datensatzes Nummer: </b>";
        print_r($key+1);
        echo "<br><b> Daten: </b>";   
        print_r($value);
        
        $erfolg = $restInterface->InsertSale($value);

        if ($erfolg) {
            echo "<br>✅ Datensatz wurde erfolgreich übertragen.";
            // Jetzt soll ein neuer Datensatz in der Datenbank daten/umsatz.csv mit dem datensatz erstellt werden
            $csvFile = 'daten/umsatz.csv';
            $csvData = array_values($value); // Convert associative array to indexed array
            $csvData = array_slice($csvData, 0, 10); // Take only the first 10 elements
            $csvLine = implode(';', $csvData); // Create CSV line with semicolon separator
            
            if (file_put_contents($csvFile, $csvLine . PHP_EOL, FILE_APPEND) !== false) {
                echo "<br>✅ Kontoausgleich wurde in ClubCash gespeichert.";
            } else {
                echo "<br>❌ Fehler beim Speichern in der umsatz.csv.";
            }

        } else {
            echo "<br>❌ Fehler beim Übertragen des Datensatzes.";
        }

        echo "</p>";

    }
    

}else {
    echo "<p>❌ Anmeldung in Vereinsflieger fehlgeschlagen.</p>\n";
}

?>
