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

// // Lese die .env-Datei
// $env = parse_ini_file('daten/.env');  // Lädt die Umgebungsvariablen aus der .env-Datei

// Wrapper-Datei einbinden
require_once 'VereinsfliegerRestInterface.php';

// VereinsfliegerRestInterface-Instanz erstellen
$restInterface = new VereinsfliegerRestInterface();

// Token-Handling
if (isset($_SESSION['accessToken']) && isset($_SESSION['tokenExpiry']) && $_SESSION['tokenExpiry'] > time()) {
    // Bestehenden Token weiterverwenden
    $restInterface->SetAccessToken($_SESSION['accessToken']);
    $tokenValid = true;
} else {
    // Token ist abgelaufen oder nicht vorhanden - Benutzer abmelden
    session_unset();
    session_destroy();
    header('Location: index.php'); 
    exit();
}

// Nur fortfahren wenn Token valid
if ($tokenValid) {
    // Nutzer abrufen
    if ($restInterface->GetUsers()) {
        // Abgerufene Nutzerdaten holen
        $usersData = $restInterface->getResponse();

        // Prüfen, ob die Daten bereits JSON sind
        if (!is_string($usersData)) {
            $usersData = json_encode($usersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        // Die Daten in eine JSON-Datei speichern
        $jsonFile = 'daten/Mitglieder.json';

        if (file_put_contents($jsonFile, $usersData)) {
            header('Content-Type: application/json');
            echo "<p>Daten erfolgreich abgerufen und in ".$jsonFile." gespeichert.</p>";
            echo "<button onclick=\"window.location.href='$jsonFile'\">Download</button>";
        } else {
            header('Content-Type: application/json');
            echo json_encode(["error" => "Fehler beim Speichern der Daten in $jsonFile."]);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(["error" => "Fehler beim Abrufen der Daten aus Vereinsflieger.de"]);
    }
}
?>
