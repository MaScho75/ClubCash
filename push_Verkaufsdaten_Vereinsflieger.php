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

// Wrapper-Datei einbinden

if (!file_exists('VereinsfliegerRestInterface.php')) {
    die("<pre>❌ Datei VereinsfliegerRestInterface.php nicht gefunden.</pre>");
}
require_once 'VereinsfliegerRestInterface.php';

echo "<p>✅ Wrapper-Datei VereinsfliegerRestInterface.php eingebunden.</p>";

// VereinsfliegerRestInterface-Instanz erstellen
$restInterface = new VereinsfliegerRestInterface();

// Token-Handling
if (isset($_SESSION['accessToken']) && isset($_SESSION['tokenExpiry']) && $_SESSION['tokenExpiry'] > time()) {
    // Bestehenden Token weiterverwenden
    $restInterface->SetAccessToken($_SESSION['accessToken']);
    echo "<p>✅ Bestehender Token wiederverwendet.</p>\n";
    $tokenValid = true;
} else {
    // Kein gültiger Token vorhanden - Benutzer abmelden
    session_destroy();
    echo "<p>❌ Kein gültiger Token vorhanden - Sie werden abgemeldet.</p>\n";
    header('Location: index.php');
    exit();
}

// Nur fortfahren wenn Token valid
if ($tokenValid) {
    // Abfragen der übertragenen Verkaufsdaten
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // JSON-Daten aus dem Request-Body lesen
        $json = file_get_contents('php://input');
        $data = json_decode($json, true); // true = as associative array

        if (json_last_error() === JSON_ERROR_NONE) {
            echo "<p>✅ Empfangene JSON-Daten</p>";
        } else {
            echo "<p>❌Fehler beim Parsen der JSON-Daten:</p>";
            echo json_last_error_msg();
        }
    } else {
        echo "<p>❌ Keine POST-Daten empfangen.</p>";
    }

    $erfolg = false; // Variable für den Erfolg der Übertragung

    foreach ($data as $key => $value) {
        if ($value['totalprice'] == 0) {
            // Wenn der Preis 0 ist, überspringen
            echo "<p>❌ Datensatz <i>" . ($value['comment']) . "</i> übersprungen, da keine Buchungen vorliegen.</p>";
            continue;
        }
        echo "<p><b>Beginn der Übertragung des Datensatzes Nummer: </b>";
        print_r($key+1);
        echo "<br><b> Daten: </b>";   
        print_r($value);
        
        $erfolg = $restInterface->InsertSale($value);

        if ($erfolg) {
            echo "<br>✅ Datensatz wurde erfolgreich übertragen.";
            // Jetzt soll ein neuer Datensatz in der Datenbank daten/umsatz.csv mit dem datensatz erstellt werden
            $csvFile = 'daten/umsatz.csv';
            // Passe das Format der CSV-Datei an: Datum;Zeit;Terminal;Schlüssel;Kundennummer;EAN;Produkt;Kategorie;Preis;MwSt
            // Erstelle eine neue Zeile für die CSV-Datei
            $date = $value['bookingdate'];
            $time = $value['Zeit'];
            $terminal = 'Z'; 
            $key = 9999999999; // Schlüssel
            $kundennummer = $value['Uid'] ; // Kundennummer
            $ean = 9999999999; // EAN
            $produkt = 'Kontoausgleich-VF'; // Produkt
            $kategorie = 'Buchung'; // Kategorie
            $preis = number_format(-$value['totalprice'], 2, '.', ''); // Preis mit 2 Dezimalstellen (negativ)
            $mwst = 0; // MwSt

            // Erstelle die CSV-Zeile
            $csvLine = implode(';', [
                $date,
                $time,
                $terminal,
                $key,
                $kundennummer,
                $ean,
                $produkt,
                $kategorie,
                $preis,
                $mwst
            ]) . PHP_EOL;

            // Schreibe die CSV-Zeile in die Datei
            if (file_put_contents($csvFile, $csvLine, FILE_APPEND) !== false) {
                echo "<br>✅ Kontoausgleich wurde in ClubCash gespeichert.";
            } else {
                echo "<br>❌ Fehler beim Speichern in der umsatz.csv.";
            }

        } else {
            echo "<br>❌ Fehler beim Übertragen des Datensatzes.";
        }

        echo "</p>";

    }
    

}
