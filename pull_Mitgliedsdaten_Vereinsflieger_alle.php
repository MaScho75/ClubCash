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


// Lese die .env-Datei
$env = parse_ini_file('.env');  // Lädt die Umgebungsvariablen aus der .env-Datei

// Wrapper-Datei einbinden
require_once 'VereinsfliegerRestInterface.php';

// Anmeldeinformationen (werden aus der .env-Datei geladen)
$UserName = $env['USERNAME'];
$Password = $env['PASSWORT'];
$AppKey = $env['APPKEY'];
$AuthSecret = $env['AUTRHSECRET'];

// VereinsfliegerRestInterface-Instanz erstellen
$restInterface = new VereinsfliegerRestInterface();

// Anmeldung durchführen
if ($restInterface->SignIn($UserName, $Password, 0, $AppKey, $AuthSecret)) {
    
    // Nutzer abrufen
    if ($restInterface->GetUsers()) {
        
        // Abgerufene Nutzerdaten holen
        $usersData = $restInterface->getResponse();

        // Prüfen, ob die Daten bereits JSON sind
        if (!is_string($usersData)) {
            $usersData = json_encode($usersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        // Die gefilterten Daten in eine JSON-Datei speichern
        $jsonFile = '../daten/kunden_alles.json';

        if (file_put_contents($jsonFile, $usersData)) {
            // Header vor jeglicher Ausgabe setzen
            header('Content-Type: application/json');
            echo $usersData;
        } else {
            header('Content-Type: application/json');
            echo json_encode(["error" => "Fehler beim Speichern der Daten"]);
        }

    } else {
        header('Content-Type: application/json');
        echo json_encode(["error" => "Fehler beim Abrufen der Daten aus Vereinsflieger.de"]);
    }

} else {
    header('Content-Type: application/json');
    echo json_encode(["error" => "Anmeldung fehlgeschlagen"]);
}

?>
